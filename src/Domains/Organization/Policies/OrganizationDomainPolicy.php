<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationDomainModel;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * An organization's email domains follow its settings: seeing one needs
 * `roster.organizations.view`, changing one `roster.organizations.update`.
 */
class OrganizationDomainPolicy extends Policy
{
    public function view(?Model $user, ?OrganizationDomainModel $domain = null): bool
    {
        return $this->permits($user, 'roster.organizations.view', $domain);
    }

    public function manage(?Model $user, ?OrganizationDomainModel $domain = null): bool
    {
        return $this->permits($user, 'roster.organizations.update', $domain);
    }
}
