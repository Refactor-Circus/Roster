<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;

/**
 * @mixin ProfileModel
 */
final class ProfileResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'display_name' => $schema->string()->nullable()->required(),
            'avatar_url' => $schema->string()->nullable()->required(),
            'timezone' => $schema->string()->nullable()->required(),
            'locale' => $schema->string()->nullable()->required(),
            'bio' => $schema->string()->nullable()->required(),
            'meta' => $schema->object()->description('Free-form profile data.')->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'display_name' => $this->display_name,
            'avatar_url' => $this->avatar_url,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'bio' => $this->bio,
            'meta' => $this->meta ?? (object) [],
        ];
    }
}
