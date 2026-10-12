<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\User\Concerns\ProfileRules;
use RefactorCircus\Roster\Domains\User\Data\UpdateProfileData;
use RefactorCircus\Roster\Domains\User\Events\ProfileUpdatedActionEvent;
use RefactorCircus\Roster\Domains\User\Events\ProfileUpdatingActionEvent;
use RefactorCircus\Roster\Support\Users;

final class UpdateProfileAction
{
    use ProfileRules;

    public function __construct(private readonly Users $users) {}

    public function execute(Model $user, UpdateProfileData $data): Model
    {
        ProfileUpdatingActionEvent::dispatch($user, $data);

        DB::transaction(function () use ($user, $data): void {
            $this->users->profile($user)->update(self::profileAttributes($data));
        });

        $user = $user->load('rosterProfile');

        ProfileUpdatedActionEvent::dispatch($user);

        return $user;
    }
}
