<?php

declare(strict_types=1);

require_once __DIR__.'/fixtures.php';

use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use Workbench\App\Models\User;

/**
 * A signed-in Atrium user holding exactly these global permissions, and
 * these in an organization.
 *
 * @param  array<int, string>  $permissions
 * @param  array<int, string>  $inOrganization
 */
function viewer(array $permissions = [], ?OrganizationModel $organization = null, array $inOrganization = []): User
{
    $user = user();

    RoleAssignmentModel::query()->create(['role_id' => roleWith(['atrium.view', ...$permissions])->id, 'user_id' => $user->getKey()]);

    if ($organization !== null) {
        RoleAssignmentModel::query()->create([
            'role_id' => roleWith($inOrganization, 'organization')->id,
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
        ]);
    }

    return $user;
}

function testId(string $id): string
{
    return 'data-testid="'.$id.'"';
}

it('shows a user page without the controls the viewer may not use', function (): void {
    $world = authorizationWorld();

    $this->actingAs(viewer(['roster.users.view']))
        ->get(route('atrium.roster.users.show', $world['target']->getRouteKey()))
        ->assertOk()
        ->assertDontSee(testId('save-account'), false)
        ->assertDontSee(testId('save-profile'), false)
        ->assertDontSee(testId('change-status'), false)
        ->assertDontSee(testId('switch-context'), false)
        ->assertDontSee(testId('domain-join'), false)
        ->assertDontSee(testId('roles-card'), false)
        ->assertDontSee(testId('impersonate-card'), false)
        ->assertDontSee(testId('danger-zone'), false);
});

it('shows each user page control with its permission', function (array $permissions, string $control): void {
    $world = authorizationWorld();

    $this->actingAs(viewer(['roster.users.view', ...$permissions]))
        ->get(route('atrium.roster.users.show', $world['target']->getRouteKey()))
        ->assertOk()
        ->assertSee(testId($control), false);
})->with([
    'account' => [['roster.users.update'], 'save-account'],
    'profile' => [['roster.users.update'], 'save-profile'],
    'status' => [['roster.users.manage-status'], 'change-status'],
    'domain join' => [['roster.users.update'], 'domain-join'],
    'roles' => [['roster.roles.view'], 'roles-card'],
    'impersonate' => [['roster.users.impersonate'], 'impersonate-card'],
    'delete' => [['roster.users.delete'], 'danger-zone'],
]);

it('shows roles without the assign and revoke controls to those who may only view them', function (): void {
    $world = authorizationWorld();

    $this->actingAs(viewer(['roster.users.view', 'roster.roles.view']))
        ->get(route('atrium.roster.users.show', $world['target']->getRouteKey()))
        ->assertSee(testId('roles-card'), false)
        ->assertDontSee(testId('assign-role'), false)
        ->assertDontSee(testId('revoke-role'), false);

    $this->actingAs(viewer(['roster.users.view', 'roster.roles.view', 'roster.roles.assign']))
        ->get(route('atrium.roster.users.show', $world['target']->getRouteKey()))
        ->assertSee(testId('assign-role'), false)
        ->assertSee(testId('revoke-role'), false);
});

it('offers role assignment only in the organizations the viewer may assign in', function (): void {
    $world = authorizationWorld();
    $viewer = viewer(['roster.users.view', 'roster.roles.view'], $world['acme'], ['roster.roles.assign']);

    $this->actingAs($viewer)
        ->get(route('atrium.roster.users.show', $world['target']->getRouteKey()))
        ->assertSee(testId('assign-role'), false)
        ->assertDontSee('<option value="">'.__('roster::roster.scope_global').'</option>', false);
});

it('shows a user their own profile and roles but not admin controls', function (): void {
    $viewer = viewer(['roster.users.view']);

    $this->actingAs($viewer)
        ->get(route('atrium.roster.users.show', $viewer->getRouteKey()))
        ->assertOk()
        ->assertSee(testId('save-profile'), false)
        ->assertSee(testId('roles-card'), false)
        ->assertDontSee(testId('save-account'), false)
        ->assertDontSee(testId('impersonate-card'), false)
        ->assertDontSee(testId('danger-zone'), false);
});

it('hides user list actions without their permissions', function (): void {
    $this->actingAs(viewer(['roster.users.view']))
        ->get(route('atrium.roster.users.index'))
        ->assertOk()
        ->assertDontSee(testId('new-user'), false);

    $this->actingAs(viewer(['roster.users.view', 'roster.users.create']))
        ->get(route('atrium.roster.users.index'))
        ->assertSee(testId('new-user'), false);
});

