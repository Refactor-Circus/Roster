<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Roster\Database\Factories\TeamFactory;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Tables;

/**
 * A group of organization members. Slugs are unique within the organization.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class TeamModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    use HasUlids;

    protected $fillable = ['organization_id', 'name', 'slug'];

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

    protected static function newFactory(): TeamFactory
    {
        return TeamFactory::new();
    }
}
