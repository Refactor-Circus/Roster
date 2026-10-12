<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferRowModel;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;
use RefactorCircus\Roster\Domains\User\Resources\UserSummaryResource;

/**
 * A single transfer includes its rows, a page at a time (`rows_page`, 100
 * per page).
 *
 * @mixin TransferModel
 */
final class TransferResource extends JsonResource
{
    private bool $rows = false;

    private int $rowsPage = 1;

    /** @var LengthAwarePaginator<int, TransferRowModel>|null */
    private ?LengthAwarePaginator $rowsPaginator = null;

    private bool $signed = false;

    public function withRows(int $page = 1): self
    {
        $this->rows = true;
        $this->rowsPage = max(1, $page);

        return $this;
    }

    /**
     * Use a short-lived signed download link instead of the API route.
     */
    public function signed(): self
    {
        $this->signed = true;

        return $this;
    }

    /**
     * @return LengthAwarePaginator<int, TransferRowModel>
     */
    private function rowsPage(): LengthAwarePaginator
    {
        return $this->rowsPaginator ??= $this->resource->lines()->paginate(100, ['*'], 'rows_page', $this->rowsPage);
    }

    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->required(),
            'type' => $schema->string()->enum(array_column(TransferType::cases(), 'value'))->required(),
            'status' => $schema->string()->enum(array_column(TransferStatus::cases(), 'value'))->required(),
            'organization' => $schema->string()->nullable()->required(),
            'requested_by' => $schema->object(UserSummaryResource::schema($schema))->nullable()->required(),
            'row_count' => $schema->integer()->nullable()->required(),
            'progress' => $schema->object([
                'done' => $schema->integer()->required(),
                'total' => $schema->integer()->required(),
            ])->nullable()->description('Rows applied of the total, while an import runs.')->required(),
            'summary' => $schema->object()->description('Planned rows per outcome.')->required(),
            'results' => $schema->object()->description('Applied rows per outcome.')->required(),
            'error' => $schema->string()->nullable()->required(),
            'filters' => $schema->object()->nullable()->required(),
            'rows' => $schema->array()->items($schema->object([
                'line' => $schema->integer()->required(),
                'values' => $schema->object(),
                'action' => $schema->string(),
                'reasons' => $schema->array()->items($schema->string()),
                'result' => $schema->string(),
                'result_reasons' => $schema->array()->items($schema->string()),
            ]))->description('One page of rows, on a single transfer.'),
            'rows_meta' => $schema->object([
                'current_page' => $schema->integer()->required(),
                'last_page' => $schema->integer()->required(),
                'per_page' => $schema->integer()->required(),
                'total' => $schema->integer()->required(),
            ])->description('The page of rows, on a single transfer.'),
            'download_url' => $schema->string()->nullable()->required(),
            'expires_at' => $schema->string()->nullable()->required(),
            'confirmed_at' => $schema->string()->nullable()->required(),
            'finished_at' => $schema->string()->nullable()->required(),
            'created_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $requester = $this->requester;
        $transfers = app(Transfers::class);

        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'organization' => $this->organization?->slug,
            'requested_by' => $requester instanceof Model ? (new UserSummaryResource($requester))->resolve($request) : null,
            'row_count' => $this->row_count,
            'progress' => $transfers->progress($this->resource),
            'summary' => $this->summary(),
            'results' => (array) ($this->report['results'] ?? []),
            'error' => $this->report['error'] ?? null,
            'filters' => $this->filters,
            'rows' => $this->when($this->rows, fn (): array => array_map(fn (TransferRowModel $row): array => $row->toReport(), $this->rowsPage()->items())),
            'rows_meta' => $this->when($this->rows, function (): array {
                $page = $this->rowsPage();

                return ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()];
            }),
            'download_url' => match (true) {
                $this->signed => $transfers->signedDownloadUrl($this->resource),
                // Lists trust the record; only a single transfer checks the file
                // (one disk call per row is slow on S3).
                $transfers->downloadable($this->resource, checkFile: $this->rows) && Route::has('roster.transfers.download') => route('roster.transfers.download', $this->id),
                default => null,
            },
            'expires_at' => $this->expires_at?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
