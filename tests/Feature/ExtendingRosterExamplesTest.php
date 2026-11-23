<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Keystone\Authorization\Abilities;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Authorization\Authorizer;
use RefactorCircus\Roster\Support\Authorization\Resources;
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
 * The registration lines of the README's resource and ability examples,
 * exactly as they are written there, and run below.
 */
const EXTENDING_ROSTER_CALLS = [
    "app(Resources::class)->resource('acme.widgets', WidgetModel::class);",
    'Gate::policy(WidgetModel::class, WidgetPolicy::class);',
    'app(Abilities::class)',
    "->define('acme.widgets.view', 'See widgets.', ['admin', 'member'])",
    "->define('acme.widgets.approve', 'Approve widgets.', ['admin']);",
];

/**
 * The README's registrations, with the fixtures standing in for
 * `WidgetModel` and `WidgetPolicy`.
 */
function registerReadmeExamples(): void
{
    app(Resources::class)->resource('acme.widgets', ExampleWidgetModel::class);
    Gate::policy(ExampleWidgetModel::class, ExampleWidgetPolicy::class);

    app(Abilities::class)
        ->define('acme.widgets.view', 'See widgets.', ['admin', 'member'])
        ->define('acme.widgets.approve', 'Approve widgets.', ['admin']);
}

/**
 * A user holding `acme.widgets.approve` in an organization, through the
 * role store.
 */
function exampleApprover(OrganizationModel $organization): User
{
    $user = user();

    grant($user, roleGranting(['acme.widgets.approve'], 'organization'), $organization);

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

it('registers the README resource, and its abilities resolve through its policy', function (): void {
    withRoleStore();
    registerReadmeExamples();

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

it('defines the README abilities in Keystone\'s registry, with the default roles they go to', function (): void {
    registerReadmeExamples();

    $abilities = app(Abilities::class)->all();

    expect(app(Abilities::class)->has('acme.widgets.view'))->toBeTrue()
        ->and($abilities['acme.widgets.view'])->toBe(['description' => 'See widgets.', 'grantTo' => ['admin', 'member']])
        ->and($abilities['acme.widgets.approve'])->toBe(['description' => 'Approve widgets.', 'grantTo' => ['admin']]);
});

it('lets the host Gate decide the README abilities without a role store', function (): void {
    registerReadmeExamples();
    $acme = organization(attributes: ['name' => 'Acme']);
    $widget = new ExampleWidgetModel(['organization_id' => $acme->getKey()]);
    $approver = user(['email' => 'approver@example.com']);
    Gate::define('acme.widgets.approve', fn (User $user): bool => $user->email === 'approver@example.com');

    expect($approver->can('approve', $widget))->toBeTrue()
        ->and(user()->can('approve', $widget))->toBeFalse()
        ->and($acme->owner->can('approve', $widget))->toBeTrue();
});
