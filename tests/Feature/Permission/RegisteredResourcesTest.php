<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Roster\Atrium\ScreenAccess;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Permission\Services\Resources;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Roster\Domains\Scim\Policies\ScimTokenPolicy;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Roster\Domains\Sso\Policies\SsoConnectionPolicy;
use RefactorCircus\Roster\Support\Policies\Policy;
use RefactorCircus\Roster\Tests\Fixtures\Policies\OpenPolicy;
use Workbench\App\Models\User;

/**
 * Another package's model, belonging to an organization through its
 * `organization()` relation.
 */
final class RegisteredWidgetModel extends Model
{
    protected $guarded = [];

    /**
     * @return BelongsTo<OrganizationModel, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }
}

/**
 * Another package's model with only an `organization_id` attribute.
 */
final class RegisteredGizmoModel extends Model
{
    protected $guarded = [];
}

/**
 * A registered model nobody wrote a policy for.
 */
final class UnpolicedGadgetModel extends Model {}

/**
 * The widget policy, answering `acme.widgets.*` with Roster's permissions.
 */
final class RegisteredWidgetPolicy extends Policy
{
    public function approve(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'acme.widgets.approve', $subject, $self);
    }
}

beforeEach(function (): void {
    config(['roster.authorization' => true]);

    PermissionModel::query()->create(['name' => 'acme.widgets.approve', 'description' => 'Approve widgets.', 'system' => true]);
    app(Permissions::class)->flush();
});

/**
 * A user holding `acme.widgets.approve` in an organization.
 */
function widgetApprover(OrganizationModel $organization): User
{
    $user = user();

    RoleAssignmentModel::query()->create([
        'role_id' => roleWith(['acme.widgets.approve'], 'organization')->id,
        'user_id' => $user->getKey(),
        'organization_id' => $organization->getKey(),
    ]);

    app(Permissions::class)->flush();

    return $user;
}

/**
 * The abilities the Gate was asked, with their arguments.
 *
 * @return ArrayObject<int, array<int, mixed>>
 */
function gateAsked(): ArrayObject
{
    $seen = new ArrayObject;

    Gate::after(function (mixed $user, string $ability, ?bool $result, array $arguments) use ($seen): void {
        $seen[] = [$ability, ...$arguments];
    });

    return $seen;
}

/**
 * The exception a throwing Blade render started from.
 */
function rootCause(Throwable $exception): Throwable
{
    while ($exception->getPrevious() !== null) {
        $exception = $exception->getPrevious();
    }

    return $exception;
}

it('sends a registered permission to its model\'s policy', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $approver = widgetApprover($acme);
    $other = user();

    app(Resources::class)->resource('acme.widgets', RegisteredWidgetModel::class);
    Gate::policy(RegisteredWidgetModel::class, RegisteredWidgetPolicy::class);
    $seen = gateAsked();

    $authorizer = app(Authorizer::class);

    expect($authorizer->check($approver, 'acme.widgets.approve', $acme))->toBeTrue()
        ->and($authorizer->check($other, 'acme.widgets.approve', $acme))->toBeFalse()
        ->and($seen->getArrayCopy())->toBe([
            ['approve', RegisteredWidgetModel::class, $acme, null],
            ['approve', RegisteredWidgetModel::class, $acme, null],
        ]);
});

it('asks no policy for a permission whose prefix is not registered', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    Gate::policy(RegisteredWidgetModel::class, OpenPolicy::class);
    $seen = gateAsked();

    expect(app(Authorizer::class)->check(user(), 'acme.widgets.approve', $acme))->toBeFalse()
        ->and($seen->getArrayCopy())->toBe([]);
});

it('looks a three-segment name up by everything before its last dot', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    app(Resources::class)->resource('acme.widgets', RegisteredWidgetModel::class);
    Gate::policy(RegisteredWidgetModel::class, OpenPolicy::class);
    $seen = gateAsked();

    // Prefix `acme.widgets.approve` is not registered: no policy, and the
    // user holds no such permission.
    expect(app(Authorizer::class)->check(user(), 'acme.widgets.approve.all', $acme))->toBeFalse()
        ->and($seen->getArrayCopy())->toBe([]);
});

