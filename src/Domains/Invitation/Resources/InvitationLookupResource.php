<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * What the public invitation lookup shows whoever holds the token: just
 * enough for the page the email links to. Never the token or its hash, an
 * id or slug, the inviter, the status, or any timestamp but `expires_at`.
 *
 * @mixin InvitationModel
 */
final class InvitationLookupResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'email' => $schema->string()->required(),
            'organization' => $schema->object([
                'name' => $schema->string()->required(),
            ])->required(),
            'teams' => $schema->array()->items($schema->string())->description('Team names, sorted.')->required(),
            'expires_at' => $schema->string()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $teams = $this->relationLoaded('teamModels')
            ? $this->getRelation('teamModels')->pluck('name')->all()
            : TeamModel::query()->whereIn('id', (array) $this->teams)->orderBy('name')->pluck('name')->all();

        return [
            'email' => $this->email,
            'organization' => ['name' => $this->organization?->name],
            'teams' => $teams,
            'expires_at' => $this->expires_at->toIso8601String(),
        ];
    }
}
