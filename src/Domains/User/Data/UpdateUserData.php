<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Roster\Support\Users;

/**
 * Input for UpdateUserAction: the account fields to change. Fields left out
 * are kept.
 */
final readonly class UpdateUserData extends Data
{
    public function __construct(
        public string|Missing $name = new Missing,
        public string|Missing $email = new Missing,
        public string|Missing $password = new Missing,
    ) {}

    /**
     * Pass the user being updated so its own email passes the unique check.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?Model $user = null): array
    {
        $users = app(Users::class);
        $unique = Rule::unique($users->table(), $users->column('email') ?? 'email');

        if ($user !== null) {
            $unique->ignore($user->getKey(), $user->getKeyName());
        }

        $rules = [
            'email' => ['sometimes', 'required', 'email', 'max:255', $unique],
            'password' => ['sometimes', 'required', 'string', 'min:8', 'max:255'],
        ];

        if ($users->column('name') !== null) {
            $rules = ['name' => ['sometimes', 'required', 'string', 'max:255']] + $rules;
        }

        return $rules;
    }
}
