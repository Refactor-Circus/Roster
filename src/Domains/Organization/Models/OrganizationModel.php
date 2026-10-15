<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Roster\Contracts\Status;
use RefactorCircus\Roster\Database\Factories\OrganizationFactory;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Statuses;
use RefactorCircus\Roster\Support\Tables;
use RefactorCircus\Roster\Support\Users;

/**
 * The tenant: owns teams, memberships and invitations.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property int|string|null $owner_id
 * @property bool $personal
 * @property bool $auto_join
 * @property string $provisioned_status
 * @property Status $status
 * @property string|null $status_reason
 * @property Carbon|null $status_changed_at
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class OrganizationModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    use HasUlids;
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'owner_id', 'personal', 'auto_join', 'provisioned_status', 'status', 'status_reason', 'status_changed_at'];

    protected $attributes = [
        'personal' => false,
        'auto_join' => false,
    ];

    public function getTable(): string
    {
        return Tables::name('organizations');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(app(Users::class)->model(), 'owner_id');
    }

    /**
     * @return HasMany<MembershipModel, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(MembershipModel::class, 'organization_id');
    }

    /**
     * @return BelongsToMany<Model, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(app(Users::class)->model(), Tables::name('memberships'), 'organization_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * @return HasMany<TeamModel, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(TeamModel::class, 'organization_id');
    }

    /**
     * @return HasMany<OrganizationDomainModel, $this>
     */
    public function domains(): HasMany
    {
        return $this->hasMany(OrganizationDomainModel::class, 'organization_id');
    }

    /**
     * @return HasMany<InvitationModel, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(InvitationModel::class, 'organization_id');
    }

    /**
     * @return HasMany<OrganizationLinkModel, $this>
     */
    public function links(): HasMany
    {
        return $this->hasMany(OrganizationLinkModel::class, 'organization_id');
    }

    public function isOwnedBy(Model $user): bool
    {
        return $this->owner_id !== null && (string) $this->owner_id === (string) $user->getKey();
    }

    public function membershipFor(Model $user): ?MembershipModel
    {
        return $this->memberships()->where('user_id', $user->getKey())->first();
    }

    /**
     * New organizations start with the configured enum's default status.
     */
    protected static function booted(): void
    {
        self::creating(function (self $organization): void {
            $organization->status ??= Statuses::organizations()::default();
        });
    }

    protected static function newFactory(): OrganizationFactory
    {
        return OrganizationFactory::new();
    }

    protected function casts(): array
    {
        return [
            'personal' => 'boolean',
            'auto_join' => 'boolean',
            'status' => Statuses::organizations(),
            'status_changed_at' => 'datetime',
        ];
    }
}
