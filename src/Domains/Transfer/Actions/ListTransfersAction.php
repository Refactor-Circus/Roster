<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Transfer\Data\ListTransfersData;
use RefactorCircus\Roster\Domains\Transfer\Events\TransfersListedActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Events\TransfersListingActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;
use RefactorCircus\Roster\Support\Concerns\ResolvesScopes;

final class ListTransfersAction
{
    use ResolvesScopes;

    /**
     * Newest first. With an organization, its transfers; otherwise only the
     * requester's own (when one is given), plus those of `$organizations`.
     *
     * @param  array<int, int|string>  $organizations
     * @return LengthAwarePaginator<int, TransferModel>
     */
    public function execute(ListTransfersData $filters = new ListTransfersData, ?Model $requester = null, array $organizations = []): LengthAwarePaginator
    {
        TransfersListingActionEvent::dispatch($filters);

        $query = TransferModel::query()->with(['organization', 'requester']);
        $organization = $this->organizationFrom($filters->organization);

        if ($organization !== null) {
            $query->where('organization_id', $organization->getKey());
        } elseif ($requester !== null) {
            // Their own, and those of the organizations they may see transfers in.
            $query->where(fn (Builder $builder): Builder => $builder
                ->where('requested_by', $requester->getKey())
                ->when($organizations !== [], fn (Builder $within): Builder => $within->orWhereIn('organization_id', $organizations)));
        }

        if ($filters->type !== null) {
            $query->where('type', $filters->type->value);
        }

        if ($filters->status !== null) {
            $query->where('status', $filters->status->value);
        }

        $transfers = $query->latest()->latest('id')->paginate($filters->perPage, ['*'], 'page', $filters->page);

        // Progress for the whole page in one query rather than one per row.
        app(Transfers::class)->preloadProgress($transfers->getCollection());

        TransfersListedActionEvent::dispatch($filters);

        return $transfers;
    }
}
