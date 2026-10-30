<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Services\MembershipSources;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\User\Resources\UserSummaryResource;
use RefactorCircus\Roster\Support\Statuses;
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
            'status' => $schema->string()->enum([...array_column(Statuses::users()::cases(), 'value'), null])->nullable()->required(),
            // Not an enum: other packages register sources, and a stored one
            // nothing registers any more still shows as stored.
            'source' => $schema->string()->description('How the user joined: a registered source\'s label, otherwise the stored value.')->required(),
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
            'source' => app(MembershipSources::class)->label($this->source),
            'teams' => $this->whenLoaded('teams', fn (): array => $this->teams
                ->map(fn (TeamModel $team): string => $team->slug)
                ->sort()
                ->values()
                ->all()),
            'joined_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
