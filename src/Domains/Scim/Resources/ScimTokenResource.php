<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Roster\Domains\Scim\Models\ScimTokenModel;

/**
 * The token itself is never serialized; it is returned once, at issue.
 *
 * @mixin ScimTokenModel
 */
final class ScimTokenResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'name' => $schema->string()->required(),
            'organization' => $schema->string()->nullable()->required(),
            'sso_connection' => $schema->string()->nullable()->required(),
            'base_url' => $schema->string()->nullable()->description('The SCIM base URL to give the identity provider.')->required(),
            'usable' => $schema->boolean()->required(),
            'last_used_at' => $schema->string()->nullable()->required(),
            'expires_at' => $schema->string()->nullable()->required(),
            'revoked_at' => $schema->string()->nullable()->required(),
            'created_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $organization = $this->organization;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'organization' => $organization?->slug,
            'sso_connection' => $this->ssoConnection?->slug,
            'base_url' => $organization !== null && Route::has('roster.scim.users.index') ? str_replace('/Users', '', route('roster.scim.users.index', $organization->slug)) : null,
            'usable' => $this->isUsable(),
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
