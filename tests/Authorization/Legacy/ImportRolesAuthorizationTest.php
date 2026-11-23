<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Roster\Domains\Transfer\Data\StartImportData;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;

it('refuses roles the importer cannot assign', function (): void {
    $acme = organization(user(), ['name' => 'Acme', 'domains' => ['acme.test']]);
    $custom = RoleModel::factory()->create(['scope' => 'organization', 'slug' => 'auditor']);
    $custom->permissions()->sync(PermissionModel::query()->where('name', 'roster.audit.view')->pluck('id'));
    $lead = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $lead->getRouteKey()));
    RoleAssignmentModel::query()->create(['role_id' => roleWith(['roster.members.manage', 'roster.members.view'], 'organization')->id, 'user_id' => $lead->getKey(), 'organization_id' => $acme->id]);

    $transfer = app(StartImportAction::class)->execute(new StartImportData(type: TransferType::ImportMembers, organization: 'acme', content: "email,role\nnew@acme.test,auditor"), $lead);

    expect($transfer->rows()[0]['action'])->toBe('error')
        ->and($transfer->rows()[0]['reasons'][0])->toContain('cannot assign the auditor role');
});
