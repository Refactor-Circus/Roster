<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\SwitchContextAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Data\SwitchContextData;
use RefactorCircus\Roster\Domains\Permission\Actions\CreatePermissionAction;
use RefactorCircus\Roster\Domains\Permission\Data\CreatePermissionData;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;

it('answers can() for roster permissions in the given scope', function (): void {
    app(CreatePermissionAction::class)->execute(new CreatePermissionData(name: 'invoices.edit'));
    $role = roleWith(['invoices.edit'], 'organization');

    $acme = organization();
    $globex = organization(attributes: ['name' => 'Globex']);
    $ada = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));
    RoleAssignmentModel::query()->create(['role_id' => $role->id, 'user_id' => $ada->getKey(), 'organization_id' => $acme->id]);

    expect($ada->can('invoices.edit', $acme))->toBeTrue()
        ->and($ada->can('invoices.edit', $globex))->toBeFalse()
        // A team argument checks within its organization.
        ->and($ada->can('invoices.edit', app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'))))->toBeTrue();
});

it('uses the current context when no scope is given', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));
    app(SwitchContextAction::class)->execute($ada, new SwitchContextData(organization: 'acme'));
    app(Permissions::class)->flush();

    expect($ada->can('roster.members.view'))->toBeTrue()
        ->and($ada->can('roster.members.manage'))->toBeFalse();
});

it('leaves unknown abilities to the app', function (): void {
    Gate::define('publish-post', fn (): bool => true);
    $ada = user();

    expect($ada->can('publish-post'))->toBeTrue()
        ->and($ada->can('something-undefined'))->toBeFalse();
});

it('lets super-admins pass every gate', function (): void {
    Gate::define('publish-post', fn (): bool => false);
    $ada = user();
    grant($ada, 'super-admin');

    expect($ada->can('publish-post'))->toBeTrue();
});

it('works in blade', function (): void {
    $ada = user();
    $this->actingAs($ada);

    expect(Blade::render("@can('roster.users.view') yes @else no @endcan"))->toContain('no');

    grant($ada, 'super-admin');

    expect(Blade::render("@can('roster.users.view') yes @else no @endcan"))->toContain('yes');
});

it('defines the atrium gate from atrium.view when the app has not', function (): void {
    $ada = user();

    expect(Gate::forUser($ada)->allows('viewAtrium'))->toBeFalse();

    RoleAssignmentModel::query()->create(['role_id' => roleWith(['atrium.view'])->id, 'user_id' => $ada->getKey()]);
    app(Permissions::class)->flush();

    expect(Gate::forUser($ada)->allows('viewAtrium'))->toBeTrue();
});
