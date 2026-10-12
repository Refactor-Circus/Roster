<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Roster\Domains\Organization\Concerns\OrganizationRules;
use RefactorCircus\Roster\Domains\Organization\Data\UpdateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationUpdatedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationUpdatingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;

final class UpdateOrganizationAction
{
    use OrganizationRules;

    /**
     * Update an organization. A given `domains` list replaces the old one.
     */
    public function execute(OrganizationModel $organization, UpdateOrganizationData $data): OrganizationModel
    {
        OrganizationUpdatingActionEvent::dispatch($organization, $data);

        DB::transaction(function () use ($organization, $data): void {
            $attributes = array_intersect_key($data->toArray(), array_flip(['name', 'slug', 'auto_join', 'provisioned_status']));

            if ($data->provisionedStatus instanceof UserStatus) {
                $attributes['provisioned_status'] = $data->provisionedStatus->value;
            }

            $organization->update($attributes);

            if (! $data->domains instanceof Missing) {
                $this->syncDomains($organization, $data->domains);
            }
        });

        $organization = $organization->refresh()->load(['domains', 'links'])->loadCount(['memberships', 'teams']);

        OrganizationUpdatedActionEvent::dispatch($organization);

        return $organization;
    }
}
