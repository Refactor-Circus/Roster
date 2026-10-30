<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Roster\Database\Factories\MembershipFactory;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Tables;
use RefactorCircus\Roster\Support\Users;

/**
 * A user's membership of an organization.
 *
 * @property string $id
 * @property string $organization_id
 * @property int|string $user_id
 * @property string $source a `MembershipSource` value or a registered one (`Services\MembershipSources`); see sourceEnum()
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class MembershipModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<MembershipFactory> */
    use HasFactory;

    use HasUlids;

    protected $fillable = ['organization_id', 'user_id', 'source'];

    protected $attributes = [
        'source' => 'direct',
    ];

    public function getTable(): string
    {
        return Tables::name('memberships');
    }

    /**
     * @return BelongsTo<OrganizationModel, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(app(Users::class)->model(), 'user_id');
    }

    /**
     * @return BelongsToMany<TeamModel, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(TeamModel::class, Tables::name('team_members'), 'membership_id', 'team_id')->withTimestamps();
    }

    /**
     * The built-in source this membership came from, or null for a source
     * another package registered (or one nothing registers any more).
     */
    public function sourceEnum(): ?MembershipSource
    {
        return MembershipSource::tryFrom($this->source);
    }

    protected static function newFactory(): MembershipFactory
    {
        return MembershipFactory::new();
    }

    /**
     * Stored as its value, so a built-in case can still be assigned.
     *
     * @return Attribute<never, MembershipSource|string>
     */
    protected function source(): Attribute
    {
        return Attribute::set(fn (MembershipSource|string $value): string => $value instanceof MembershipSource ? $value->value : $value);
    }

    protected function casts(): array
    {
        return [
            'source' => 'string',
        ];
    }
}
