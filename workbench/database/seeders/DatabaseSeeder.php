<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use RefactorCircus\Roster\Domains\Invitation\Actions\CreateInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Data\CreateInvitationData;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;
use RefactorCircus\Roster\Domains\Permission\Actions\CreatePermissionAction;
use RefactorCircus\Roster\Domains\Permission\Data\CreatePermissionData;
use RefactorCircus\Roster\Domains\Role\Actions\AssignRoleAction;
use RefactorCircus\Roster\Domains\Role\Actions\CreateRoleAction;
use RefactorCircus\Roster\Domains\Role\Data\AssignRoleData;
use RefactorCircus\Roster\Domains\Role\Data\CreateRoleData;
use RefactorCircus\Roster\Domains\Role\Enums\RoleScope;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Actions\SuspendUserAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Domains\User\Data\SuspendUserData;
use RefactorCircus\Roster\Support\Statuses;
use Workbench\Database\Factories\UserFactory;

/**
 * Demo data, built through Roster's own Actions.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Invitation emails go nowhere in the demo.
        Notification::fake();

        $admin = UserFactory::new()->create(['name' => 'Admin', 'email' => 'admin@example.com']);
        Artisan::call('roster:grant-super-admin', ['email' => 'admin@example.com']);

        $ada = UserFactory::new()->create(['name' => 'Ada Lovelace', 'email' => 'ada@acme.test']);
        $grace = UserFactory::new()->create(['name' => 'Grace Hopper', 'email' => 'grace@acme.test']);
        $alan = UserFactory::new()->create(['name' => 'Alan Turing', 'email' => 'alan@globex.test']);
        $eve = UserFactory::new()->create(['name' => 'Eve Example', 'email' => 'eve@example.com']);

        $acme = app(CreateOrganizationAction::class)->execute(new CreateOrganizationData(name: 'Acme', domains: ['acme.test']), $ada);
        $globex = app(CreateOrganizationAction::class)->execute(new CreateOrganizationData(name: 'Globex'), $alan);

        app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $grace->getRouteKey()));
        app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $admin->getRouteKey()));

        $engineering = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Engineering'));
        app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Support'));
        app(CreateTeamAction::class)->execute($globex, new CreateTeamData(name: 'Research'));

        app(AddTeamMemberAction::class)->execute($engineering, new AddTeamMemberData(user: $grace->getRouteKey()));
        app(AssignRoleAction::class)->execute($grace, new AssignRoleData(
            role: RoleModel::query()->where('slug', 'lead')->value('id'),
            organization: $acme->slug,
            team: $engineering->slug,
        ));

        app(CreateInvitationAction::class)->execute($acme, new CreateInvitationData(email: 'new.hire@acme.test', teams: ['support']), $ada);

        app(CreatePermissionAction::class)->execute(new CreatePermissionData(name: 'invoices.edit', description: 'Edit invoices.'));
        $billing = app(CreateRoleAction::class)->execute(new CreateRoleData(
            name: 'Billing',
            scope: RoleScope::Organization,
            permissions: ['invoices.edit', 'roster.members.view'],
        ));
        app(AssignRoleAction::class)->execute($grace, new AssignRoleData(role: $billing->id, organization: $acme->slug));

        app(SuspendUserAction::class)->execute($eve, new SuspendUserData(reason: 'Demo: a suspended account.'));

        // Demo: an account awaiting approval, also on Acme's Members tab.
        $pat = app(CreateUserAction::class)->execute(new CreateUserData(name: 'Pat Pending', email: 'pat@acme.test', status: Statuses::users()::pending()));
        app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $pat->getRouteKey()));
    }
}
