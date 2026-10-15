<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Roster\Contracts\AccountStatus;
use RefactorCircus\Roster\Database\Factories\ProfileFactory;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Statuses;
use RefactorCircus\Roster\Support\Tables;
use RefactorCircus\Roster\Support\Users;

/**
 * A user's Roster profile and account status.
 *
 * Lives beside the host's users table rather than on it, so Roster works on
 * any user model without altering its schema.
 *
 * @property string $id
 * @property int|string $user_id
 * @property string|null $display_name
 * @property string|null $avatar_url
 * @property string|null $timezone
 * @property string|null $locale
 * @property string|null $bio
 * @property array<string, mixed>|null $meta
 * @property AccountStatus $status
 * @property string|null $status_reason
 * @property Carbon|null $status_changed_at
 * @property string|null $current_organization_id
 * @property string|null $current_team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ProfileModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    use HasUlids;

    protected $fillable = [
        'user_id',
        'display_name',
        'avatar_url',
        'timezone',
        'locale',
        'bio',
        'meta',
        'status',
        'status_reason',
        'status_changed_at',
        'current_organization_id',
        'current_team_id',
    ];

    public function getTable(): string
    {
        return Tables::name('profiles');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(app(Users::class)->model(), 'user_id');
    }

    /**
     * @return BelongsTo<OrganizationModel, $this>
     */
    public function currentOrganization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'current_organization_id');
    }

    /**
     * @return BelongsTo<TeamModel, $this>
     */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(TeamModel::class, 'current_team_id');
    }

    /**
     * New profiles start with the configured enum's default status.
     */
    protected static function booted(): void
    {
        self::creating(function (self $profile): void {
            $profile->status ??= Statuses::users()::default();
        });
    }

    protected static function newFactory(): ProfileFactory
    {
        return ProfileFactory::new();
    }

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'status' => Statuses::users(),
            'status_changed_at' => 'datetime',
        ];
    }
}
