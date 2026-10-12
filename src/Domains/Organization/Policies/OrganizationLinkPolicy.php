<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationLinkModel;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * An organization's links to external systems follow its settings: seeing
 * one needs `roster.organizations.view`, changing one `roster.organizations.update`.
 */
class OrganizationLinkPolicy extends Policy
{
    public function view(?Model $user, ?OrganizationLinkModel $link = null): bool
    {
        return $this->permits($user, 'roster.organizations.view', $link);
    }

    public function manage(?Model $user, ?OrganizationLinkModel $link = null): bool
    {
        return $this->permits($user, 'roster.organizations.update', $link);
    }
}
