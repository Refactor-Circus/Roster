<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\User\Resources\UserSummaryResource;
use RefactorCircus\Roster\Support\Statuses;

/**
 * @mixin TeamModel
 */
final class TeamResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->required(),
            'name' => $schema->string()->required(),
            'organization' => $schema->string()->nullable()->required(),
            'status' => $schema->string()->enum(Statuses::values(Statuses::teams()))->required(),
            'status_reason' => $schema->string()->nullable()->required(),
            'status_changed_at' => $schema->string()->nullable()->required(),
            'members_count' => $schema->integer()->description('When counted.'),
            'members' => $schema->array()->items($schema->object(UserSummaryResource::schema($schema)))->description('When loaded: at most the first 100 members, oldest first; list the organization\'s members with `team` for all of them.'),
            'created_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'organization' => $this->organization?->slug,
            'status' => $this->status->value,
            'status_reason' => $this->status_reason,
            'status_changed_at' => $this->status_changed_at?->toIso8601String(),
            'members_count' => $this->whenCounted('seats'),
            'members' => $this->whenLoaded('memberships', fn (): array => $this->memberships
                ->map(fn (MembershipModel $membership): ?Model => $membership->user)
                ->filter()
                ->map(fn (Model $user): array => (new UserSummaryResource($user))->resolve($request))
                ->values()
                ->all()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
