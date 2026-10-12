<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;
use RefactorCircus\Roster\Roster;
use RefactorCircus\Roster\Support\Users;

/**
 * A user as every surface returns it: identity from the host model, profile
 * and status from Roster. A user with no profile row yet reads as an empty,
 * active profile.
 *
 * @property Model $resource
 */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->union(['integer', 'string'])->description('The user\'s route key.')->required(),
            'name' => $schema->string()->nullable()->required(),
            'email' => $schema->string()->nullable()->required(),
            'status' => $schema->string()->enum(array_column(UserStatus::cases(), 'value'))->required(),
            'status_reason' => $schema->string()->nullable()->required(),
            'status_changed_at' => $schema->string()->nullable()->required(),
            'profile' => $schema->object(ProfileResource::schema($schema))->required(),
            'current_organization' => $schema->string()->nullable()->description('Organization slug.')->required(),
            'current_team' => $schema->string()->nullable()->description('Team slug.')->required(),
            'created_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $users = app(Users::class);
        $user = $this->resource;

        $profile = $user->relationLoaded('rosterProfile') ? $user->getRelation('rosterProfile') : null;
        $profile = $profile instanceof ProfileModel ? $profile : new ProfileModel;

        $createdAt = $user->getAttribute($user->getCreatedAtColumn() ?? 'created_at');

        return [
            'id' => $user->getRouteKey(),
            'name' => $users->name($user),
            'email' => $users->email($user),
            'status' => ($profile->status ?? UserStatus::Active)->value,
            'status_reason' => $profile->status_reason,
            'status_changed_at' => $profile->status_changed_at?->toIso8601String(),
            'profile' => (new ProfileResource($profile))->resolve($request),
            'current_organization' => app(Roster::class)->organization($user)?->slug,
            'current_team' => app(Roster::class)->team($user)?->slug,
            'created_at' => $createdAt instanceof Carbon ? $createdAt->toIso8601String() : null,
        ];
    }
}
