<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Roster\Domains\Organization\Concerns\OrganizationRules;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;

/**
 * Input for UpdateOrganizationAction: the fields and settings to change.
 * Fields left out are kept; a given `domains` list replaces the old one.
 */
final readonly class UpdateOrganizationData extends Data
{
    use OrganizationRules;

    /**
     * @param  array<int, string>|Missing  $domains
     */
    public function __construct(
        public string|Missing $name = new Missing,
        public string|Missing $slug = new Missing,
        public bool|Missing $autoJoin = new Missing,
        public UserStatus|Missing $provisionedStatus = new Missing,
        public array|Missing $domains = new Missing,
    ) {}

    /**
     * Pass the organization being updated so its own slug and domains pass
     * the unique checks.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?OrganizationModel $organization = null): array
    {
        $slug = Rule::unique(OrganizationModel::class, 'slug');

        if ($organization !== null) {
            $slug->ignore($organization->getKey());
        }

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'alpha_dash', 'max:255', $slug],
        ] + self::settingsRules($organization);
    }
}
