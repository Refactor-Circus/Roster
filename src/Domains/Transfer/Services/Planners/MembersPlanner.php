<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Services\Planners;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use RefactorCircus\Keystone\Authorization\Contracts\Roles;
use RefactorCircus\Roster\Domains\Invitation\Actions\CreateInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Data\CreateInvitationData;
use RefactorCircus\Roster\Domains\Organization\Concerns\ManagesMemberships;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Events\MemberAddedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\MemberAddingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Actions\UpdateProfileAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Domains\User\Data\UpdateProfileData;
use RefactorCircus\Roster\Support\Authorization\Authorizer;
use RefactorCircus\Roster\Support\Users;
use RuntimeException;

/**
 * Members into an organization: `email, name, display_name, teams, role`.
 *
 * Accounts on the organization's domains are linked or created and join;
 * anyone else is invited. Teams and a role are added, never removed; roles
 * are the bound role store's, so without one a role value is a row error.
 */
final class MembersPlanner extends Planner
{
    /**
     * What the last `plan()` looked up about the row's person, for `apply()`.
     *
     * @var array{user: Model|null, member: bool, newTeams: array<int, TeamModel>, newRole: bool}|null
     */
    private ?array $facts = null;

    /**
     * What prefetch() looked up for the preview's current batch, by
     * lowercased email and user key; null outside a preview.
     *
     * @var array{emails: array<string, true>, users: array<string, Model>, memberships: array<string, MembershipModel>, invited: array<string, true>}|null
     */
    private ?array $batch = null;

    use ManagesMemberships;

    public function __construct(
        private readonly Users $users,
        private readonly Roles $roles,
    ) {}

    /**
     * The batch's accounts, their memberships with seats and the pending
     * invitations: four queries for up to 500 rows.
     */
    public function prefetch(array $rows, TransferModel $transfer): void
    {
        $organization = $this->organization($transfer);
        $emails = array_values(array_unique(array_filter(
            array_map(fn (array $values): string => strtolower($values['email'] ?? ''), $rows),
            fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
        )));

        $users = $this->users->findManyByEmail($emails);
        $keys = array_values(array_map(fn (Model $user): mixed => $user->getKey(), $users));
        $memberships = [];

        if ($keys !== []) {
            foreach ($organization->memberships()->whereIn('user_id', $keys)->with('teams')->get() as $membership) {
                $memberships[(string) $membership->user_id] = $membership;
            }
        }

        $invited = $emails === [] ? [] : $organization->invitations()->pending()->whereIn('email', $emails)->pluck('email')
            ->map(fn (mixed $email): string => strtolower((string) $email))
            ->all();

        $this->batch = [
            'emails' => array_fill_keys($emails, true),
            'users' => $users,
            'memberships' => $memberships,
            'invited' => array_fill_keys($invited, true),
        ];
    }

    public function plan(array $values, TransferModel $transfer, ?Model $actor): array
    {
        $organization = $this->organization($transfer);
        $email = strtolower($values['email'] ?? '');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->outcome(self::ERROR, __('roster::roster.import_invalid_email'));
        }

        [$teams, $unknownTeams] = $this->teams($transfer, $values['teams'] ?? '');

        if ($unknownTeams !== []) {
            return $this->outcome(self::ERROR, __('roster::roster.import_unknown_teams', ['teams' => implode(', ', $unknownTeams)]));
        }

        $role = null;

        if (($values['role'] ?? '') !== '') {
            $role = $values['role'];

            if (! $this->roleExists($transfer, $role)) {
                return $this->outcome(self::ERROR, __('roster::roster.import_unknown_role', ['role' => $role]));
            }

            if (! $this->mayGrant($transfer, $actor, $role)) {
                return $this->outcome(self::ERROR, __('roster::roster.import_role_not_grantable', ['role' => $role]));
            }
        }

        $batch = $this->batch !== null && isset($this->batch['emails'][$email]) ? $this->batch : null;
        $user = $batch !== null ? ($batch['users'][$email] ?? null) : $this->users->findByEmail($email);
        $this->facts = ['user' => $user, 'member' => false, 'newTeams' => $teams, 'newRole' => $role !== null];
        $membership = match (true) {
            $user === null => null,
            $batch !== null => $batch['memberships'][(string) $user->getKey()] ?? null,
            default => $organization->membershipFor($user),
        };

        if ($user !== null && $membership !== null) {
            $newTeams = array_values(array_filter($teams, fn (TeamModel $team): bool => $batch !== null
                ? ! $membership->teams->contains($team)
                : ! $team->hasMember($user)));
            $newRole = $role !== null && ! $this->holds($user, $role, $organization);
            $this->facts = ['user' => $user, 'member' => true, 'newTeams' => $newTeams, 'newRole' => $newRole];

            return $newTeams === [] && ! $newRole
                ? $this->outcome(self::SKIP, __('roster::roster.import_already_member'))
                : $this->outcome(self::UPDATE, __('roster::roster.import_adds_teams_or_role'));
        }

        if ($this->owned($transfer, $email)) {
            return $user !== null
                ? $this->outcome(self::LINK, __('roster::roster.import_existing_account'))
                : $this->outcome(self::CREATE, __('roster::roster.import_new_account'));
        }

        if (! $this->remember($transfer, 'may-invite', fn (): bool => app(Authorizer::class)->check($actor, 'roster.invitations.manage', $organization))) {
            return $this->outcome(self::ERROR, __('roster::roster.import_cannot_invite'));
        }

