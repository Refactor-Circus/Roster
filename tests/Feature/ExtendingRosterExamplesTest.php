<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Permission\Services\Resources;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Support\Policies\Policy;
use Workbench\App\Models\User;

/**
 * The README's `WidgetModel`: another package's model, belonging to an
 * organization.
 */
final class ExampleWidgetModel extends Model
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
 * The README's `WidgetPolicy`.
 */
final class ExampleWidgetPolicy extends Policy
{
    public function approve(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'acme.widgets.approve', $subject, $self);
    }
}

/**
 * The registration lines of the README's resource and catalogue examples,
 * exactly as they are written there, and run below.
 */
const EXTENDING_ROSTER_CALLS = [
    "app(Resources::class)->resource('acme.widgets', WidgetModel::class);",
    'Gate::policy(WidgetModel::class, WidgetPolicy::class);',
    'app(PermissionCatalogue::class)',
    "->permission('acme.widgets.view', 'See widgets.', ['admin', 'member'])",
    "->permission('acme.widgets.approve', 'Approve widgets.', ['admin']);",
];

/**
 * The README's registrations, with the fixtures standing in for
 * `WidgetModel` and `WidgetPolicy`.
 */
function registerReadmeExamples(): void
{
    app(Resources::class)->resource('acme.widgets', ExampleWidgetModel::class);
    Gate::policy(ExampleWidgetModel::class, ExampleWidgetPolicy::class);

    app(PermissionCatalogue::class)
        ->permission('acme.widgets.view', 'See widgets.', ['admin', 'member'])
        ->permission('acme.widgets.approve', 'Approve widgets.', ['admin']);
}

/**
 * The shared built-in role with this slug.
 */
function sharedRole(string $slug): ?RoleModel
{
    return RoleModel::query()->whereNull('organization_id')->where('slug', $slug)->first();
}

/**
 * The permission names a role holds.
 *
 * @return array<int, string>
 */
function rolePermissions(?RoleModel $role): array
{
    return $role?->permissions()->orderBy('name')->pluck('name')->map(strval(...))->all() ?? [];
}

/**
 * A user holding `acme.widgets.approve` in an organization.
 */
function exampleApprover(OrganizationModel $organization): User
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

beforeEach(function (): void {
    config(['roster.authorization' => true]);
});

it('runs the registration lines the README shows', function (): void {
    $readme = (string) file_get_contents(dirname(__DIR__, 2).'/README.md');
    $start = strpos($readme, "\n## Extending Roster\n");

    expect($start)->not->toBeFalse('The README has no Extending Roster section');

    $end = strpos($readme, "\n## ", (int) $start + 1);
    $section = substr($readme, (int) $start, $end === false ? null : $end - (int) $start);

    foreach (EXTENDING_ROSTER_CALLS as $call) {
        expect($section)->toContain($call);
    }
});

it('registers the README resource, and its permissions resolve through its policy', function (): void {
    registerReadmeExamples();
    $this->artisan('roster:sync-permissions')->assertSuccessful();

    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);
    $widget = new ExampleWidgetModel(['organization_id' => $acme->getKey()]);
    $authorizer = app(Authorizer::class);

    expect(app(Resources::class)->model('acme.widgets'))->toBe(ExampleWidgetModel::class)
        ->and($authorizer->check(exampleApprover($acme), 'acme.widgets.approve', $acme))->toBeTrue()
        ->and($authorizer->check(user(), 'acme.widgets.approve', $acme))->toBeFalse()
        ->and(exampleApprover($acme)->can('approve', $widget))->toBeTrue()
        ->and(user()->can('approve', $widget))->toBeFalse()
        ->and(exampleApprover($globex)->can('approve', $widget))->toBeFalse();
});

it('registers the README permissions, creating them without touching existing built-in roles', function (): void {
    $before = rolePermissions(sharedRole('admin'));

    registerReadmeExamples();

    expect(app(PermissionCatalogue::class)->has('acme.widgets.view'))->toBeTrue()
        ->and(app(PermissionCatalogue::class)->grantedTo('admin'))->toContain('acme.widgets.view', 'acme.widgets.approve')
        ->and(app(PermissionCatalogue::class)->grantedTo('member'))->toContain('acme.widgets.view')
        ->not->toContain('acme.widgets.approve');

    // Registered after the role tables migrated, as in an existing app.
    $this->artisan('roster:sync-permissions')->assertSuccessful();

    expect(PermissionModel::query()->whereIn('name', ['acme.widgets.approve', 'acme.widgets.view'])->where('system', true)->count())->toBe(2)
        ->and(rolePermissions(sharedRole('admin')))->toBe($before);
});

it('grants the README permissions to the built-in roles a sync creates', function (): void {
    sharedRole('admin')?->delete();
    sharedRole('member')?->delete();

    registerReadmeExamples();
    $this->artisan('roster:sync-permissions')->assertSuccessful();

    expect(rolePermissions(sharedRole('admin')))->toContain('acme.widgets.view', 'acme.widgets.approve')
        ->and(rolePermissions(sharedRole('member')))->toContain('acme.widgets.view')
        ->not->toContain('acme.widgets.approve');
});
