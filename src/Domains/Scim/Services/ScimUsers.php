<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Roster\Domains\Organization\Actions\RemoveMemberAction;
use RefactorCircus\Roster\Domains\Organization\Concerns\ManagesMemberships;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Scim\Exceptions\ScimException;
use RefactorCircus\Roster\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Roster\Domains\Scim\Models\ScimUserModel;
use RefactorCircus\Roster\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Actions\DeactivateUserAction;
use RefactorCircus\Roster\Domains\User\Actions\ReactivateUserAction;
use RefactorCircus\Roster\Domains\User\Actions\UpdateProfileAction;
use RefactorCircus\Roster\Domains\User\Actions\UpdateUserAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Domains\User\Data\DeactivateUserData;
use RefactorCircus\Roster\Domains\User\Data\UpdateProfileData;
use RefactorCircus\Roster\Domains\User\Data\UpdateUserData;
use RefactorCircus\Roster\Roster;
use RefactorCircus\Roster\Support\Statuses;
use RefactorCircus\Roster\Support\Tables;
use RefactorCircus\Roster\Support\Users;

/**
 * SCIM Users for one organization, on top of Roster's Actions.
 *
 * A SCIM User is a member. Deprovisioning removes the membership and, for
 * accounts SCIM created that belong nowhere else, deactivates the account.
 * Nothing is ever deleted.
 */
final class ScimUsers
{
    use ManagesMemberships;

    public function __construct(
        private readonly Users $users,
        private readonly ScimContext $context,
    ) {}

    /**
     * @return array{0: array<int, ScimUserModel>, 1: int}
     */
    public function list(?string $filter, int $startIndex, int $count): array
    {
        $query = ScimUserModel::query()->where('organization_id', $this->organization()->getKey())->with('user.rosterProfile');

        if ($filter !== null && trim($filter) !== '') {
            $email = $this->users->column('email') ?? 'email';

            foreach (FilterParser::parse($filter, ['username', 'externalid', 'emails.value', 'id', 'emails']) as $condition) {
                $value = (string) $condition['value'];
                $like = fn (string $column, Builder $builder): Builder => ScimQuery::compare($builder, $column, $condition['operator'], $value);

                match ($condition['attribute']) {
                    'externalid' => $condition['operator'] === 'eq' ? $query->where('external_id', $value) : $like('external_id', $query),
                    'id' => $query->where('id', $value),
                    default => $query->whereHas('user', fn (Builder $user): Builder => $like($email, $user)),
                };
            }
        }

        $total = (clone $query)->count();
        $page = $query->orderBy('created_at')->orderBy('id')->skip(max(0, $startIndex - 1))->take($count)->get()->all();

        $this->preloadGroups($page);

        return [$page, $total];
    }