        if ($batch !== null ? isset($batch['invited'][$email]) : $organization->invitations()->pending()->where('email', $email)->exists()) {
            return $this->outcome(self::SKIP, __('roster::roster.invitation_already_pending'));
        }

        return $this->outcome(self::INVITE, __('roster::roster.import_outside_domains'));
    }

    /**
     * Applies what `plan()` found, reusing what it looked up (the user, their
     * membership, seats and role) rather than querying for it again.
     */
    public function apply(array $values, TransferModel $transfer, ?Model $actor): array
    {
        $this->facts = null;
        $this->batch = null;
        $plan = $this->plan($values, $transfer, $actor);
        $organization = $this->organization($transfer);
        $email = strtolower($values['email'] ?? '');
        [$teams] = $this->teams($transfer, $values['teams'] ?? '');
        $role = ($values['role'] ?? '') !== '' ? $values['role'] : null;
        $facts = $this->lookedUp();

        if (in_array($plan['action'], [self::ERROR, self::SKIP], true) || $facts === null) {
            return $plan;
        }

        if ($plan['action'] === self::INVITE) {
            app(CreateInvitationAction::class)->execute($organization, new CreateInvitationData(
                email: $email,
                teams: array_map(fn (TeamModel $team): string => $team->slug, $teams),
            ), $actor);

            return $plan;
        }

        $user = $facts['user'];

        if ($user === null) {
            $user = app(CreateUserAction::class)->execute(new CreateUserData(
                email: $email,
                name: ($values['name'] ?? '') !== '' ? $values['name'] : Str::before($email, '@'),
            ));
        }

        if (($values['display_name'] ?? '') !== '') {
            app(UpdateProfileAction::class)->execute($user, new UpdateProfileData(displayName: $values['display_name']));
        }

        // AddMemberAction would record the membership as direct; join with
        // the import source but announce it the same way.
        if (! $facts['member']) {
            MemberAddingActionEvent::dispatch($organization, $user);
            $this->join($organization, $user, MembershipSource::Import);
            MemberAddedActionEvent::dispatch($organization, $user);
        }

        // A new member holds no seat or organization role yet.
        foreach ($facts['newTeams'] as $team) {
            app(AddTeamMemberAction::class)->execute($team, new AddTeamMemberData(user: $user));
        }

        if ($role !== null && $facts['newRole'] && $user instanceof Authenticatable) {
            $this->roles->assign($user, $role, $organization);
            app(Permissions::class)->forget($user);
        }

        return $plan;
    }

    /**
     * @return array{user: Model|null, member: bool, newTeams: array<int, TeamModel>, newRole: bool}|null
     */
    private function lookedUp(): ?array
    {
        return $this->facts;
    }

    private function organization(TransferModel $transfer): OrganizationModel
    {
        return $this->remember($transfer, 'organization', fn (): ?OrganizationModel => $transfer->organization)
            ?? throw new RuntimeException('A members import needs an organization.');
    }

    /**
     * Teams named by slug or name, from the organization's teams loaded once.
     *
     * @return array{0: array<int, TeamModel>, 1: array<int, string>}
     */
    private function teams(TransferModel $transfer, string $value): array
    {
        $teams = $this->remember($transfer, 'teams', fn (): Collection => $this->organization($transfer)->teams()->get());
        $found = [];
        $unknown = [];

        foreach ($this->list($value) as $name) {
            $team = $teams->first(fn (TeamModel $team): bool => $team->slug === $name)
                ?? $teams->first(fn (TeamModel $team): bool => strcasecmp($team->name, $name) === 0);

            $team === null ? $unknown[] = $name : $found[] = $team;
        }

        return [$found, $unknown];
    }

    /**
     * Whether the bound role store has the organization role, asked once per
     * slug and import.
     */
    private function roleExists(TransferModel $transfer, string $slug): bool
    {
        return $this->remember($transfer, 'role:'.$slug, fn (): bool => $this->roles->exists($slug, $this->organization($transfer)));
    }

    /**
     * Who may hand out a role in an import. The role store does not say what a
     * role grants, so "nobody hands out more than they hold" is kept with the
     * contract alone (D-033): a super-admin, the organization's owner, or an
     * actor who already holds that same role in the organization. Imports
     * without an actor or with authorization off allow any role, as before.
     */
    private function mayGrant(TransferModel $transfer, ?Model $actor, string $role): bool
    {
        if ($actor === null || ! app(Authorizer::class)->enabled()) {
            return true;
        }

        if (! $actor instanceof Authenticatable) {
            return false;
        }

        $organization = $this->organization($transfer);

        return $this->remember($transfer, 'actor-super-admin', fn (): bool => app(Permissions::class)->isSuperAdmin($actor))
            || $organization->isOwnedBy($actor)
            || in_array($role, $this->remember($transfer, 'actor-roles', fn (): array => $this->roles->of($actor, $organization)), true);
    }

    /**
     * Whether the user already holds the role in the organization itself.
     */
    private function holds(Model $user, string $role, OrganizationModel $organization): bool
    {
        return ! $user instanceof Authenticatable || in_array($role, $this->roles->of($user, $organization), true);
    }

    private function owned(TransferModel $transfer, string $email): bool
    {
        $domains = $this->remember($transfer, 'domains', fn (): array => array_map('strtolower', $this->organization($transfer)->domains()->pluck('domain')->all()));

        return in_array(strtolower(Str::after($email, '@')), $domains, true);
    }
}
