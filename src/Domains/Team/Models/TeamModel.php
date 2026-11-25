<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Authorization\Contracts\Scope;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Roster\Contracts\Status;
use RefactorCircus\Roster\Database\Factories\TeamFactory;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Statuses;
use RefactorCircus\Roster\Support\Tables;

/**
 * A group of organization members. Slugs are unique within the organization.
 *
 * A Keystone authorization scope inside its organization: grants in the
 * organization apply on the team, and the team grants nothing by itself.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $name
 * @property string $slug
 * @property Status $status
 * @property string|null $status_reason
 * @property Carbon|null $status_changed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class TeamModel extends Model implements Scope
{
    use DispatchesModelEvents;

    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    use HasUlids;

    protected $fillable = ['organization_id', 'name', 'slug', 'status', 'status_reason', 'status_changed_at'];

    public function getTable(): string
    {
        return Tables::name('teams');
    }

    /**
     * @return BelongsTo<OrganizationModel, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    /**
     * @return HasMany<TeamMemberModel, $this>
     */
    public function seats(): HasMany
    {
        return $this->hasMany(TeamMemberModel::class, 'team_id');
    }

    /**
     * @return BelongsToMany<MembershipModel, $this>
     */
    public function memberships(): BelongsToMany
    {
        return $this->belongsToMany(MembershipModel::class, Tables::name('team_members'), 'team_id', 'membership_id')->withTimestamps();
    }

    public function hasMember(Model $user): bool
    {
        return $this->memberships()->where('user_id', $user->getKey())->exists();
    }

    /**
     * The team's organization, whose grants also apply on the team.
     */
    public function parentScope(): ?Scope
    {
        return $this->organization;
    }

    /**
     * A team has no rule of its own: its organization's owner rule reaches it
     * through the parent scope.
     */
    public function grantsByItself(Authenticatable $user, string $ability): bool
    {
        return false;
    }

    /**
     * New teams start with the configured enum's default status.
     */
    protected static function booted(): void
    {
        self::creating(function (self $team): void {
            $team->status ??= Statuses::teams()::default();
        });
    }

    protected static function newFactory(): TeamFactory
    {
        return TeamFactory::new();
    }

    protected function casts(): array
    {
        return [
            'status' => Statuses::teams(),
            'status_changed_at' => 'datetime',
        ];
    }
}
