<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Support\Tables;

/**
 * A seat on a team, held through an organization membership.
 *
 * @property string $id
 * @property string $team_id
 * @property string $membership_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class TeamMemberModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    protected $fillable = ['team_id', 'membership_id'];

    public function getTable(): string
    {
        return Tables::name('team_members');
    }

    /**
     * @return BelongsTo<TeamModel, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(TeamModel::class, 'team_id');
    }

    /**
     * @return BelongsTo<MembershipModel, $this>
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(MembershipModel::class, 'membership_id');
    }
}
