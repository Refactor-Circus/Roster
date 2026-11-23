<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Database\Eloquent\Collection;
use RefactorCircus\Roster\Domains\Organization\Data\SearchOrganizationsData;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationsSearchedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationsSearchingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\PrefixSearch;

final class SearchOrganizationsAction
{
    /**
     * Up to `limit` organizations whose name or slug starts with `q`, by
     * name; deleted ones are left out. Matched through `PrefixSearch` (`%`,
     * `_` and `\` in `q` match themselves).
     *
     * @param  array<int, int|string>|null  $within  Only these organizations; null for no limit.
     * @return Collection<int, OrganizationModel>
     */
    public function execute(SearchOrganizationsData $data, ?array $within = null): Collection
    {
        OrganizationsSearchingActionEvent::dispatch($data);

        $query = OrganizationModel::query()->with(['links', 'owner']);

        if ($within !== null) {
            $query->whereIn('id', $within);
        }

        $organizations = PrefixSearch::apply($query, ['name', 'slug'], $data->q)
            ->orderBy('name')
            ->orderBy('id')
            ->limit($data->limit)
            ->get();

        OrganizationsSearchedActionEvent::dispatch($data);

        return $organizations;
    }
}
