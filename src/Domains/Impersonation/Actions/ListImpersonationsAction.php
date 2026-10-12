<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RefactorCircus\Roster\Domains\Impersonation\Data\ListImpersonationsData;
use RefactorCircus\Roster\Domains\Impersonation\Events\ImpersonationsListedActionEvent;
use RefactorCircus\Roster\Domains\Impersonation\Events\ImpersonationsListingActionEvent;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;
use RefactorCircus\Roster\Support\Concerns\ResolvesScopes;
use RefactorCircus\Roster\Support\Users;

final class ListImpersonationsAction
{
    use ResolvesScopes;

    public function __construct(private readonly Users $users) {}

    /**
     * Newest first.
     *
     * @param  array<int, int|string>|null  $organizations  Only these organizations; null for no limit.
     * @return LengthAwarePaginator<int, ImpersonationModel>
     */
    public function execute(ListImpersonationsData $filters = new ListImpersonationsData, ?array $organizations = null): LengthAwarePaginator
    {
        ImpersonationsListingActionEvent::dispatch($filters);

        $query = ImpersonationModel::query()->with(['user', 'impersonator', 'organization']);

        if ($filters->active) {
            $query->active();
        }

        foreach (['user_id' => $filters->user, 'impersonator_id' => $filters->impersonator] as $column => $user) {
            if ($user !== null) {
                $query->where($column, $this->users->findOrFail($user)->getKey());
            }
        }

        $organization = $this->organizationFrom($filters->organization);

        if ($organization !== null) {
            $query->where('organization_id', $organization->getKey());
        }

        if ($organizations !== null) {
            $query->whereIn('organization_id', $organizations);
        }

        $impersonations = $query->latest()->latest('id')->paginate($filters->perPage, ['*'], 'page', $filters->page);

        ImpersonationsListedActionEvent::dispatch($filters);

        return $impersonations;
    }
}
