<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Scim\Exceptions\ScimException;
use RefactorCircus\Roster\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Roster\Domains\Scim\Models\ScimUserModel;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Actions\DeleteTeamAction;
use RefactorCircus\Roster\Domains\Team\Actions\RemoveTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\UpdateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\Team\Data\UpdateTeamData;
use RefactorCircus\Roster\Domains\Team\Models\TeamMemberModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Tables;

/**
 * SCIM Groups for one organization: its teams, on top of Roster's Actions.
 */
final class ScimGroups
{
    public function __construct(private readonly ScimContext $context) {}

    /**
     * @return array{0: array<int, ScimGroupModel>, 1: int}
     */
    public function list(?string $filter, int $startIndex, int $count): array
    {
        $query = ScimGroupModel::query()->where('organization_id', $this->organization()->getKey())->with('team');

        if ($filter !== null && trim($filter) !== '') {
            foreach (FilterParser::parse($filter, ['displayname', 'externalid', 'id', 'members.value']) as $condition) {
                $value = (string) $condition['value'];

                match ($condition['attribute']) {
                    'displayname' => $query->whereHas('team', fn (Builder $team): Builder => ScimQuery::compare($team, 'name', $condition['operator'], $value)),
                    'externalid' => ScimQuery::compare($query, 'external_id', $condition['operator'], $value),
                    'id' => $query->where('id', $value),
                    default => $query->whereIn('team_id', $this->teamsOfMember($value)),
                };
            }
        }

        $total = (clone $query)->count();
        $page = $query->orderBy('created_at')->orderBy('id')->skip(max(0, $startIndex - 1))->take($count)->get()->all();

        $this->preloadMembers($page);

        return [$page, $total];
    }

    /**
     * Resolve the members of a page of groups in two queries, so mapping each
     * one adds none.
     *
     * @param  array<int, ScimGroupModel>  $groups
     */
    private function preloadMembers(array $groups): void
    {
        $teamIds = array_values(array_filter(array_map(fn (ScimGroupModel $group): string => (string) $group->team_id, $groups)));

        if ($teamIds === []) {
            return;
        }

        $memberships = Tables::name('memberships');
        $seatsTable = Tables::name('team_members');

        $seats = TeamMemberModel::query()
            ->join($memberships, $memberships.'.id', '=', $seatsTable.'.membership_id')
            ->whereIn($seatsTable.'.team_id', $teamIds)
            ->get([$seatsTable.'.team_id', $memberships.'.user_id']);

        $scimUsers = ScimUserModel::query()
            ->where('organization_id', $this->organization()->getKey())
            ->whereIn('user_id', $seats->pluck('user_id')->unique()->all())
            ->with('user')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (ScimUserModel $scimUser): string => (string) $scimUser->user_id);

        $seatsByTeam = $seats->groupBy(fn (TeamMemberModel $seat): string => (string) $seat->team_id);

