<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Impex\Impex;
use RefactorCircus\Roster\Domains\Transfer\Data\StartExportData;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Domains\Transfer\Events\TransferStartedActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Events\TransferStartingActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;
use RefactorCircus\Roster\Support\Concerns\ResolvesScopes;

final class StartExportAction
{
    use ResolvesScopes;

    public function __construct(private readonly Transfers $transfers) {}

    /**
     * Build a CSV export in the background.
     */
    public function execute(StartExportData $data, Model $actor): TransferModel
    {
        $this->transfers->ensureAvailable();

        $type = $data->type;
        $organization = $this->organizationFrom($data->organization);

        if ($type->needsOrganization() && $organization === null) {
            throw ValidationException::withMessages(['organization' => __('roster::roster.transfer_needs_organization')]);
        }

        TransferStartingActionEvent::dispatch($data);

        $transfer = DB::transaction(fn (): TransferModel => TransferModel::query()->create([
            'type' => $type,
            'organization_id' => Transfers::scope($type, $organization)?->getKey(),
            'requested_by' => $actor->getKey(),
            'status' => TransferStatus::Running,
            'filters' => match ($type) {
                TransferType::ExportOrganizations => $data->filters->toArray(),
                default => null,
            },
        ]));

        $run = app(Impex::class)->run(
            slug: Transfers::EXPORT_FLOW,
            arguments: [$transfer->id],
            tags: ['roster_transfer' => $transfer->id],
            owners: array_filter(['requester' => $actor, 'organization' => $organization]),
        );

        $transfer->update(['impex_run_id' => (string) $run->getKey()]);
        $transfer = $transfer->refresh();

        TransferStartedActionEvent::dispatch($transfer);

        return $transfer;
    }
}
