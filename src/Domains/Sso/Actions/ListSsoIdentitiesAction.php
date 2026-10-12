<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use RefactorCircus\Roster\Domains\Sso\Data\ListSsoIdentitiesData;
use RefactorCircus\Roster\Domains\Sso\Events\SsoIdentitiesListedActionEvent;
use RefactorCircus\Roster\Domains\Sso\Events\SsoIdentitiesListingActionEvent;
use RefactorCircus\Roster\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Roster\Support\Concerns\ResolvesScopes;
use RefactorCircus\Roster\Support\Users;

final class ListSsoIdentitiesAction
{
    use ResolvesScopes;

    public function __construct(private readonly Users $users) {}

    /**
     * @return LengthAwarePaginator<int, SsoIdentityModel>
     */
    public function execute(ListSsoIdentitiesData $filters = new ListSsoIdentitiesData): LengthAwarePaginator
    {
        SsoIdentitiesListingActionEvent::dispatch($filters);

        $query = SsoIdentityModel::query()->with(['connection.organization', 'user']);

        if ($filters->user !== null) {
            $query->where('user_id', $this->users->resolve($filters->user)->getKey());
        }

        $organization = $this->organizationFrom($filters->organization);

        if ($organization !== null) {
            $query->whereHas('connection', fn (Builder $connection): Builder => $connection->where('organization_id', $organization->getKey()));
        }

        $identities = $query->latest()->latest('id')->paginate($filters->perPage, ['*'], 'page', $filters->page);

        SsoIdentitiesListedActionEvent::dispatch($filters);

        return $identities;
    }
}
