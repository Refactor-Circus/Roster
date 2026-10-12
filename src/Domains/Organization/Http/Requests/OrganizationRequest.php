<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Http\Request;

abstract class OrganizationRequest extends Request
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
     * The organization named in the route, by slug.
     */
    protected function organization(): OrganizationModel
    {
        return $this->resolvedOrganization ??= OrganizationModel::query()
            ->where('slug', $this->route('organization'))
            ->firstOrFail();
    }

    /**
     * The same organization, found among deleted ones too: for restoring and
     * purging.
     */
    protected function trashedOrganization(): OrganizationModel
    {
        return $this->trashedOrganization ??= OrganizationModel::withTrashed()->where('slug', $this->route('organization'))->firstOrFail();
    }
}
