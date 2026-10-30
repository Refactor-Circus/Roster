<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Roster\Atrium\ScreenAccess;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use Workbench\App\Models\User;

beforeEach(function (): void {
    $this->here = organization(user(), ['name' => 'Here']);
    $this->elsewhere = organization(user(), ['name' => 'Elsewhere']);

    // A host's section view, written where only these tests look.
    $directory = sys_get_temp_dir().'/roster-screen-access-'.getmypid();

    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    file_put_contents($directory.'/section.blade.php', '<span>{{ $record?->getKey() }}</span>');
    View::addNamespace('screenaccess', $directory);
});

/**
 * Turn authorization on and sign in a user holding these permissions
 * globally, or in an organization.
 *
 * @param  array<int, string>  $permissions
 */
function screenAccessViewer(array $permissions, ?OrganizationModel $in = null): User
{
    config()->set('roster.authorization', true);
    $viewer = user();

    RoleAssignmentModel::query()->create([
        'role_id' => roleWith($permissions, $in === null ? 'global' : 'organization')->id,
        'user_id' => $viewer->getKey(),
        'organization_id' => $in?->getKey(),
    ]);

    app(Permissions::class)->flush();
    test()->actingAs($viewer);

    return $viewer;
}

/**
 * Each record type of criterion 4, with the permission asked and where the
 * allowed viewer holds it: `here` (the record's organization) or `global`.
 * The denied viewer holds it in the other organization instead (or, for a
 * global check, only in the record's own organization).
 *
 * @return array<string, array{Closure(OrganizationModel): ?Model, string, string}>
 */
function screenAccessRecords(): array
{
    return [
        'an organization' => [fn (OrganizationModel $here): Model => $here, 'roster.members.view', 'here'],
        'a team' => [fn (OrganizationModel $here): Model => TeamModel::factory()->create(['organization_id' => $here->getKey()]), 'roster.teams.view', 'here'],
        'a user' => [fn (OrganizationModel $here): Model => user(), 'roster.users.update', 'global'],
        'a role in an organization' => [fn (OrganizationModel $here): Model => RoleModel::factory()->create(['scope' => 'organization', 'organization_id' => $here->getKey()]), 'roster.roles.view', 'here'],
        'a global role' => [fn (OrganizationModel $here): Model => RoleModel::factory()->create(['scope' => 'global']), 'roster.roles.view', 'global'],
        'an impersonation in an organization' => [fn (OrganizationModel $here): Model => ImpersonationModel::factory()->create(['impersonator_id' => user()->getKey(), 'user_id' => user()->getKey(), 'organization_id' => $here->getKey()]), 'roster.users.impersonate', 'here'],
        'a global impersonation' => [fn (OrganizationModel $here): Model => ImpersonationModel::factory()->create(['impersonator_id' => user()->getKey(), 'user_id' => user()->getKey(), 'organization_id' => null]), 'roster.users.impersonate', 'global'],
        'a transfer' => [fn (OrganizationModel $here): Model => TransferModel::factory()->create(['requested_by' => user()->getKey(), 'organization_id' => $here->getKey()]), 'roster.members.view', 'here'],
        'an invitation' => [fn (OrganizationModel $here): Model => InvitationModel::factory()->create(['organization_id' => $here->getKey()]), 'roster.invitations.view', 'here'],
        'no record' => [fn (OrganizationModel $here): ?Model => null, 'roster.users.view', 'global'],
    ];
}

it('grants a permission held in the scope the screens use for the record', function (Closure $record, string $permission, string $held): void {
    $model = $record($this->here);

    screenAccessViewer([$permission], $held === 'here' ? $this->here : null);

    expect(ScreenAccess::allowsOn($permission, $model))->toBeTrue();
})->with(screenAccessRecords());

it('refuses a permission held only elsewhere', function (Closure $record, string $permission, string $held): void {
    $model = $record($this->here);

    // Held in another organization, or in an organization where the check is global.
    screenAccessViewer([$permission], $held === 'here' ? $this->elsewhere : $this->here);

    expect(ScreenAccess::allowsOn($permission, $model))->toBeFalse();
})->with(screenAccessRecords());

it('asks a transfer\'s requester as the user it is about, and without one, the organization only', function (): void {
    $viewer = screenAccessViewer([]);
    $transfer = TransferModel::factory()->create(['requested_by' => $viewer->getKey(), 'organization_id' => $this->here->getKey()]);

    // The requester may see their own transfer without the permission.
    expect(ScreenAccess::allowsOn('roster.members.view', $transfer))->toBeTrue();

    // Without a requester only the organization is asked.
    $transfer->setRelation('requester', null);

    expect(ScreenAccess::allowsOn('roster.members.view', $transfer))->toBeFalse();
});

