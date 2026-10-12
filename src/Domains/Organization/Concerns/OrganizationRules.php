<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Concerns;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationDomainModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;

/**
 * Validation shared by the Data objects that write an organization's
 * settings, and the domain sync shared by their Actions.
 */
trait OrganizationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected static function settingsRules(?OrganizationModel $organization = null): array
    {
        $domain = Rule::unique(OrganizationDomainModel::class, 'domain');

        if ($organization !== null) {
            $domain->where(fn (Builder $query): Builder => $query->where('organization_id', '!=', $organization->getKey()));
        }

        return [
            'auto_join' => ['sometimes', 'boolean'],
            // Accounts created through its SSO or SCIM: active, or waiting for approval.
            'provisioned_status' => ['sometimes', Rule::in([UserStatus::Active->value, UserStatus::Pending->value])],
            'domains' => ['sometimes', 'array', 'max:50'],
            'domains.*' => ['string', 'distinct', 'max:253', 'regex:/^(?!-)[a-z0-9-]+(\.[a-z0-9-]+)+$/i', $domain],
        ];
    }

    /**
     * Replace the organization's domains with the given list, lower-cased.
     *
     * @param  array<int, mixed>  $domains
     */
    private function syncDomains(OrganizationModel $organization, array $domains): void
    {
        $organization->domains()->delete();

        foreach (array_unique(array_map(fn (mixed $domain): string => strtolower((string) $domain), $domains)) as $domain) {
            $organization->domains()->create(['domain' => $domain]);
        }
    }
}
