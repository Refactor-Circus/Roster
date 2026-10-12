<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use LogicException;
use RefactorCircus\Impex\Domains\Batch\Models\BatchModel;
use RefactorCircus\Impex\Impex;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Domains\Transfer\Exceptions\TransfersUnavailableException;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Services\Exporters\Exporter;
use RefactorCircus\Roster\Domains\Transfer\Services\Exporters\MembersExporter;
use RefactorCircus\Roster\Domains\Transfer\Services\Exporters\OrganizationsExporter;
use RefactorCircus\Roster\Domains\Transfer\Services\Exporters\UsersExporter;
use RefactorCircus\Roster\Domains\Transfer\Services\Planners\MembersPlanner;
use RefactorCircus\Roster\Domains\Transfer\Services\Planners\OrganizationsPlanner;
use RefactorCircus\Roster\Domains\Transfer\Services\Planners\Planner;
use RefactorCircus\Roster\Domains\Transfer\Services\Planners\TeamsPlanner;
use RefactorCircus\Roster\Domains\Transfer\Services\Planners\UsersPlanner;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV import and export, run as Impex flows. Impex is optional.
 */
class Transfers
{
    public const string IMPORT_FLOW = 'roster:import';

    public const string EXPORT_FLOW = 'roster:export';

    public function available(): bool
    {
        return class_exists(Impex::class);
    }

    public function ensureAvailable(): void
    {
        if (! $this->available()) {
            throw TransfersUnavailableException::make();
        }
    }

    public function planner(TransferModel $transfer): Planner
    {
        return match ($transfer->type) {
            TransferType::ImportMembers => app(MembersPlanner::class),
            TransferType::ImportUsers => app(UsersPlanner::class),
            TransferType::ImportTeams => app(TeamsPlanner::class),
            TransferType::ImportOrganizations => app(OrganizationsPlanner::class),
            default => throw new LogicException("[{$transfer->type->value}] is not an import."),
        };
    }

    public function exporter(TransferModel $transfer): Exporter
    {
        return match ($transfer->type) {
            TransferType::ExportMembers => app(MembersExporter::class),
            TransferType::ExportUsers => app(UsersExporter::class),
            TransferType::ExportOrganizations => app(OrganizationsExporter::class),
            default => throw new LogicException("[{$transfer->type->value}] is not an export."),
        };
    }

    public function disk(): Filesystem
    {
        return Storage::disk((string) config('roster.transfers.disk', 'local'));
    }

    /**
     * How far a running import has got: rows applied (or failed) of the
     * total, from Impex's batch counters. Null before rows are applied.
     *
     * @return array{done: int, total: int}|null
     */
    public function progress(TransferModel $transfer): ?array
    {
        if (! $this->available() || $transfer->impex_run_id === null) {
            return null;
        }

        $batch = $transfer->relationLoaded('impexBatch')
            ? $transfer->getRelation('impexBatch')
            : BatchModel::query()->where('run_id', $transfer->impex_run_id)->latest()->first();

        if (! $batch instanceof BatchModel) {
            return null;
        }

        return ['done' => $batch->succeeded + $batch->failed, 'total' => $batch->total];
    }

    /**
     * Load the progress of many transfers in one query.
     *
     * @param  iterable<int, TransferModel>  $transfers
     */
    public function preloadProgress(iterable $transfers): void
    {
        $transfers = collect($transfers)->filter(fn (TransferModel $transfer): bool => $transfer->impex_run_id !== null);

        if (! $this->available() || $transfers->isEmpty()) {
            return;
        }

        $batches = BatchModel::query()
            ->whereIn('run_id', $transfers->pluck('impex_run_id')->all())
            ->orderBy('created_at')
            ->get()
            ->keyBy('run_id');

        $transfers->each(fn (TransferModel $transfer) => $transfer->setRelation('impexBatch', $batches->get($transfer->impex_run_id)));
    }

    /**
     * The CSV template for an import type: the app's published copy
     * (`resources/roster/import-templates`) when there is one, otherwise
     * Roster's own. Templates hold no data, only the header and commented
     * example rows.
     */
    public function template(TransferType $type): string
    {
        if (! $type->isImport()) {
            throw new LogicException("[{$type->value}] is not an import.");
        }

        $published = resource_path('roster/import-templates/'.$type->value.'.csv');

        return (string) file_get_contents(is_file($published) ? $published : dirname(__DIR__, 4).'/resources/import-templates/'.$type->value.'.csv');
    }

    /**
     * Whether a finished export's file is still there to download.
     */
    public function downloadable(TransferModel $transfer, bool $checkFile = true): bool
    {
        return ! $transfer->type->isImport()
            && $transfer->status === TransferStatus::Completed
            && $transfer->output_path !== null
            && (! $checkFile || $this->disk()->exists($transfer->output_path));
    }

    /**
     * Stream an export's CSV. Callers authorize first.
     */
    public function download(TransferModel $transfer): StreamedResponse
    {
        abort_unless($this->downloadable($transfer), 404);

        $name = Str::slug($transfer->type->value.'-'.($transfer->organization->slug ?? 'all').'-'.$transfer->created_at?->format('Y-m-d-His')).'.csv';

        return $this->disk()->download((string) $transfer->output_path, $name, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * A short-lived signed link to an export, for MCP clients that can't send
     * the API's credentials. SECURITY: whoever holds it can download until it
     * expires.
     */
    public function signedDownloadUrl(TransferModel $transfer): ?string
    {
        if (! $this->downloadable($transfer) || ! Route::has('roster.transfers.file')) {
            return null;
        }

        return URL::temporarySignedRoute('roster.transfers.file', now()->addMinutes(15), ['transfer' => $transfer->id]);
    }

    /**
     * The organization a transfer's permission is checked in: the
     * organization for organization-wide types, otherwise none (global).
     */
    public static function scope(TransferType $type, ?OrganizationModel $organization): ?OrganizationModel
    {
        return $type->needsOrganization() ? $organization : null;
    }

    /**
     * Whose transfers a list shows the actor, as ListTransfersAction's
     * `requester` and `organizations` arguments: everyone's within a named
     * organization or with `roster.users.view`; otherwise their own, plus
     * those of the organizations they may see members in.
     *
     * @return array{0: Model|null, 1: array<int, int|string>}
     */
    public function visibleTo(?Model $actor, ?OrganizationModel $organization): array
    {
        $authorizer = app(Authorizer::class);

        if ($organization !== null || $authorizer->check($actor, 'roster.users.view')) {
            return [null, []];
        }

        return [$actor, $authorizer->organizationsWith($actor, 'roster.members.view') ?? []];
    }
}