        foreach ($groups as $group) {
            $userIds = ($seatsByTeam->get((string) $group->team_id) ?? collect())->pluck('user_id')->map(fn (mixed $id): string => (string) $id)->all();

            $this->context->members[$group->id] = collect($userIds)
                ->flatMap(fn (string $userId): array => $scimUsers->get($userId)?->all() ?? [])
                ->sortBy('id')
                ->values()
                ->all();
        }
    }

    public function find(string $id): ScimGroupModel
    {
        return ScimGroupModel::query()
            ->where('organization_id', $this->organization()->getKey())
            ->whereKey($id)
            ->with('team')
            ->first() ?? throw ScimException::notFound('Group');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ScimGroupModel
    {
        $name = $this->displayName($data);
        $organization = $this->organization();
        $externalId = is_scalar($data['externalId'] ?? null) ? (string) $data['externalId'] : null;

        if ($organization->teams()->where('name', $name)->whereIn('id', ScimGroupModel::query()->where('organization_id', $organization->getKey())->select('team_id'))->exists()) {
            throw ScimException::uniqueness("A group named [{$name}] already exists.");
        }

        return DB::transaction(function () use ($data, $name, $organization, $externalId): ScimGroupModel {
            $team = app(CreateTeamAction::class)->execute($organization, new CreateTeamData(name: $name));

            $group = ScimGroupModel::query()->create([
                'organization_id' => $organization->getKey(),
                'team_id' => $team->getKey(),
                'external_id' => $externalId,
            ]);

            $this->syncMembers($team, $this->memberIds($data));

            return $group->refresh()->load('team');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function replace(ScimGroupModel $group, array $data, bool $members = true): ScimGroupModel
    {
        $team = $group->team ?? throw ScimException::notFound('Group');
        $name = $this->displayName($data);

        return DB::transaction(function () use ($group, $team, $data, $name, $members): ScimGroupModel {
            if ($team->name !== $name) {
                app(UpdateTeamAction::class)->execute($team, new UpdateTeamData(name: $name));
            }

            if (array_key_exists('externalId', $data)) {
                $group->update(['external_id' => is_scalar($data['externalId']) ? (string) $data['externalId'] : null]);
            }

            if ($members) {
                $this->syncMembers($team, $this->memberIds($data));
            }

            $group->touch();

            return $group->refresh()->load('team');
        });
    }

    /**
     * @param  array<int, mixed>  $operations
     */
    public function patch(ScimGroupModel $group, array $operations): ScimGroupModel
    {
        $view = app(ScimMapper::class)->group($group);

        return $this->replace($group, PatchApplier::apply($view, $operations));
    }

    public function delete(ScimGroupModel $group): void
    {
        $team = $group->team;

        DB::transaction(function () use ($group, $team): void {
            $group->delete();

            if ($team instanceof TeamModel) {
                app(DeleteTeamAction::class)->execute($team);
            }
        });
    }

    /**
     * Make the team's seats match exactly the given SCIM users.
     *
     * @param  array<int, string>  $scimUserIds
     */
    private function syncMembers(TeamModel $team, array $scimUserIds): void
    {
        $wanted = ScimUserModel::query()
            ->where('organization_id', $this->organization()->getKey())
            ->whereIn('id', $scimUserIds)
            ->with('user')
            ->get();

        if ($wanted->count() !== count(array_unique($scimUserIds))) {
            throw ScimException::invalidValue('Every member must be a user provisioned in this organization.');
        }

        // Loaded once: the seats before any change, and which of them SCIM
        // manages, rather than a query per seat on every group PATCH.
        $seated = $team->memberships()->with('user')->get();
        $current = $seated->map(fn (MembershipModel $membership): string => (string) $membership->user_id)->all();
        $managed = ScimUserModel::query()
            ->where('organization_id', $this->organization()->getKey())
            ->whereIn('user_id', $seated->pluck('user_id')->all())
            ->pluck('user_id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all();
        $wantedUsers = $wanted->filter(fn (ScimUserModel $scimUser): bool => $scimUser->active && $scimUser->user instanceof Model);

        foreach ($wantedUsers as $scimUser) {
            $user = $scimUser->user;

            if ($user instanceof Model && ! in_array((string) $scimUser->user_id, $current, true)) {
                $this->attempt(fn () => app(AddTeamMemberAction::class)->execute($team, new AddTeamMemberData(user: $user)));
            }
        }

        $keep = $wantedUsers->map(fn (ScimUserModel $scimUser): string => (string) $scimUser->user_id)->all();

        foreach ($seated as $membership) {
            // Only seats SCIM can see are SCIM's to remove.
            $userId = (string) $membership->user_id;

            if (in_array($userId, $managed, true) && ! in_array($userId, $keep, true) && $membership->user instanceof Model) {
                $this->attempt(fn () => app(RemoveTeamMemberAction::class)->execute($team, $membership->user));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private function memberIds(array $data): array
    {
        return array_values(array_filter(array_map(
            fn (mixed $member): ?string => is_array($member) && is_scalar($member['value'] ?? null) ? (string) $member['value'] : null,
            (array) ($data['members'] ?? []),
        )));
    }

    /**
     * @return array<int, string>
     */
    private function teamsOfMember(string $scimUserId): array
    {
        $scimUser = ScimUserModel::query()->where('organization_id', $this->organization()->getKey())->whereKey($scimUserId)->first();
        $membership = $scimUser?->user instanceof Model ? $this->organization()->membershipFor($scimUser->user) : null;

        return $membership === null ? [] : $membership->teams()->pluck(Tables::name('teams').'.id')->map(fn (mixed $id): string => (string) $id)->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function displayName(array $data): string
    {
        $name = $data['displayName'] ?? null;

        if (! is_string($name) || trim($name) === '') {
            throw ScimException::invalidValue('displayName is required.');
        }

        return trim($name);
    }

    private function attempt(callable $callback): void
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            throw ScimException::invalidValue((string) collect($exception->errors())->flatten()->first());
        }
    }

    private function organization(): OrganizationModel
    {
        return $this->context->organization();
    }
}
