<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Http\Request;
use RefactorCircus\Roster\Support\Users;

abstract class UserRequest extends Request
{
    private ?Model $target = null;

    private ?Model $trashed = null;

    /**
     * The user named in the route, found by the user model's route key.
     */
    protected function targetUser(): Model
    {
        return $this->target ??= app(Users::class)->findOrFail($this->route('user'));
    }

    /**
     * The same user, found among deleted ones too: for restoring and purging.
     */
    protected function trashedUser(): Model
    {
        return $this->trashed ??= app(Users::class)->findWithTrashedOrFail($this->route('user'));
    }
}