it('lists only the organization tabs the viewer may open', function (): void {
    $world = authorizationWorld();
    $viewer = viewer([], $world['acme'], ['roster.organizations.view', 'roster.members.view']);

    $this->actingAs($viewer)
        ->get(route('atrium.roster.organizations.show', 'acme'))
        ->assertOk()
        ->assertSee(testId('tab-members'), false)
        ->assertSee(testId('tab-settings'), false)
        ->assertDontSee(testId('tab-roles'), false)
        ->assertDontSee(testId('add-member'), false)
        ->assertDontSee(testId('remove-member'), false);

    $this->actingAs($viewer)->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'roles']))->assertForbidden();
    // Activity is no longer a built-in tab: the old link opens the default tab.
    $this->actingAs($viewer)->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'activity']))->assertOk();
});

it('opens on the first tab the viewer may see', function (): void {
    $world = authorizationWorld();
    $viewer = viewer([], $world['acme'], ['roster.organizations.view']);

    // Settings is the only tab this viewer may open, so it is the default;
    // Atrium draws no tab bar for a single tab.
    $this->actingAs($viewer)
        ->get(route('atrium.roster.organizations.show', 'acme'))
        ->assertOk()
        ->assertDontSee(testId('tab-members'), false)
        ->assertDontSee(testId('screen-tabs'), false)
        ->assertSee(__('roster::roster.external_links'))
        ->assertDontSee(testId('save-organization'), false)
        ->assertDontSee(testId('link-organization'), false)
        ->assertDontSee(testId('danger-zone'), false);
});

it('shows organization controls with their permissions in that organization', function (): void {
    $world = authorizationWorld();
    $viewer = viewer([], $world['acme'], [
        'roster.organizations.view', 'roster.organizations.update', 'roster.organizations.delete',
        'roster.members.view', 'roster.members.manage', 'roster.teams.view', 'roster.teams.manage',
        'roster.invitations.view', 'roster.invitations.manage',
    ]);

    $this->actingAs($viewer)->get(route('atrium.roster.organizations.show', 'acme'))
        ->assertSee(testId('add-member'), false)
        ->assertSee(testId('remove-member'), false)
        ->assertSee(testId('organization-transfers'), false);

    $this->actingAs($viewer)->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'teams']))
        ->assertSee(testId('create-team'), false);

    $this->actingAs($viewer)->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'invitations']))
        ->assertSee(testId('send-invitation'), false)
        ->assertSee(testId('revoke-invitation'), false);

    $this->actingAs($viewer)->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'settings']))
        ->assertSee(testId('save-organization'), false)
        ->assertSee(testId('link-organization'), false)
        ->assertSee(testId('danger-zone'), false);
});

it('offers only the imports and exports the viewer may start', function (): void {
    $this->actingAs(viewer(['roster.users.view']))
        ->get(route('atrium.roster.transfers.index'))
        ->assertOk()
        ->assertDontSee(testId('import-card'), false)
        ->assertSee(testId('export-card'), false)
        ->assertSee('value="export_users"', false)
        ->assertDontSee('value="export_organizations"', false);

    $this->actingAs(viewer(['roster.users.view', 'roster.users.create']))
        ->get(route('atrium.roster.transfers.index'))
        ->assertSee(testId('import-card'), false)
        ->assertSee('value="import_users"', false)
        ->assertDontSee('value="import_organizations"', false);
});

it('shows roles and permissions read only to those who may not manage them', function (): void {
    $world = authorizationWorld();
    $viewer = viewer(['roster.roles.view']);

    $this->actingAs($viewer)->get(route('atrium.roster.roles.index'))
        ->assertOk()
        ->assertDontSee(testId('new-role-card'), false);

    $this->actingAs($viewer)->get(route('atrium.roster.roles.show', $world['role']->id))
        ->assertOk()
        ->assertSee(testId('role-permissions'), false)
        ->assertDontSee(testId('save-role'), false)
        ->assertDontSee(testId('delete-role'), false);

    $this->actingAs($viewer)->get(route('atrium.roster.permissions.index'))
        ->assertOk()
        ->assertDontSee(testId('new-permission-card'), false)
        ->assertDontSee('name="description"', false);
});
