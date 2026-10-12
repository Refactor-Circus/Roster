<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Mcp\Request;
use RefactorCircus\Roster\Support\Users;

abstract class UserMcpRequest extends Request
{
    private ?Model $target = null;

    private ?Model $trashed = null;

    /**
     * The user named by the `user` argument, found by the user model's route key.
     */
    protected function targetUser(): Model
    {
        return $this->target ??= app(Users::class)->findOrFail($this->get('user'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function userRules(): array
    {
        return ['user' => ['required']];
    }

    /**
     * Every user tool names its user.
     */
    protected function rules(): array
    {
        return $this->userRules();
    }

    /**
     * The same user, found among deleted ones too: for restoring and purging.
     */
    protected function trashedUser(): Model
    {
        return $this->trashed ??= app(Users::class)->findWithTrashedOrFail($this->get('user'));
    }
}
