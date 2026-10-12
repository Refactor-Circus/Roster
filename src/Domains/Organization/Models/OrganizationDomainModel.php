<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Roster\Support\Tables;

/**
 * An email domain whose verified users may auto-join the organization.
 *
 * @property string $id
 * @property string $organization_id
 * @property string $domain
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class OrganizationDomainModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    protected $fillable = ['organization_id', 'domain'];

    public function getTable(): string
    {
        return Tables::name('organization_domains');
    }

    /**
     * @return BelongsTo<OrganizationModel, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }
}
