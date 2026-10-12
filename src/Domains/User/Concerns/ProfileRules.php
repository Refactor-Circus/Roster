<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Concerns;

use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Domains\User\Data\UpdateProfileData;

/**
 * Profile field rules, shared by every Action that writes a profile.
 */
trait ProfileRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected static function profileRules(): array
    {
        return [
            'display_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'avatar_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'timezone' => ['sometimes', 'nullable', 'timezone:all'],
            'locale' => ['sometimes', 'nullable', 'string', 'max:16'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'meta' => ['sometimes', 'nullable', 'array'],
        ];
    }

    /**
     * The profile fields the input gave.
     *
     * @return array<string, mixed>
     */
    protected static function profileAttributes(CreateUserData|UpdateProfileData $data): array
    {
        return array_intersect_key($data->toArray(), array_flip(['display_name', 'avatar_url', 'timezone', 'locale', 'bio', 'meta']));
    }
}
