<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Concerns\ManagesMemberships;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Events\MemberAddedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\MemberAddingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Users;

final class AddMemberAction
{
    use ManagesMemberships;

    public function __construct(private readonly Users $users) {}

    public function execute(OrganizationModel $organization, AddMemberData $data): MembershipModel
    {
        $user = $this->users->findOrFail($data->user);

        if ($organization->membershipFor($user) !== null) {
            throw ValidationException::withMessages(['user' => __('roster::roster.already_a_member')]);
        }

        MemberAddingActionEvent::dispatch($organization, $user);

        $membership = DB::transaction(fn (): MembershipModel => $this->join($organization, $user, MembershipSource::Direct));

        MemberAddedActionEvent::dispatch($organization, $user);

        return $membership->load(['user', 'teams', 'organization']);
    }
}
