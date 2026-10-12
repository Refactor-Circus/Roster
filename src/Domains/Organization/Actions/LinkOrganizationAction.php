<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Roster\Domains\Organization\Data\LinkOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationLinkedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationLinkingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationLinkModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class LinkOrganizationAction
{
    /**
     * Record (or change) the organization's id and account number in one
     * external system. An id already linked to another organization is
     * refused.
     */
    public function execute(OrganizationModel $organization, LinkOrganizationData $data): OrganizationLinkModel
    {
        $source = $data->source;
        $externalId = $data->externalId;

        $taken = OrganizationLinkModel::query()
            ->where('source', $source)
            ->where('external_id', $externalId)
            ->where('organization_id', '!=', $organization->getKey())
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['external_id' => __('roster::roster.external_id_taken', ['source' => $source])]);
        }

        OrganizationLinkingActionEvent::dispatch($organization, $data);

        $link = DB::transaction(fn (): OrganizationLinkModel => OrganizationLinkModel::query()->updateOrCreate(
            ['organization_id' => $organization->getKey(), 'source' => $source],
            ['external_id' => $externalId] + ($data->accountNumber instanceof Missing ? [] : ['account_number' => $data->accountNumber]),
        ));

        OrganizationLinkedActionEvent::dispatch($organization, $source, $externalId, $link->account_number);

        return $link;
    }
}
