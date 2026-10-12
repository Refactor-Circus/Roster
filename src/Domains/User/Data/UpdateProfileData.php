<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Roster\Domains\User\Concerns\ProfileRules;

/**
 * Input for UpdateProfileAction: the profile fields to change. Fields left
 * out are kept.
 */
final readonly class UpdateProfileData extends Data
{
    use ProfileRules;

    /**
     * @param  array<string, mixed>|Missing|null  $meta
     */
    public function __construct(
        public string|Missing|null $displayName = new Missing,
        public string|Missing|null $avatarUrl = new Missing,
        public string|Missing|null $timezone = new Missing,
        public string|Missing|null $locale = new Missing,
        public string|Missing|null $bio = new Missing,
        public array|Missing|null $meta = new Missing,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return self::profileRules();
    }
}
