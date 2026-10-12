<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Roster\Domains\Role\Enums\RoleScope;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\User\Resources\UserSummaryResource;

/**
 * @mixin RoleAssignmentModel
 */
final class RoleAssignmentResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'user' => $schema->object(UserSummaryResource::schema($schema))->nullable()->required(),
            'role' => $schema->object([
                'id' => $schema->string()->required(),
                'slug' => $schema->string()->required(),
                'name' => $schema->string()->required(),
                'scope' => $schema->string()->enum(array_column(RoleScope::cases(), 'value'))->required(),
            ])->nullable()->required(),
            'organization' => $schema->string()->nullable()->description('Organization slug.')->required(),
            'team' => $schema->string()->nullable()->description('Team slug.')->required(),
            'assigned_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;

        return [
            'id' => $this->id,
            'user' => $user instanceof Model ? (new UserSummaryResource($user))->resolve($request) : null,
            'role' => $this->role === null ? null : [
                'id' => $this->role->id,
                'slug' => $this->role->slug,
                'name' => $this->role->name,
                'scope' => $this->role->scope->value,
            ],
            'organization' => $this->organization?->slug,
            'team' => $this->team?->slug,
            'assigned_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
