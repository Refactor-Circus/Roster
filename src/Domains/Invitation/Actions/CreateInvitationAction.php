<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Invitation\Data\CreateInvitationData;
use RefactorCircus\Roster\Domains\Invitation\Events\InvitationCreatedActionEvent;
use RefactorCircus\Roster\Domains\Invitation\Events\InvitationCreatingActionEvent;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Invitation\Notifications\InvitationNotification;
use RefactorCircus\Roster\Domains\Invitation\Services\InvitationTokens;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Users;

final class CreateInvitationAction
{
    public function __construct(private readonly Users $users) {}

    /**
     * Invite an email address into the organization, optionally onto some
     * of its teams (by slug), and email the link.
     */
    public function execute(OrganizationModel $organization, CreateInvitationData $data, ?Model $actor = null): InvitationModel
    {
        $email = strtolower($data->email);
        $teams = $this->teamIds($organization, $data->teams);

        $this->guard($organization, $email);

        InvitationCreatingActionEvent::dispatch($organization, $data);

        $token = InvitationTokens::generate();

        $invitation = DB::transaction(fn (): InvitationModel => InvitationModel::query()->create([
            'organization_id' => $organization->getKey(),
            'email' => $email,
            'token_hash' => InvitationTokens::hash($token),
            'teams' => $teams,
            'invited_by' => $actor?->getKey(),
            'expires_at' => now()->addDays((int) config('roster.invitations.expires_after_days', 7)),
        ]));

        $invitation->load('organization');

        Notification::route('mail', $email)->notify(new InvitationNotification($invitation, $token));

        InvitationCreatedActionEvent::dispatch($invitation);

        return $invitation;
    }

    private function guard(OrganizationModel $organization, string $email): void
    {
        $existing = $this->users->findByEmail($email);

        if ($existing !== null && $organization->membershipFor($existing) !== null) {
            throw ValidationException::withMessages(['email' => __('roster::roster.already_a_member')]);
        }

        if ($organization->invitations()->pending()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => __('roster::roster.invitation_already_pending')]);
        }
    }

    /**
     * @param  array<int, string>  $slugs
     * @return array<int, string>
     */
    private function teamIds(OrganizationModel $organization, array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        $teams = $organization->teams()->whereIn('slug', $slugs)->pluck('id', 'slug');

        if ($teams->count() !== count($slugs)) {
            throw ValidationException::withMessages(['teams' => __('roster::roster.teams_not_in_organization')]);
        }

        return array_values(array_map('strval', $teams->all()));
    }
}
