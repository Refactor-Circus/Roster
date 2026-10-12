<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Transfers\Flows\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Impex\Impex;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\Transfer\Events\TransferFinishedActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Events\TransferFinishingActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferRowModel;
use RefactorCircus\Roster\Domains\Transfer\Services\PlanCache;

/**
 * Fold each row's outcome back into the report.
 */
final class FinishImport
{
    /**
     * @return array<string, mixed>
     */
    public function execute(string $transfer, string $batch): array
    {
        $model = TransferModel::query()->findOrFail($transfer);
        TransferFinishingActionEvent::dispatch($model);

        DB::transaction(function () use ($model, $batch): void {
            // Rows record their own result as they apply; a row whose job failed
            // outright (after its retries) never did, so it's marked here.
            foreach (app(Impex::class)->batchItems($batch) as $item) {
                if (! is_array($item->result['value'] ?? null)) {
                    TransferRowModel::query()
                        ->where('transfer_id', $model->id)
                        ->where('line', (int) str_replace('line-', '', (string) $item->item_key))
                        ->update(['result' => 'error', 'result_reasons' => json_encode([(string) ($item->error['message'] ?? 'Failed.')])]);
                }
            }

            $results = TransferRowModel::query()
                ->where('transfer_id', $model->id)
                ->selectRaw("coalesce(result, 'error') as outcome, count(*) as total")
                ->groupBy('outcome')
                ->orderBy('outcome')
                ->pluck('total', 'outcome')
                ->map(fn (mixed $count): int => (int) $count)
                ->all();

            $model->update([
                'status' => TransferStatus::Completed,
                'finished_at' => now(),
                'report' => ['summary' => $model->summary(), 'results' => $results],
            ]);
        });

        app(PlanCache::class)->forget($model->id);
        TransferFinishedActionEvent::dispatch($model->refresh());

        return ['status' => 'completed'];
    }
}
