<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Roster\Database\Factories\RoleFactory;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Role\Enums\RoleScope;
use RefactorCircus\Roster\Support\Tables;

/**
 * A bundle of permissions, assigned globally, per organization or per team.
 *
 * A role without an organization is shared by every organization; one with
 * an organization is that organization's own.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property RoleScope $scope
 * @property string|null $organization_id
 * @property string|null $description
 * @property bool $super
 * @property bool $system
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class RoleModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    use HasUlids;

    protected $fillable = ['name', 'slug', 'scope', 'organization_id', 'description', 'super', 'system'];

    protected $attributes = [
        'super' => false,
        'system' => false,
    ];

    public function getTable(): string
    {
        return Tables::name('roles');
    }

    /**
     * @return BelongsToMany<PermissionModel, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(PermissionModel::class, Tables::name('permission_role'), 'role_id', 'permission_id');
    }

    /**
     * @return BelongsTo<OrganizationModel, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organization_id');
    }

    /**
     * @return HasMany<RoleAssignmentModel, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(RoleAssignmentModel::class, 'role_id');
    }

    protected static function newFactory(): RoleFactory
    {
        return RoleFactory::new();
    }

    protected function casts(): array
    {
        return [
            'scope' => RoleScope::class,
            'super' => 'boolean',
            'system' => 'boolean',
        ];
    }
}
