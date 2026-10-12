<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\User\Events\UserRestoredActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UserRestoringActionEvent;
use RefactorCircus\Roster\Support\Users;

final class RestoreUserAction
{
    public function __construct(private readonly Users $users) {}

    /**
     * Bring back a deleted user, with the profile, memberships and roles they
     * kept while deleted.
     */
    public function execute(Model $user): Model
    {
        if (! $this->users->trashed($user)) {
            throw ValidationException::withMessages(['user' => __('roster::roster.not_deleted')]);
        }

        UserRestoringActionEvent::dispatch($user);

        DB::transaction(function () use ($user): void {
            if (method_exists($user, 'restore')) {
                $user->restore();
            }

            // Their personal organization comes back with them.
            OrganizationModel::onlyTrashed()->where('owner_id', $user->getKey())->where('personal', true)->get()->each(fn (OrganizationModel $organization): bool => $organization->restore());
        });

        $user = $user->load('rosterProfile');

        UserRestoredActionEvent::dispatch($user);

        return $user;
    }
}
