<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Mcp\Request;

abstract class OrganizationMcpRequest extends Request
{
    private ?OrganizationModel $resolvedOrganization = null;

    private ?OrganizationModel $trashedOrganization = null;

    /**
     * Checked in the organization the request names, unless a request says otherwise.
     */
    protected function scope(): OrganizationModel|TeamModel|null
    {
        return $this->organization();
    }

    /**
     * The organization named by the `organization` slug argument.
     */
    protected function organization(): OrganizationModel
    {
        return $this->resolvedOrganization ??= OrganizationModel::query()
            ->where('slug', $this->get('organization'))
            ->firstOrFail();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function organizationRules(): array
    {
        return ['organization' => ['required', 'string']];
    }

    /**
     * Every organization tool names its organization.
     */
    protected function rules(): array
    {
        return $this->organizationRules();
    }

    /**
     * The same organization, found among deleted ones too: for restoring and
     * purging.
     */
    protected function trashedOrganization(): OrganizationModel
    {
        return $this->trashedOrganization ??= OrganizationModel::withTrashed()->where('slug', $this->get('organization'))->firstOrFail();
    }
}
