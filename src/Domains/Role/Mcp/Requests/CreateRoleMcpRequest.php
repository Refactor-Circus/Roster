<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Actions\CreateRoleAction;
use RefactorCircus\Roster\Domains\Role\Data\CreateRoleData;
use RefactorCircus\Roster\Domains\Role\Resources\RoleResource;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Mcp\Request;
use RefactorCircus\Roster\Support\Scopes;

final class CreateRoleMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    protected function scope(): OrganizationModel|TeamModel|null
    {
        return Scopes::fromInput($this->get('organization'), $this->get('team'));
    }

    protected function rules(): array
    {
        return CreateRoleData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new RoleResource(app(CreateRoleAction::class)->execute(CreateRoleData::fromValidated($validated), $this->actor())))->toStructuredResponse();
    }
}