it('refuses a record type it does not know, whatever the viewer holds', function (): void {
    screenAccessViewer(['roster.roles.view', 'roster.users.view']);

    expect(ScreenAccess::allowsOn('roster.roles.view', PermissionModel::query()->firstOrFail()))->toBeFalse()
        ->and(ScreenAccess::allowsOn('roster.roles.view', null))->toBeTrue();
});

it('shows a permission section in the organization\'s aside only to those holding it there', function (): void {
    Atrium::screens()->section('roster.organization', 'aside', SectionDefinition::make('test.members')->view('screenaccess::section')->permission('roster.members.view'));

    screenAccessViewer(['roster.organizations.view', 'roster.members.view'], $this->here);

    $this->get(route('atrium.roster.organizations.show', $this->here))
        ->assertOk()
        ->assertSee('data-testid="screen-aside"', false)
        ->assertSee('data-testid="section-test.members"', false);

    app()->forgetScopedInstances();

    // Opens the screen here, holds the section's permission only elsewhere.
    $viewer = screenAccessViewer(['roster.organizations.view'], $this->here);
    RoleAssignmentModel::query()->create([
        'role_id' => roleWith(['roster.members.view'], 'organization')->id,
        'user_id' => $viewer->getKey(),
        'organization_id' => $this->elsewhere->getKey(),
    ]);
    app(Permissions::class)->flush();

    $this->get(route('atrium.roster.organizations.show', $this->here))
        ->assertOk()
        ->assertDontSee('data-testid="screen-aside"', false)
        ->assertDontSee('data-testid="section-test.members"', false);
});

it('shows a permission row action only in the rows the viewer may update', function (): void {
    Atrium::screens()->section('roster.users', 'row.actions', SectionDefinition::make('test.update')->view('screenaccess::section')->permission('roster.users.update'));

    user();
    user();

    // May list users, and update only themselves.
    $viewer = screenAccessViewer(['roster.users.view']);

    $content = (string) $this->get(route('atrium.roster.users.index'))->assertOk()->getContent();

    expect(substr_count($content, 'data-testid="section-test.update"'))->toBe(1)
        ->and($content)->toContain('data-testid="section-test.update"><span>'.$viewer->getKey().'</span>');

    app()->forgetScopedInstances();

    screenAccessViewer(['roster.users.view', 'roster.users.update']);

    $response = $this->get(route('atrium.roster.users.index'))->assertOk();

    expect(substr_count((string) $response->getContent(), 'data-testid="section-test.update"'))->toBe($response->viewData('users')->count());
});

it('asks no query per row for a list\'s row actions', function (string $key, string $route, Closure $seed): void {
    $organization = $this->here;
    $requester = user();
    $this->actingAs(user());

    // The queries one request to the list asks.
    $count = function () use ($route): int {
        app()->forgetScopedInstances();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get(route($route))->assertOk();

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queries;
    };

    $section = SectionDefinition::make('test.row')->view('screenaccess::section')->permission('roster.roles.update');

    $seed($organization, $requester);
    $one = $count();

    Atrium::screens()->section($key, 'row.actions', $section);
    $oneWithSection = $count();

    $seed($organization, $requester);
    $seed($organization, $requester);
    $threeWithSection = $count();

    // Back to nothing registered, as after the test.
    $this->tearDownResetsScreenExtensions();
    $three = $count();

    // The section's access check reads each row's organization (and a
    // transfer's requester) from what the list already loaded.
    expect($three)->toBeGreaterThan(0)
        ->and($threeWithSection - $three)->toBe($oneWithSection - $one);
})->with([
    'roles' => ['roster.roles', 'atrium.roster.roles.index', fn (OrganizationModel $organization, User $requester): Model => RoleModel::factory()->create(['scope' => 'organization', 'organization_id' => $organization->getKey()])],
    'impersonations' => ['roster.impersonations', 'atrium.roster.impersonations.index', fn (OrganizationModel $organization, User $requester): Model => ImpersonationModel::factory()->create(['impersonator_id' => $requester->getKey(), 'user_id' => user()->getKey(), 'organization_id' => $organization->getKey()])],
    'transfers' => ['roster.transfers', 'atrium.roster.transfers.index', fn (OrganizationModel $organization, User $requester): Model => TransferModel::factory()->create(['requested_by' => $requester->getKey(), 'organization_id' => $organization->getKey()])],
]);
