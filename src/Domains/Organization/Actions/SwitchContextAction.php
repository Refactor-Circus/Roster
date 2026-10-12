<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Data\SwitchContextData;
use RefactorCircus\Roster\Domains\Organization\Events\ContextSwitchedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\ContextSwitchingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Users;

final class SwitchContextAction
{
    public function __construct(private readonly Users $users) {}

    /**
     * Make an organization (by slug), and optionally one of its teams, the
     * user's current context. The user must belong to both.
     */
    public function execute(Model $user, SwitchContextData $data): Model
    {
        $organization = OrganizationModel::query()->where('slug', $data->organization)->first();

        if ($organization === null || $organization->membershipFor($user) === null) {
            throw ValidationException::withMessages(['organization' => __('roster::roster.not_a_member')]);
        }

        $team = null;

        if ($data->team !== null && $data->team !== '') {
            $team = TeamModel::query()->where('organization_id', $organization->getKey())->where('slug', $data->team)->first();

            if ($team === null || ! $team->hasMember($user)) {
                throw ValidationException::withMessages(['team' => __('roster::roster.not_on_team')]);
            }
        }

        ContextSwitchingActionEvent::dispatch($user, $data);

        DB::transaction(fn () => $this->users->profile($user)->update([
            'current_organization_id' => $organization->getKey(),
            'current_team_id' => $team?->getKey(),
        ]));

        $user = $user->load('rosterProfile');

        ContextSwitchedActionEvent::dispatch($user);

        return $user;
    }
}
