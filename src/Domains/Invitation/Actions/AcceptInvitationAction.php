<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\Invitation\Concerns\ResolvesInvitations;
use RefactorCircus\Roster\Domains\Invitation\Data\AcceptInvitationData;
use RefactorCircus\Roster\Domains\Invitation\Events\InvitationAcceptedActionEvent;
use RefactorCircus\Roster\Domains\Invitation\Events\InvitationAcceptingActionEvent;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Organization\Concerns\ManagesMemberships;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Users;

final class AcceptInvitationAction
{
    use ManagesMemberships;
    use ResolvesInvitations;

    public function __construct(private readonly Users $users) {}

    /**
     * Accept an invitation as `$user`, whose email must match it. Joins the
     * organization and every invited team that still exists, and makes the
     * organization current when the user has none yet.
     */
    public function execute(AcceptInvitationData $data, Model $user): InvitationModel
    {
        $invitation = $this->pendingInvitationFor($data->token, $user);

        InvitationAcceptingActionEvent::dispatch($invitation, $user);

        DB::transaction(function () use ($invitation, $user): void {
            /** @var OrganizationModel $organization */
            $organization = $invitation->organization;

            $membership = $this->join($organization, $user, MembershipSource::Invitation);

            $teams = TeamModel::query()
                ->where('organization_id', $organization->getKey())
                ->whereIn('id', (array) $invitation->teams)
                ->get();

            foreach ($teams as $team) {
                $this->seat($team, $membership);
            }

            $profile = $this->users->profile($user);

            if ($profile->current_organization_id === null) {
                $profile->update(['current_organization_id' => $organization->getKey()]);
            }

            $invitation->update(['accepted_at' => now()]);
        });

        $invitation = $invitation->refresh()->load('organization');

        InvitationAcceptedActionEvent::dispatch($invitation, $user);

        return $invitation;
    }
}
