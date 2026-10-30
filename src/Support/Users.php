<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Support;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use InvalidArgumentException;
use RefactorCircus\Roster\Contracts\AccountStatus;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;

/**
 * The host application's user model, as configured in `roster.users`.
 *
 * Every Roster Action reaches users through this class, so the model class,
 * key type and column names are resolved in exactly one place.
 */
final class Users
{
    /**
     * Where the user counts per status are cached (the Atrium user-status
     * widget reads them); forgotten whenever one of Roster's User actions
     * finishes.
     */
    public const string STATUS_COUNTS_CACHE = 'roster:atrium:user-status-counts';

    /**
     * Whether each user model soft-deletes, read from its traits once.
     *
     * @var array<string, bool>
     */
    private array $softDeletes = [];

    public function __construct(private readonly Repository $config) {}

    /**
     * @return class-string<Model>
     */
    public function model(): string
    {
        $model = $this->config->get('roster.users.model');

        if (! is_string($model) || ! is_subclass_of($model, Model::class)) {
            throw new InvalidArgumentException('[roster.users.model] must be an Eloquent model class.');
        }

        return $model;
    }

    public function newModel(): Model
    {
        $model = $this->model();

        return new $model;
    }

    /**
     * @return Builder<Model>
     */
    public function query(): Builder
    {
        return $this->newModel()->newQuery();
    }

    public function table(): string
    {
        return $this->newModel()->getTable();
    }

    public function routeKeyName(): string
    {
        return $this->newModel()->getRouteKeyName();
    }

    /**
     * The user with this email, ignoring case. The address as given and its
     * lowercase form are tried first with a plain equality the email index
     * serves (Roster lowercases the addresses it writes); only a miss falls
     * back to a case-insensitive comparison (CaseInsensitive), which on
     * PostgreSQL wants an optional `lower(email)` expression index. The one
     * shared copy of this lookup.
     */
    public function findByEmail(string $email): ?Model
    {
        $column = $this->column('email');

        if ($column === null || $email === '') {
            return null;
        }

        $matches = fn (Model $user): bool => strcasecmp((string) $this->email($user), $email) === 0;

        return $this->query()->whereIn($column, array_values(array_unique([$email, strtolower($email)])))->get()->first($matches)
            ?? CaseInsensitive::where($this->query(), $column, $email)->get()->first($matches);
    }

    /**
     * The users with these emails, ignoring case, keyed by lowercased email:
     * findByEmail() for a whole batch, in a query per 500 addresses plus one
     * case-insensitive query per 100 addresses not found as given.
     *
     * @param  array<int, string>  $emails
     * @return array<string, Model>
     */
    public function findManyByEmail(array $emails): array
    {
        $column = $this->column('email');
        $emails = array_values(array_unique(array_filter($emails, fn (string $email): bool => $email !== '')));
        $wanted = array_values(array_unique(array_map(strtolower(...), $emails)));

        if ($column === null || $wanted === []) {
            return [];
        }

        $found = [];
        $collect = function (iterable $users) use (&$found): void {
            foreach ($users as $user) {
                if ($user instanceof Model) {
                    $found[strtolower((string) $this->email($user))] ??= $user;
                }
            }
        };

        foreach (array_chunk(array_values(array_unique([...$wanted, ...$emails])), 500) as $chunk) {
            $collect($this->query()->whereIn($column, $chunk)->get());
        }

        foreach (array_chunk(array_values(array_diff($wanted, array_keys($found))), 100) as $chunk) {
            $collect($this->query()->where(function (Builder $query) use ($chunk, $column): void {
                foreach ($chunk as $email) {
                    $query->orWhere(fn (Builder $one): Builder => CaseInsensitive::where($one, $column, $email));
                }
            })->get());
        }

        return array_intersect_key($found, array_flip($wanted));
    }

    /**
     * Whether the user model soft-deletes (Laravel's SoftDeletes), so deleted
     * users can be restored.
     */
    public function softDeletes(): bool
    {
        $model = $this->model();

        return $this->softDeletes[$model] ??= in_array(SoftDeletes::class, class_uses_recursive($model), true);
    }

    /**
     * Users including deleted ones, when the model soft-deletes.
     *
     * @return Builder<Model>
     */
    public function withTrashed(): Builder
    {
        $query = $this->query();

        return $this->softDeletes() ? $query->withoutGlobalScope(SoftDeletingScope::class) : $query;
    }

    /**
     * Only deleted users; none when the model can't soft-delete.
     *
     * @return Builder<Model>
     */
    public function onlyTrashed(): Builder
    {
        $query = $this->withTrashed();

        return $this->softDeletes()
            ? $query->whereNotNull($this->newModel()->qualifyColumn('deleted_at'))
            : $query->whereRaw('1 = 0');
    }

    /**
     * Find a user by route key, deleted ones included.
     */
    public function findWithTrashedOrFail(mixed $key): Model
    {
        return $this->withTrashed()->where($this->routeKeyName(), $key)->firstOrFail();
    }

    /**
     * Whether a user is soft-deleted.
     */
    public function trashed(Model $user): bool
    {
        return $this->softDeletes() && $user->getAttribute('deleted_at') !== null;
    }

    /**
     * A user the caller already has (no query), or one found by route key.
     */
    public function resolve(mixed $user): Model
    {
        return $user instanceof Model ? $user : $this->findOrFail($user);
    }

    /**
     * Find a user by its route key, the identifier every surface uses.
     */
    public function findOrFail(mixed $key): Model
    {
        return $this->query()->where($this->routeKeyName(), $key)->firstOrFail();
    }

    /**
     * The user model's column for a logical field (`name`, `email`, `password`),
     * or null when the users table has no such column.
     */
    public function column(string $field): ?string
    {
        $column = $this->config->get('roster.users.columns.'.$field, $field);

        return is_string($column) && $column !== '' ? $column : null;
    }

    public function name(Model $user): ?string
    {
        $column = $this->column('name');

        return $column === null ? null : $this->string($user->getAttribute($column));
    }

    public function email(Model $user): ?string
    {
        $column = $this->column('email');

        return $column === null ? null : $this->string($user->getAttribute($column));
    }

    /**
     * The user's profile, created on first access.
     */
    public function profile(Model $user): ProfileModel
    {
        $profile = $user->relationLoaded('rosterProfile') ? $user->getRelation('rosterProfile') : null;

        if (! $profile instanceof ProfileModel) {
            $profile = ProfileModel::query()->firstOrCreate(['user_id' => $user->getKey()]);
            $user->setRelation('rosterProfile', $profile);
        }

        return $profile;
    }

    /**
     * The user's profile row, without creating one.
     */
    public function profileIfExists(Model $user): ?ProfileModel
    {
        if (! $user->relationLoaded('rosterProfile')) {
            // Remembered on the model, a missing profile included, so status
            // and context lookups query once per user.
            $user->setRelation('rosterProfile', ProfileModel::query()->where('user_id', $user->getKey())->first());
        }

        $profile = $user->getRelation('rosterProfile');

        return $profile instanceof ProfileModel ? $profile : null;
    }

    /**
     * Whether the user has proven they own their email address. Models that
     * do not implement MustVerifyEmail never count as verified.
     */
    public function emailVerified(Model $user): bool
    {
        return $user instanceof MustVerifyEmail && $user->hasVerifiedEmail();
    }

    /**
     * The user's status. A user without a profile row is active.
     */
    public function status(Model $user): AccountStatus
    {
        return $this->profileIfExists($user)->status ?? Statuses::users()::active();
    }

    private function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}
