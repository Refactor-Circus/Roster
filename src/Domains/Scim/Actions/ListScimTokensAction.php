<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Scim\Data\ListScimTokensData;
use RefactorCircus\Roster\Domains\Scim\Events\ScimTokensListedActionEvent;
use RefactorCircus\Roster\Domains\Scim\Events\ScimTokensListingActionEvent;
use RefactorCircus\Roster\Domains\Scim\Models\ScimTokenModel;

final class ListScimTokensAction
{
    /**
     * @return LengthAwarePaginator<int, ScimTokenModel>
     */
    public function execute(OrganizationModel $organization, ListScimTokensData $filters = new ListScimTokensData): LengthAwarePaginator
    {
        ScimTokensListingActionEvent::dispatch($organization, $filters);

        $tokens = ScimTokenModel::query()
            ->where('organization_id', $organization->getKey())
            ->with(['organization', 'ssoConnection'])
            ->latest()
            ->latest('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);

        ScimTokensListedActionEvent::dispatch($organization, $filters);

        return $tokens;
    }
}
