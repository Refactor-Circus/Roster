<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;
use RefactorCircus\Roster\Domains\User\Resources\UserSummaryResource;

/**
 * The link token is never serialized.
 *
 * @mixin ImpersonationModel
 */
final class ImpersonationResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'user' => $schema->object(UserSummaryResource::schema($schema))->nullable()->required(),
            'impersonator' => $schema->object(UserSummaryResource::schema($schema))->nullable()->required(),
            'organization' => $schema->string()->nullable()->description('Organization slug.')->required(),
            'reason' => $schema->string()->required(),
            'active' => $schema->boolean()->required(),
            'link_expires_at' => $schema->string()->required(),
            'started_at' => $schema->string()->nullable()->required(),
            'expires_at' => $schema->string()->nullable()->required(),
            'ended_at' => $schema->string()->nullable()->required(),
            'end_reason' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;
        $impersonator = $this->impersonator;

        return [
            'id' => $this->id,
            'user' => $user instanceof Model ? (new UserSummaryResource($user))->resolve($request) : null,
            'impersonator' => $impersonator instanceof Model ? (new UserSummaryResource($impersonator))->resolve($request) : null,
            'organization' => $this->organization?->slug,
            'reason' => $this->reason,
            'active' => $this->isActive(),
            'link_expires_at' => $this->link_expires_at->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'end_reason' => $this->end_reason,
        ];
    }
}