    public function find(string $id): ScimUserModel
    {
        return ScimUserModel::query()
            ->where('organization_id', $this->organization()->getKey())
            ->whereKey($id)
            ->with('user')
            ->first() ?? throw ScimException::notFound('User');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ScimUserModel
    {
        $email = $this->email($data);
        $externalId = $this->string($data['externalId'] ?? null);
        $organization = $this->organization();

        if ($externalId !== null && ScimUserModel::query()->where('organization_id', $organization->getKey())->where('external_id', $externalId)->exists()) {
            throw ScimException::uniqueness("A user with externalId [{$externalId}] already exists.");
        }

        $this->guardDomain($email);

        return DB::transaction(function () use ($data, $email, $externalId, $organization): ScimUserModel {
            $user = $this->findByEmail($email);
            $created = false;

            if ($user !== null && ScimUserModel::query()->where('organization_id', $organization->getKey())->where('user_id', $user->getKey())->exists()) {
                throw ScimException::uniqueness("A user with userName [{$email}] already exists.");
            }

            if ($user === null) {
                // The organization decides whether its new accounts need approval.
                $user = app(CreateUserAction::class)->execute(new CreateUserData(email: $email, name: $this->name($data, $email), status: app(Roster::class)->provisionedStatus($organization)));
                $created = true;
            }

            $scimUser = ScimUserModel::query()->create([
                'organization_id' => $organization->getKey(),
                'user_id' => $user->getKey(),
                'external_id' => $externalId,
                'created_by_scim' => $created,
                'active' => true,
            ]);

            $this->join($organization, $user, 'scim');
            $this->profile($user, $data);
            $this->linkIdentity($user, $externalId, $email);

            if (array_key_exists('active', $data) && ! $this->boolean($data['active'])) {
                $this->deprovision($scimUser);
            }

            return $scimUser->refresh()->load('user');
        });
    }

    /**
     * Apply a full SCIM User (PUT) or a patched view to an existing user.
     *
     * @param  array<string, mixed>  $data
     */
    public function replace(ScimUserModel $scimUser, array $data): ScimUserModel
    {
        $user = $scimUser->user ?? throw ScimException::notFound('User');
        $email = $this->email($data);

        return DB::transaction(function () use ($scimUser, $user, $data, $email): ScimUserModel {
            $emailChanged = strcasecmp((string) $this->users->email($user), $email) !== 0;

            if ($emailChanged) {
                $this->guardDomain($email);
            }

            $name = $this->name($data, $email);
            $nameChanged = $this->users->column('name') !== null && $this->users->name($user) !== $name;

            $changes = new UpdateUserData(
                name: $nameChanged ? $name : new Missing,
                email: $emailChanged ? $email : new Missing,
            );

            if ($emailChanged || $nameChanged) {
                try {
                    app(UpdateUserAction::class)->execute($user, $changes);
                } catch (ValidationException $exception) {
                    throw ScimException::uniqueness((string) collect($exception->errors())->flatten()->first());
                }
            }

            $this->profile($user, $data);

            $externalId = $this->string($data['externalId'] ?? null);

            if ($externalId !== $scimUser->external_id) {
                $scimUser->update(['external_id' => $externalId]);
                $this->linkIdentity($user, $externalId, $email);
            }

            $active = array_key_exists('active', $data) ? $this->boolean($data['active']) : $scimUser->active;

            if ($active && ! $scimUser->active) {
                $this->restore($scimUser);
            } elseif (! $active && $scimUser->active) {
                $this->deprovision($scimUser);
            }

            $scimUser->touch();

            return $scimUser->refresh()->load('user');
        });
    }

    /**
     * @param  array<int, mixed>  $operations
     */
    public function patch(ScimUserModel $scimUser, array $operations): ScimUserModel
    {
        $view = app(ScimMapper::class)->user($scimUser);

        return $this->replace($scimUser, PatchApplier::apply($view, $operations));
    }

    /**
     * DELETE: deprovision and forget the SCIM resource.
     */
    public function delete(ScimUserModel $scimUser): void
    {
        DB::transaction(function () use ($scimUser): void {
            if ($scimUser->active) {
                $this->deprovision($scimUser);
            }

            $scimUser->delete();
        });
    }

    private function deprovision(ScimUserModel $scimUser): void
    {
        $user = $scimUser->user;
        $organization = $this->organization();

        if (! $user instanceof Model) {
            return;
        }

        if ($organization->isOwnedBy($user)) {
            throw ScimException::mutability("The organization's owner cannot be deprovisioned. Transfer ownership first.");
        }

        if ($organization->membershipFor($user) !== null) {
            app(RemoveMemberAction::class)->execute($organization, $user);
        }

        $orphaned = ! MembershipModel::query()->where('user_id', $user->getKey())->exists();
        $deactivate = $scimUser->created_by_scim && $orphaned && $this->users->status($user)->allowsAccess();

        if ($deactivate) {
            app(DeactivateUserAction::class)->execute($user, new DeactivateUserData(reason: __('roster::roster.scim_deprovisioned', ['organization' => $organization->name])));
        }

        $scimUser->update(['active' => false, 'deactivated_by_scim' => $deactivate || $scimUser->deactivated_by_scim]);
    }

    private function restore(ScimUserModel $scimUser): void
    {
        $user = $scimUser->user;

        if (! $user instanceof Model) {
            return;
        }

        if ($scimUser->deactivated_by_scim && $this->users->status($user) === Statuses::users()::deactivated()) {
            app(ReactivateUserAction::class)->execute($user);
        }

        $this->join($this->organization(), $user, 'scim');

        $scimUser->update(['active' => true, 'deactivated_by_scim' => false]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function profile(Model $user, array $data): void
    {
        $display = $this->string($data['displayName'] ?? null);

        // The SCIM view shows the account name when no display name is set;
        // echoing that back is not a change.
        $current = $this->users->profileIfExists($user)->display_name ?? $this->users->name($user);

        if ($display !== null && $display !== $current) {
            app(UpdateProfileAction::class)->execute($user, new UpdateProfileData(displayName: $display));
        }
    }

    /**
     * Record the IdP's id as the SSO subject, so a later SSO sign-in through
     * the token's connection lands on this account.
     */
    private function linkIdentity(Model $user, ?string $externalId, string $email): void
    {
        $connection = $this->context->token?->ssoConnection;

        if ($connection === null || $externalId === null) {
            return;
        }

        $existing = SsoIdentityModel::query()->where('connection_id', $connection->getKey())->where('subject', $externalId)->first();

        if ($existing !== null && (string) $existing->user_id !== (string) $user->getKey()) {
            throw ScimException::uniqueness("externalId [{$externalId}] is already linked to another account.");
        }

        SsoIdentityModel::query()->updateOrCreate(
            ['connection_id' => $connection->getKey(), 'subject' => $externalId],
            ['user_id' => $user->getKey(), 'email' => $email],
        );
    }

    /**
     * SCIM may only claim or move accounts onto the organization's domains.
     */
    private function guardDomain(string $email): void
    {
        $domain = strtolower(substr($email, (int) strrpos($email, '@') + 1));

        if (! $this->organization()->domains()->where('domain', $domain)->exists()) {
            throw ScimException::invalidValue("[{$domain}] is not one of the organization's domains.");
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function email(array $data): string
    {
        $candidates = [$data['userName'] ?? null];

        foreach ((array) ($data['emails'] ?? []) as $email) {
            if (is_array($email)) {
                $candidates[] = $email['value'] ?? null;
            }
        }

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && filter_var($candidate, FILTER_VALIDATE_EMAIL) !== false) {
                return strtolower($candidate);
            }
        }

        throw ScimException::invalidValue('userName (or a primary email) must be an email address.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function name(array $data, string $email): string
    {
        $name = (array) ($data['name'] ?? []);
        $parts = array_filter([$this->string($name['givenName'] ?? null), $this->string($name['familyName'] ?? null)]);

        return $this->string($name['formatted'] ?? null)
            ?? ($parts !== [] ? implode(' ', $parts) : null)
            ?? $this->string($data['displayName'] ?? null)
            ?? Str::before($email, '@');
    }

    private function findByEmail(string $email): ?Model
    {
        return $this->users->findByEmail($email);
    }

    private function organization(): OrganizationModel
    {
        return $this->context->organization();
    }

    private function boolean(mixed $value): bool
    {
        return is_string($value) ? in_array(strtolower($value), ['true', '1'], true) : (bool) $value;
    }

    private function string(mixed $value): ?string
    {
        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
    }

    /**
     * Resolve the groups of a page of users in two queries, so mapping each
     * one adds none.
     *
     * @param  array<int, ScimUserModel>  $scimUsers
     */
    private function preloadGroups(array $scimUsers): void
    {
        $userIds = array_values(array_filter(array_map(fn (ScimUserModel $scimUser): mixed => $scimUser->user_id, $scimUsers)));

        if ($userIds === []) {
            return;
        }

        $teams = MembershipModel::query()
            ->where('organization_id', $this->organization()->getKey())
            ->whereIn('user_id', $userIds)
            ->with('teams:id')
            ->get()
            ->mapWithKeys(fn (MembershipModel $membership): array => [(string) $membership->user_id => $membership->teams->modelKeys()]);

        $groups = ScimGroupModel::query()
            ->where('organization_id', $this->organization()->getKey())
            ->whereIn('team_id', $teams->flatten()->unique()->all())
            ->with('team')
            ->get();

        foreach ($scimUsers as $scimUser) {
            $mine = $teams->get((string) $scimUser->user_id, []);
            $this->context->groups[$scimUser->id] = $groups->filter(fn (ScimGroupModel $group): bool => in_array($group->team_id, $mine, true))->values()->all();
        }
    }

    /**
     * Groups this user is in, for the `groups` attribute.
     *
     * @return array<int, ScimGroupModel>
     */
    public function groupsOf(ScimUserModel $scimUser): array
    {
        if (array_key_exists($scimUser->id, $this->context->groups)) {
            return $this->context->groups[$scimUser->id];
        }

        $membership = $scimUser->user instanceof Model ? $this->organization()->membershipFor($scimUser->user) : null;

        if ($membership === null) {
            return [];
        }

        return ScimGroupModel::query()
            ->where('organization_id', $this->organization()->getKey())
            ->whereIn('team_id', $membership->teams()->pluck(Tables::name('teams').'.id'))
            ->with('team')
            ->get()
            ->all();
    }
}
