<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use RefactorCircus\Roster\Domains\Organization\Data\ListOrganizationsData;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationsListedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationsListingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Users;

final class ListOrganizationsAction
{
    public function __construct(private readonly Users $users) {}

    /**
     * @param  array<int, int|string>|null  $organizations  Only these organizations; null for no limit.
     * @return LengthAwarePaginator<int, OrganizationModel>
     */
    public function execute(ListOrganizationsData $filters = new ListOrganizationsData, ?array $organizations = null): LengthAwarePaginator
    {
        OrganizationsListingActionEvent::dispatch($filters);

        $query = match ($filters->trashed) {
            'only' => OrganizationModel::onlyTrashed(),
            'with' => OrganizationModel::withTrashed(),
            default => OrganizationModel::query(),
        };
        $query->with(['domains', 'links', 'owner'])->withCount(['memberships', 'teams']);

        if ($organizations !== null) {
            $query->whereIn('id', $organizations);
        }

        $search = $filters->search;

        if ($search !== null && $search !== '') {
            $query->where(fn (Builder $builder): Builder => $builder
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('slug', 'like', '%'.$search.'%'));
        }

        if ($filters->user !== null) {
            $user = $this->users->resolve($filters->user);

            $query->whereIn('id', MembershipModel::query()->select('organization_id')->where('user_id', $user->getKey()));
        }

        // Find organizations by their records in external systems.
        $link = array_filter([
            'source' => $filters->source,
            'external_id' => $filters->externalId,
            'account_number' => $filters->accountNumber,
        ], fn (?string $value): bool => $value !== null && $value !== '');

        if ($link !== []) {
            $query->whereHas('links', fn (Builder $builder): Builder => $builder->where($link));
        }

        $organizations = $query->orderBy('name')->orderBy('id')->paginate($filters->perPage, ['*'], 'page', $filters->page);

        OrganizationsListedActionEvent::dispatch($filters);

        return $organizations;
    }
}
