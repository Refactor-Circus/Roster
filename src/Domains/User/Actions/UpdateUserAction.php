<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Roster\Domains\Impersonation\Services\ImpersonationContext;
use RefactorCircus\Roster\Domains\User\Data\UpdateUserData;
use RefactorCircus\Roster\Domains\User\Events\UserUpdatedActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UserUpdatingActionEvent;
use RefactorCircus\Roster\Support\Users;

final class UpdateUserAction
{
    public function __construct(private readonly Users $users) {}

    public function execute(Model $user, UpdateUserData $data): Model
    {
        // An impersonator must not be able to take over the account.
        $impersonated = app(ImpersonationContext::class)->active()?->user_id;

        if ($impersonated !== null && (string) $impersonated === (string) $user->getKey()) {
            foreach (['email', 'password'] as $field) {
                if ($data->has($field)) {
                    throw ValidationException::withMessages([$field => __('roster::roster.impersonation_account_locked')]);
                }
            }
        }

        UserUpdatingActionEvent::dispatch($user, $data);

        DB::transaction(function () use ($user, $data): void {
            foreach (['name' => $data->name, 'email' => $data->email, 'password' => $data->password] as $field => $value) {
                $column = $this->users->column($field);

                if ($column !== null && ! $value instanceof Missing) {
                    $user->setAttribute($column, $value);
                }
            }

            $user->save();
        });

        $user = $user->refresh()->load('rosterProfile');

        UserUpdatedActionEvent::dispatch($user);

        return $user;
    }
}
