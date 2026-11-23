<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use Workbench\App\Models\User;

/**
 * A member of the organization, with the given built-in role in it.
 */
function memberWithRole(OrganizationModel $organization, string $role): User
{
    $user = user();
    app(AddMemberAction::class)->execute($organization, new AddMemberData(user: $user->getRouteKey()));
    grant($user, $role, $organization);

    return $user;
}

it('rejects guests with 401', function (): void {
    organization(attributes: ['name' => 'Acme']);

    $this->getJson(route('roster.organizations.role-assignments.index', 'acme'))->assertUnauthorized();
});

it('rejects a signed-in user without roster.roles.view in the organization with 403', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);

    // Not a member at all.
    $this->actingAs(user())->getJson(route('roster.organizations.role-assignments.index', 'acme'))->assertForbidden();

    // A member whose role does not hold roster.roles.view.
    $this->actingAs(memberWithRole($acme, 'member'))->getJson(route('roster.organizations.role-assignments.index', 'acme'))->assertForbidden();
});

it('rejects roster.roles.view held only in another organization with 403', function (): void {
    organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);
    $admin = memberWithRole($globex, 'admin');

    $this->actingAs($admin);

    $this->getJson(route('roster.organizations.role-assignments.index', 'acme'))->assertForbidden();
    $this->getJson(route('roster.organizations.role-assignments.index', 'globex'))->assertOk();
});

it('lets a user with roster.roles.view in the organization list its assignments', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $admin = memberWithRole($acme, 'admin');

    $this->actingAs($admin)->getJson(route('roster.organizations.role-assignments.index', 'acme'))
        ->assertOk()
        ->assertJsonPath('data.0.organization', 'acme');
});

it('answers 404 for a deleted organization, even to a super-admin', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $super = user();
    grant($super, 'super-admin');
    $acme->delete();

    $this->actingAs($super)->getJson(route('roster.organizations.role-assignments.index', 'acme'))->assertNotFound();
});
