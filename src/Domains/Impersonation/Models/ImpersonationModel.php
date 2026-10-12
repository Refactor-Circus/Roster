<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Roster\Database\Factories\ImpersonationFactory;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Tables;
use RefactorCircus\Roster\Support\Users;

/**
 * One impersonation: requested (link issued), started (link used), ended.
 *
 * @property string $id
 * @property int|string $impersonator_id
 * @property int|string $user_id
 * @property string|null $organization_id
 * @property string $reason
 * @property string|null $token_hash
 * @property Carbon $link_expires_at
 * @property Carbon|null $started_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $ended_at
 * @property string|null $end_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ImpersonationModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<ImpersonationFactory> */
    use HasFactory;

    use HasUlids;

    public const string ENDED_STOPPED = 'stopped';

    public const string ENDED_EXPIRED = 'expired';

    public const string ENDED_FORCED = 'forced';

    protected $fillable = [
        'impersonator_id',
        'user_id',
        'organization_id',
        'reason',
        'token_hash',
        'link_expires_at',
        'started_at',
        'expires_at',
        'ended_at',
        'end_reason',
    ];

    protected $hidden = ['token_hash'];

    public function getTable(): string
    {
        return Tables::name('impersonations');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function impersonator(): BelongsTo
    {
        return $this->belongsTo(app(Users::class)->model(), 'impersonator_id');
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
    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    /**
     * Started, not ended and not past its time limit.
     */
    public function isActive(): bool
    {
        return $this->started_at !== null
            && $this->ended_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNotNull('started_at')->whereNull('ended_at')->where('expires_at', '>', now());
    }

    /**
     * Whether the user is the one impersonating.
     */
    public function startedBy(?Model $user): bool
    {
        return $user !== null && (string) $this->impersonator_id === (string) $user->getKey();
    }

    /**
     * How stopping it ends it: stopped by its impersonator, forced by anyone else.
     */
    public function endReasonFor(?Model $actor): string
    {
        return $this->startedBy($actor) ? self::ENDED_STOPPED : self::ENDED_FORCED;
    }

    protected static function newFactory(): ImpersonationFactory
    {
        return ImpersonationFactory::new();
    }

    protected function casts(): array
    {
        return [
            'link_expires_at' => 'datetime',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }
}
