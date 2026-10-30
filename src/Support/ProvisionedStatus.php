<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Support;

use InvalidArgumentException;
use RefactorCircus\Roster\Contracts\AccountStatus;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * Resolves the status an organization's externally provisioned users (SSO
 * just-in-time accounts, SCIM) start with, against one user-status enum.
 * `Roster::provisionedStatus()` builds it from `roster.statuses.users`.
 */
final readonly class ProvisionedStatus
{
    /**
     * @param  class-string<AccountStatus>  $statuses  the app's user-status enum
     */
    public function __construct(private string $statuses) {}

    /**
     * The organization's `provisioned_status` as one of the statuses. No
     * stored value starts them active; a value that is not one of the
     * statuses starts them active too, and is reported, so provisioning
     * never fails and a bad value is never silent.
     */
    public function for(OrganizationModel $organization): AccountStatus
    {
        $stored = $organization->getAttribute('provisioned_status');

        if ($stored === null) {
            return $this->statuses::active();
        }

        $status = $stored instanceof AccountStatus ? $stored : $this->statuses::tryFrom(is_scalar($stored) ? (string) $stored : '');

        if ($status instanceof AccountStatus) {
            return $status;
        }

        $fallback = $this->statuses::active();

        report(new InvalidArgumentException(sprintf(
            'Organization [%s] has an invalid provisioned_status [%s]; its provisioned users start as [%s].',
            $organization->slug,
            is_scalar($stored) ? (string) $stored : get_debug_type($stored),
            $fallback->value,
        )));

        return $fallback;
    }
}
