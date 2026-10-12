<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * A user's profile follows the user: `roster.users.view` to see it,
 * `roster.users.update` to change it. Everyone may do both to their own.
 */
class ProfilePolicy extends Policy
{
    public function view(?Model $user, ?ProfileModel $profile = null): bool
    {
        return $this->permits($user, 'roster.users.view', $profile, $profile?->user);
    }

    public function update(?Model $user, ?ProfileModel $profile = null): bool
    {
        return $this->permits($user, 'roster.users.update', $profile, $profile?->user);
    }
}
