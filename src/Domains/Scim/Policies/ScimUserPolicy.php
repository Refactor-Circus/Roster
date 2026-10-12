<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Scim\Models\ScimUserModel;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * Accounts a SCIM client provisioned need `roster.scim.manage` in their
 * organization.
 */
class ScimUserPolicy extends Policy
{
    public function view(?Model $user, ?ScimUserModel $scimUser = null): bool
    {
        return $this->permits($user, 'roster.scim.manage', $scimUser);
    }

    public function manage(?Model $user, ?ScimUserModel $scimUser = null): bool
    {
        return $this->permits($user, 'roster.scim.manage', $scimUser);
    }
}
