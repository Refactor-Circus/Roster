<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Role\Enums\RoleScope;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;

/**
 * @mixin RoleModel
 */
final class RoleResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'name' => $schema->string()->required(),
            'slug' => $schema->string()->required(),
            'scope' => $schema->string()->enum(array_column(RoleScope::cases(), 'value'))->required(),
            'organization' => $schema->string()->nullable()->description('The organization owning a custom role.')->required(),
            'description' => $schema->string()->nullable()->required(),
            'super' => $schema->boolean()->required(),
            'system' => $schema->boolean()->required(),
            'permissions' => $schema->array()->items($schema->string())->description('Permission names, when loaded.'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'scope' => $this->scope->value,
            'organization' => $this->organization?->slug,
            'description' => $this->description,
            'super' => $this->super,
            'system' => $this->system,
            'permissions' => $this->whenLoaded('permissions', fn (): array => $this->permissions
                ->map(fn (PermissionModel $permission): string => $permission->name)
                ->sort()
                ->values()
                ->all()),
        ];
    }
}
