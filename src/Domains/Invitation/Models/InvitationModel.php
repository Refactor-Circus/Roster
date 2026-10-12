<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Roster\Database\Factories\InvitationFactory;
use RefactorCircus\Roster\Domains\Invitation\Enums\InvitationStatus;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Tables;

/**
 * An emailed invitation into an organization, optionally onto teams.
 *
 * Only a hash of the token is stored; the token itself exists once, in the
 * invitation email.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $email
 * @property string $token_hash
 * @property array<int, string>|null $teams
 * @property int|string|null $invited_by
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $declined_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class InvitationModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<InvitationFactory> */
    use HasFactory;

    use HasUlids;

    protected $fillable = [
        'organization_id',
        'email',
        'token_hash',
        'teams',
        'invited_by',
        'expires_at',
        'accepted_at',
        'declined_at',
        'revoked_at',
    ];

    protected $hidden = ['token_hash'];

    public function getTable(): string
    {
        return Tables::name('invitations');
    }

    /**
     * @return BelongsTo<OrganizationModel, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    public function status(): InvitationStatus
    {
        return match (true) {
            $this->accepted_at !== null => InvitationStatus::Accepted,
            $this->declined_at !== null => InvitationStatus::Declined,
            $this->revoked_at !== null => InvitationStatus::Revoked,
            $this->expires_at->isPast() => InvitationStatus::Expired,
            default => InvitationStatus::Pending,
        };
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeWithStatus(Builder $query, InvitationStatus $status): void
    {
        match ($status) {
            InvitationStatus::Pending => $query->pending(),
            InvitationStatus::Accepted => $query->whereNotNull('accepted_at'),
            InvitationStatus::Declined => $query->whereNotNull('declined_at'),
            InvitationStatus::Revoked => $query->whereNotNull('revoked_at'),
            InvitationStatus::Expired => $query->whereNull('accepted_at')->whereNull('declined_at')->whereNull('revoked_at')->where('expires_at', '<=', now()),
        };
    }

    protected static function newFactory(): InvitationFactory
    {
        return InvitationFactory::new();
    }

    protected function casts(): array
    {
        return [
            'teams' => 'array',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
