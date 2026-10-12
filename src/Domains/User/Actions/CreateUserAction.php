<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RefactorCircus\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\JoinOrganizationsByDomainAction;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;
use RefactorCircus\Roster\Domains\User\Concerns\ProfileRules;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Domains\User\Events\UserCreatedActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UserCreatingActionEvent;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;
use RefactorCircus\Roster\Domains\User\Services\Approvals;
use RefactorCircus\Roster\Support\Users;

final class CreateUserAction
{
    use ProfileRules;

    public function __construct(private readonly Users $users) {}

    /**
     * Create a user and its profile together.
     *
     * Without a password a random one is set, so the account exists but can
     * only be signed into after a reset. With `roster.organizations.personal`
     * on, the user also gets a personal organization; a user created already
     * verified joins any auto-join organizations owning their domain.
     */
    public function execute(CreateUserData $data): Model
    {
        UserCreatingActionEvent::dispatch($data);

        $user = DB::transaction(function () use ($data): Model {
            $user = $this->users->newModel();

            foreach (['name' => $data->name, 'email' => $data->email] as $field => $value) {
                $column = $this->users->column($field);

                if ($column !== null && $value !== null) {
                    $user->setAttribute($column, $value);
                }
            }

            $password = $this->users->column('password');

            if ($password !== null) {
                $user->setAttribute($password, $data->password ?? Str::password(32));
            }

            $user->save();

            $pending = $data->status === UserStatus::Pending;
            $profile = ProfileModel::query()->create(['user_id' => $user->getKey()] + self::profileAttributes($data) + ($pending ? ['status' => UserStatus::Pending, 'status_changed_at' => now()] : []));

            if (config('roster.organizations.personal') === true) {
                $name = $this->users->name($user) ?? $this->users->email($user) ?? 'Personal';

                app(CreateOrganizationAction::class)->execute(new CreateOrganizationData(name: $name), $user, personal: true);
            }

            app(JoinOrganizationsByDomainAction::class)->execute($user);

            return $user->setRelation('rosterProfile', $profile->refresh());
        });

        UserCreatedActionEvent::dispatch($user);

        if ($this->users->status($user) === UserStatus::Pending) {
            app(Approvals::class)->pending($user);
        }

        return $user;
    }
}
