<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Actions;

use RefactorCircus\Roster\Domains\Invitation\Data\LookUpInvitationData;
use RefactorCircus\Roster\Domains\Invitation\Events\InvitationLookedUpActionEvent;
use RefactorCircus\Roster\Domains\Invitation\Events\InvitationLookingUpActionEvent;
use RefactorCircus\Roster\Domains\Invitation\Exceptions\InvalidInvitationException;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Invitation\Services\InvitationTokens;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

final class LookUpInvitationAction
{
    /**
     * The invitation a token names, for the page a guest opens from the
     * invitation email: pending, unexpired and in an organization that is not
     * deleted, with its organization and its teams (by name) loaded.
     *
     * Every other token - unknown, expired, revoked, accepted, declined, or
     * for a deleted organization - is refused with the same exception, so a
     * caller cannot tell whether a token ever existed. The token is matched
     * by its hash only, and neither event carries it.
     *
     * @throws InvalidInvitationException
     */
    public function execute(LookUpInvitationData $data): InvitationModel
    {
        InvitationLookingUpActionEvent::dispatch();

        $invitation = InvitationModel::query()
            ->where('token_hash', InvitationTokens::hash($data->token))
            ->pending()
            ->whereHas('organization')
            ->with('organization')
            ->first() ?? throw InvalidInvitationException::forToken();

        $invitation->setRelation('teamModels', TeamModel::query()
            ->where('organization_id', $invitation->organization_id)
            ->whereIn('id', (array) $invitation->teams)
            ->orderBy('name')
            ->get(['id', 'name']));

        InvitationLookedUpActionEvent::dispatch($invitation);

        return $invitation;
    }
}
