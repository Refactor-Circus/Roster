<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Services\Planners;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RefactorCircus\Roster\Domains\Invitation\Actions\CreateInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Data\CreateInvitationData;
use RefactorCircus\Roster\Domains\Organization\Concerns\ManagesMemberships;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Events\MemberAddedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\MemberAddingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Actions\AssignRoleAction;
use RefactorCircus\Roster\Domains\Role\Data\AssignRoleData;
use RefactorCircus\Roster\Domains\Role\Enums\RoleScope;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Actions\UpdateProfileAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Domains\User\Data\UpdateProfileData;
use RefactorCircus\Roster\Support\Users;
use RuntimeException;

/**
 * Members into an organization: `email, name, display_name, teams, role`.
 *
 * Accounts on the organization's domains are linked or created and join;
 * anyone else is invited. Teams and a role are added, never removed.
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
     * @var array{emails: array<string, true>, users: array<string, Model>, memberships: array<string, MembershipModel>, roles: array<string, array<int, string>>, invited: array<string, true>}|null
     */
    private ?array $batch = null;

    use ManagesMemberships;

    public function __construct(private readonly Users $users) {}

    /**
     * The batch's accounts, their memberships with seats, their organization
     * roles and the pending invitations: five queries for up to 500 rows.
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
        $roles = [];

        if ($keys !== []) {
            foreach ($organization->memberships()->whereIn('user_id', $keys)->with('teams')->get() as $membership) {
                $memberships[(string) $membership->user_id] = $membership;
            }

            $assignments = RoleAssignmentModel::query()
                ->where('organization_id', $organization->getKey())
                ->whereNull('team_id')
                ->whereIn('user_id', $keys)
                ->get(['user_id', 'role_id']);

            foreach ($assignments as $assignment) {
                $roles[(string) $assignment->user_id][] = (string) $assignment->role_id;
            }
        }

        $invited = $emails === [] ? [] : $organization->invitations()->pending()->whereIn('email', $emails)->pluck('email')
            ->map(fn (mixed $email): string => strtolower((string) $email))
            ->all();

        $this->batch = [
            'emails' => array_fill_keys($emails, true),
            'users' => $users,
            'memberships' => $memberships,
            'roles' => $roles,
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
            $role = $this->role($transfer, $values['role']);

            if ($role === null) {
                return $this->outcome(self::ERROR, __('roster::roster.import_unknown_role', ['role' => $values['role']]));
            }

            if (! $this->mayGrant($transfer, $actor, $role)) {
                return $this->outcome(self::ERROR, __('roster::roster.import_role_not_grantable', ['role' => $role->slug]));
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
            $newRole = $role !== null && ! ($batch !== null
                ? in_array((string) $role->getKey(), $batch['roles'][(string) $user->getKey()] ?? [], true)
                : $this->holds($user, $role, $organization));
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
        $role = ($values['role'] ?? '') !== '' ? $this->role($transfer, $values['role']) : null;
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

        if ($role !== null && $facts['newRole']) {
            app(AssignRoleAction::class)->execute($user, new AssignRoleData(role: (string) $role->id, organization: $organization), $actor);
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
     * An organization role by slug: the organization's own before a shared one.
     */
    private function role(TransferModel $transfer, string $slug): ?RoleModel
    {
        $organization = $this->organization($transfer);

        return $this->remember($transfer, 'roles', fn (): Collection => RoleModel::query()
            ->with('permissions')
            ->where('scope', RoleScope::Organization)
            ->where(fn (Builder $query): Builder => $query->whereNull('organization_id')->orWhere('organization_id', $organization->getKey()))
            ->orderByRaw('organization_id is null')
            ->get())
            ->firstWhere('slug', $slug);
    }

    /**
     * The same rule as assigning by hand: nobody hands out more than they hold.
     */
    private function mayGrant(TransferModel $transfer, ?Model $actor, RoleModel $role): bool
    {
        if ($actor === null || ! app(Authorizer::class)->enabled()) {
            return true;
        }

        $held = $this->remember($transfer, 'actor-permissions', fn (): array => app(Permissions::class)->for($actor, $this->organization($transfer)));

        return ! $role->super
            && array_diff($role->permissions->pluck('name')->all(), $held) === [];
    }

    private function holds(Model $user, RoleModel $role, OrganizationModel $organization): bool
    {
        return RoleAssignmentModel::query()
            ->where('role_id', $role->getKey())
            ->where('user_id', $user->getKey())
            ->where('organization_id', $organization->getKey())
            ->whereNull('team_id')
            ->exists();
    }

    private function owned(TransferModel $transfer, string $email): bool
    {
        $domains = $this->remember($transfer, 'domains', fn (): array => array_map('strtolower', $this->organization($transfer)->domains()->pluck('domain')->all()));

        return in_array(strtolower(Str::after($email, '@')), $domains, true);
    }
}
