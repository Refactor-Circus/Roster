<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Impex\Impex;
use RefactorCircus\Roster\Domains\Transfer\Data\StartImportData;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\Transfer\Events\TransferStartedActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Events\TransferStartingActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;
use RefactorCircus\Roster\Support\Concerns\ResolvesScopes;

final class StartImportAction
{
    use ResolvesScopes;

    public function __construct(private readonly Transfers $transfers) {}

    /**
     * Store the CSV and validate it. Nothing changes until the preview is
     * confirmed with ConfirmImportAction.
     */
    public function execute(StartImportData $data, Model $actor): TransferModel
    {
        $this->transfers->ensureAvailable();

        $type = $data->type;
        $organization = $this->organizationFrom($data->organization);

        if ($type->needsOrganization() && $organization === null) {
            throw ValidationException::withMessages(['organization' => __('roster::roster.transfer_needs_organization')]);
        }

        // The event carries what was asked for, not the CSV itself.
        TransferStartingActionEvent::dispatch(new StartImportData(type: $type, organization: $data->organization));

        $transfer = DB::transaction(function () use ($type, $organization, $actor, $data): TransferModel {
            $transfer = TransferModel::query()->create([
                'type' => $type,
                'organization_id' => Transfers::scope($type, $organization)?->getKey(),
                'requested_by' => $actor->getKey(),
                'status' => TransferStatus::Validating,
            ]);

            $path = 'roster/transfers/'.$transfer->id.'/import.csv';

            $data->file !== null
                ? $this->transfers->disk()->putFileAs(dirname($path), $data->file, basename($path))
                : $this->transfers->disk()->put($path, (string) $data->content);

            $transfer->update(['input_path' => $path]);

            return $transfer;
        });

        $run = app(Impex::class)->run(
            slug: Transfers::IMPORT_FLOW,
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
