<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Roster\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Roster\Domains\User\Resources\UserSummaryResource;

/**
 * @mixin SsoIdentityModel
 */
final class SsoIdentityResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'connection' => $schema->string()->nullable()->required(),
            'organization' => $schema->string()->nullable()->required(),
            'user' => $schema->object(UserSummaryResource::schema($schema))->nullable()->required(),
            'subject' => $schema->string()->required(),
            'email' => $schema->string()->nullable()->required(),
            'last_login_at' => $schema->string()->nullable()->required(),
            'linked_at' => $schema->string()->nullable()->required(),
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
            'connection' => $this->connection?->slug,
            'organization' => $this->connection?->organization?->slug,
            'user' => $user instanceof Model ? (new UserSummaryResource($user))->resolve($request) : null,
            'subject' => $this->subject,
            'email' => $this->email,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'linked_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
