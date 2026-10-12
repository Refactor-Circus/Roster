<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Tables;

/**
 * A team as an organization's identity provider sees it over SCIM.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $team_id
 * @property string|null $external_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class ScimGroupModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    protected $fillable = ['organization_id', 'team_id', 'external_id'];

    public function getTable(): string
    {
        return Tables::name('scim_groups');
    }

    /**
     * @return BelongsTo<TeamModel, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(TeamModel::class, 'team_id');
    }

    /**
     * @return BelongsTo<OrganizationModel, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }
}