it('throws, naming the resource, when a registered model has no policy', function (): void {
    Exceptions::fake();
    $acme = organization(attributes: ['name' => 'Acme']);
    $approver = widgetApprover($acme);
    app(Resources::class)->resource('acme.gadgets', UnpolicedGadgetModel::class);
    $this->actingAs($approver);
    request()->setUserResolver(fn (): User => $approver);

    $this->assertThrows(
        fn () => app(Authorizer::class)->check($approver, 'acme.gadgets.approve', $acme),
        InvalidArgumentException::class,
        'acme.gadgets',
    );
    $this->assertThrows(
        fn () => ScreenAccess::allows('acme.gadgets.approve', $acme),
        InvalidArgumentException::class,
        'acme.gadgets',
    );

    try {
        Blade::render("@rosterCan('acme.gadgets.approve', \$scope) yes @endrosterCan", ['scope' => $acme]);
        $this->fail('@rosterCan did not throw.');
    } catch (Throwable $exception) {
        expect(rootCause($exception))->toBeInstanceOf(InvalidArgumentException::class)
            ->and(rootCause($exception)->getMessage())->toContain('acme.gadgets');
    }

    Exceptions::assertNothingReported();

    // Nothing was remembered for the checks that threw: once the policy
    // exists, the same check reaches it.
    Gate::policy(UnpolicedGadgetModel::class, OpenPolicy::class);

    expect(app(Authorizer::class)->check($approver, 'acme.gadgets.approve', $acme))->toBeTrue();
});

it('finds a registered record\'s organization through its organization() relation', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);
    app(Resources::class)->resource('acme.widgets', RegisteredWidgetModel::class);
    Gate::policy(RegisteredWidgetModel::class, RegisteredWidgetPolicy::class);

    $widget = new RegisteredWidgetModel(['organization_id' => $acme->getKey()]);

    expect(app(Authorizer::class)->parentOf($widget, 'organization')?->is($acme))->toBeTrue()
        // A member holding the permission there.
        ->and(widgetApprover($acme)->can('approve', $widget))->toBeTrue()
        // A member without it.
        ->and(user()->can('approve', $widget))->toBeFalse()
        // A holder in another organization.
        ->and(widgetApprover($globex)->can('approve', $widget))->toBeFalse();
});

it('finds a registered record\'s organization through its organization_id attribute', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $gizmo = new RegisteredGizmoModel(['organization_id' => $acme->getKey()]);

    expect(app(Authorizer::class)->parentOf($gizmo, 'organization')?->is($acme))->toBeTrue()
        ->and(app(Authorizer::class)->parentOf($gizmo, 'team'))->toBeNull()
        ->and(app(Authorizer::class)->parentOf(new RegisteredGizmoModel, 'organization'))->toBeNull();
});

it('sees a resource registered after the first check', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $user = user();
    $resources = app(Resources::class);
    $authorizer = app(Authorizer::class);
    Gate::policy(RegisteredWidgetModel::class, OpenPolicy::class);

    expect($authorizer->check($user, 'acme.widgets.approve', $acme))->toBeFalse();

    $generation = $resources->generation();
    $resources->resource('acme.widgets', RegisteredWidgetModel::class);

    expect($resources->generation())->toBeGreaterThan($generation)
        ->and($authorizer->check($user, 'acme.widgets.approve', $acme))->toBeTrue();
});

it('takes registered permissions on ScreenAccess and @rosterCan', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $approver = widgetApprover($acme);
    $other = user();
    app(Resources::class)->resource('acme.widgets', RegisteredWidgetModel::class);
    Gate::policy(RegisteredWidgetModel::class, RegisteredWidgetPolicy::class);
    $view = "@rosterCan('acme.widgets.approve', \$scope) yes @endrosterCan";

    $this->actingAs($approver);
    request()->setUserResolver(fn (): User => $approver);

    expect(ScreenAccess::allows('acme.widgets.approve', $acme))->toBeTrue()
        ->and(trim(Blade::render($view, ['scope' => $acme])))->toBe('yes');

    $this->actingAs($other);
    request()->setUserResolver(fn (): User => $other);

    expect(ScreenAccess::allows('acme.widgets.approve', $acme))->toBeFalse()
        ->and(trim(Blade::render($view, ['scope' => $acme])))->toBe('');
});

it('registers the SSO and SCIM resources and policies from their own domains', function (): void {
    expect(app(Resources::class)->model('roster.sso'))->toBe(SsoConnectionModel::class)
        ->and(app(Resources::class)->model('roster.scim'))->toBe(ScimTokenModel::class)
        ->and(Gate::getPolicyFor(SsoConnectionModel::class))->toBeInstanceOf(SsoConnectionPolicy::class)
        ->and(Gate::getPolicyFor(ScimTokenModel::class))->toBeInstanceOf(ScimTokenPolicy::class);
});

it('leaves the impersonation-blocked list to the application', function (): void {
    $blocked = config('roster.impersonation.blocked');

    app(Resources::class)->resource('acme.widgets', RegisteredWidgetModel::class);
    app(PermissionCatalogue::class)->permission('acme.widgets.purge', 'Purge widgets.', ['admin']);

    expect(config('roster.impersonation.blocked'))->toBe($blocked)
        ->and(implode(' ', (array) $blocked))->not->toContain('acme.');
});
