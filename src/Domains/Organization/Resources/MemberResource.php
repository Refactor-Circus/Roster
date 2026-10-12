<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Domains\User\Resources\UserSummaryResource;
use RefactorCircus\Roster\Support\Users;

/**
 * @mixin MembershipModel
 */
final class MemberResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'user' => $schema->object(UserSummaryResource::schema($schema))->nullable()->required(),
            'owner' => $schema->boolean()->required(),
            'status' => $schema->string()->enum([...array_column(UserStatus::cases(), 'value'), null])->nullable()->required(),
            'source' => $schema->string()->enum(array_column(MembershipSource::cases(), 'value'))->required(),
            'teams' => $schema->array()->items($schema->string())->description('Team slugs, when loaded.'),
            'joined_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;
        $organization = $this->organization;

        return [
            'user' => $user instanceof Model ? (new UserSummaryResource($user))->resolve($request) : null,
            'owner' => $user instanceof Model && $organization !== null && $organization->isOwnedBy($user),
            'status' => $user instanceof Model ? app(Users::class)->status($user)->value : null,
            'source' => $this->source->value,
            'teams' => $this->whenLoaded('teams', fn (): array => $this->teams
                ->map(fn (TeamModel $team): string => $team->slug)
                ->sort()
                ->values()
                ->all()),
            'joined_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
