<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Services\Planners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Actions\UpdateProfileAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Domains\User\Data\UpdateProfileData;
use RefactorCircus\Roster\Support\Users;

/**
 * Plain accounts: `email, name, display_name`. Existing emails are skipped;
 * passwords are never imported.
 */
final class UsersPlanner extends Planner
{
    /**
     * The preview's current batch: each valid email, and whether an account
     * has it. Null outside a preview.
     *
     * @var array<string, bool>|null
     */
    private ?array $batch = null;

    public function __construct(private readonly Users $users) {}

    /**
     * Which of the batch's emails have accounts, in one query.
     */
    public function prefetch(array $rows, TransferModel $transfer): void
    {
        $emails = array_values(array_filter(array_map(fn (array $values): string => strtolower($values['email'] ?? ''), $rows), fn (string $email): bool => $email !== ''));
        $found = $this->users->findManyByEmail($emails);

        $this->batch = [];

        foreach ($emails as $email) {
            $this->batch[$email] = isset($found[$email]);
        }
    }

    public function plan(array $values, TransferModel $transfer, ?Model $actor): array
    {
        $email = strtolower($values['email'] ?? '');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->outcome(self::ERROR, __('roster::roster.import_invalid_email'));
        }

        return $this->exists($email)
            ? $this->outcome(self::SKIP, __('roster::roster.import_account_exists'))
            : $this->outcome(self::CREATE, __('roster::roster.import_new_account'));
    }

    public function apply(array $values, TransferModel $transfer, ?Model $actor): array
    {
        $this->batch = null;
        $plan = $this->plan($values, $transfer, $actor);

        if ($plan['action'] !== self::CREATE) {
            return $plan;
        }

        $email = strtolower($values['email']);

        $user = app(CreateUserAction::class)->execute(new CreateUserData(
            email: $email,
            name: ($values['name'] ?? '') !== '' ? $values['name'] : Str::before($email, '@'),
        ));

        if (($values['display_name'] ?? '') !== '') {
            app(UpdateProfileAction::class)->execute($user, new UpdateProfileData(displayName: $values['display_name']));
        }

        return $plan;
    }

    private function exists(string $email): bool
    {
        return $this->batch[$email] ?? $this->users->findByEmail($email) !== null;
    }
}
