<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RefactorCircus\Roster\Domains\Sso\Data\ListSsoConnectionsData;
use RefactorCircus\Roster\Domains\Sso\Events\SsoConnectionsListedActionEvent;
use RefactorCircus\Roster\Domains\Sso\Events\SsoConnectionsListingActionEvent;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Roster\Support\Concerns\ResolvesScopes;

final class ListSsoConnectionsAction
{
    use ResolvesScopes;

    /**
     * @return LengthAwarePaginator<int, SsoConnectionModel>
     */
    public function execute(ListSsoConnectionsData $filters = new ListSsoConnectionsData): LengthAwarePaginator
    {
        SsoConnectionsListingActionEvent::dispatch($filters);

        $query = SsoConnectionModel::query()->with('organization')->withCount('identities');
        $organization = $this->organizationFrom($filters->organization);

        if ($organization !== null) {
            $query->where('organization_id', $organization->getKey());
        }

        $connections = $query->orderBy('name')->orderBy('id')->paginate($filters->perPage, ['*'], 'page', $filters->page);

        SsoConnectionsListedActionEvent::dispatch($filters);

        return $connections;
    }
}
