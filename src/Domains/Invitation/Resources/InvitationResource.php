<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Roster\Domains\Invitation\Enums\InvitationStatus;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * The token is never serialized: it exists only in the invitation email.
 *
 * @mixin InvitationModel
 */
final class InvitationResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'email' => $schema->string()->required(),
            'organization' => $schema->string()->nullable()->description('Organization slug.')->required(),
            'teams' => $schema->array()->items($schema->string())->description('Team slugs.')->required(),
            'status' => $schema->string()->enum(array_column(InvitationStatus::cases(), 'value'))->required(),
            'expires_at' => $schema->string()->required(),
            'accepted_at' => $schema->string()->nullable()->required(),
            'declined_at' => $schema->string()->nullable()->required(),
            'revoked_at' => $schema->string()->nullable()->required(),
            'created_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'organization' => $this->organization?->slug,
            'teams' => $this->relationLoaded('teamModels')
                ? $this->getRelation('teamModels')->pluck('slug')->all()
                : TeamModel::query()->whereIn('id', (array) $this->teams)->orderBy('slug')->pluck('slug')->all(),
            'status' => $this->status()->value,
            'expires_at' => $this->expires_at->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'declined_at' => $this->declined_at?->toIso8601String(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
