<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Tables;
use RefactorCircus\Roster\Support\Users;

/**
 * A role held by a user: globally, in an organization, or on a team.
 *
 * @property string $id
 * @property string $role_id
 * @property int|string $user_id
 * @property string|null $organization_id
 * @property string|null $team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class RoleAssignmentModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    protected $fillable = ['role_id', 'user_id', 'organization_id', 'team_id'];

    public function getTable(): string
    {
        return Tables::name('role_assignments');
    }

    /**
     * @return BelongsTo<RoleModel, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(RoleModel::class, 'role_id');
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
     * @return BelongsTo<TeamModel, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(TeamModel::class, 'team_id');
    }
}
