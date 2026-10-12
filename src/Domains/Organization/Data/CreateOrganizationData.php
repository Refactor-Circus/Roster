<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Roster\Domains\Organization\Concerns\OrganizationRules;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Support\Users;

/**
 * Input for CreateOrganizationAction: the organization and its settings.
 *
 * Without a slug one is made from the name. `owner` is the owning user's
 * route key. `provisioned_status` is the status of accounts created through
 * its SSO or SCIM: active, or waiting for approval.
 */
final readonly class CreateOrganizationData extends Data
{
    use OrganizationRules;

    /**
     * @param  array<int, string>  $domains
     */
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public int|string|null $owner = null,
        public bool $autoJoin = false,
        public UserStatus $provisionedStatus = UserStatus::Active,
        public array $domains = [],
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        $users = app(Users::class);

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'alpha_dash', 'max:255', Rule::unique(OrganizationModel::class, 'slug')],
            'owner' => ['sometimes', 'nullable', Rule::exists($users->table(), $users->routeKeyName())],
        ] + self::settingsRules();
    }
}
