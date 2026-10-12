<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Actions\ListRolesAction;
use RefactorCircus\Roster\Domains\Role\Data\ListRolesData;
use RefactorCircus\Roster\Domains\Role\Resources\RoleResource;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Mcp\Request;
use RefactorCircus\Roster\Support\Scopes;

final class ListRolesMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    protected function scope(): OrganizationModel|TeamModel|null
    {
        return Scopes::fromInput($this->get('organization'), $this->get('team'));
    }

    protected function acrossOrganizations(): bool
    {
        return true;
    }

    protected function rules(): array
    {
        return ListRolesData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $roles = app(ListRolesAction::class)->execute(ListRolesData::fromValidated($validated), $this->organizations());

        return RoleResource::collection($roles)->toStructuredResponse();
    }
}
