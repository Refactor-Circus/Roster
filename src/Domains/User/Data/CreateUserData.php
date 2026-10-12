<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Roster\Domains\User\Concerns\ProfileRules;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Support\Users;

/**
 * Input for CreateUserAction: the account, and optionally its profile.
 *
 * `name` is required unless `roster.users.columns.name` is null. Without a
 * password a random one is set. `status` Pending makes the account wait for
 * an approver.
 */
final readonly class CreateUserData extends Data
{
    use ProfileRules;

    /**
     * @param  array<string, mixed>|Missing|null  $meta
     */
    public function __construct(
        public string $email,
        public ?string $name = null,
        public ?string $password = null,
        public UserStatus $status = UserStatus::Active,
        public string|Missing|null $displayName = new Missing,
        public string|Missing|null $avatarUrl = new Missing,
        public string|Missing|null $timezone = new Missing,
        public string|Missing|null $locale = new Missing,
        public string|Missing|null $bio = new Missing,
        public array|Missing|null $meta = new Missing,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        $users = app(Users::class);
        $email = $users->column('email') ?? 'email';

        $rules = [
            'email' => ['required', 'email', 'max:255', Rule::unique($users->table(), $email)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'max:255'],
            // `pending` makes the account wait for an approver.
            'status' => ['sometimes', Rule::in([UserStatus::Active->value, UserStatus::Pending->value])],
        ];

        if ($users->column('name') !== null) {
            $rules = ['name' => ['required', 'string', 'max:255']] + $rules;
        }

        return $rules + self::profileRules();
    }
}
