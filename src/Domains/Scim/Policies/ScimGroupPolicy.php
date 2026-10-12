<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * Groups a SCIM client provisioned need `roster.scim.manage` in their
 * organization.
 */
class ScimGroupPolicy extends Policy
{
    public function view(?Model $user, ?ScimGroupModel $scimGroup = null): bool
    {
        return $this->permits($user, 'roster.scim.manage', $scimGroup);
    }

    public function manage(?Model $user, ?ScimGroupModel $scimGroup = null): bool
    {
        return $this->permits($user, 'roster.scim.manage', $scimGroup);
    }
}
