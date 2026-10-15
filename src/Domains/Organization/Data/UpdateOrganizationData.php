<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Roster\Contracts\AccountStatus;
use RefactorCircus\Roster\Contracts\Status;
use RefactorCircus\Roster\Domains\Organization\Concerns\OrganizationRules;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Concerns\PreparesStatuses;
use RefactorCircus\Roster\Support\Statuses;

/**
 * Input for UpdateOrganizationAction: the fields and settings to change.
 * Fields left out are kept; a given `domains` list replaces the old one.
 * A new `status` stamps `status_changed_at`; `status_reason` is kept unless
 * given.
 */
final readonly class UpdateOrganizationData extends Data
{
    use OrganizationRules;
    use PreparesStatuses;

    /**
     * @param  array<int, string>|Missing  $domains
     */
    public function __construct(
        public string|Missing $name = new Missing,
        public string|Missing $slug = new Missing,
        public bool|Missing $autoJoin = new Missing,
        public AccountStatus|Missing $provisionedStatus = new Missing,
        public array|Missing $domains = new Missing,
        public Status|Missing $status = new Missing,
        public string|Missing|null $statusReason = new Missing,
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

    protected static function prepare(array $validated): array
    {
        return self::prepareStatuses($validated, [
            'provisioned_status' => Statuses::user(...),
            'status' => Statuses::organization(...),
        ]);
    }
}
