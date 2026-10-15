<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Roster\Contracts\AccountStatus;
use RefactorCircus\Roster\Contracts\Status;
use RefactorCircus\Roster\Domains\Organization\Concerns\OrganizationRules;
use RefactorCircus\Roster\Domains\Organization\Data\UpdateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationUpdatedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationUpdatingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class UpdateOrganizationAction
{
    use OrganizationRules;

    /**
     * Update an organization. A given `domains` list replaces the old one;
     * a new `status` stamps `status_changed_at`.
     */
    public function execute(OrganizationModel $organization, UpdateOrganizationData $data): OrganizationModel
    {
        OrganizationUpdatingActionEvent::dispatch($organization, $data);

        DB::transaction(function () use ($organization, $data): void {
            $attributes = array_intersect_key($data->toArray(), array_flip(['name', 'slug', 'auto_join', 'provisioned_status', 'status', 'status_reason']));

            if ($data->provisionedStatus instanceof AccountStatus) {
                $attributes['provisioned_status'] = $data->provisionedStatus->value;
            }

            if ($data->status instanceof Status && $data->status !== $organization->status) {
                $attributes['status_changed_at'] = now();
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
