<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Transfers\Flows\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\Transfer\Events\ImportValidatedActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Events\ImportValidatingActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferRowModel;
use RefactorCircus\Roster\Domains\Transfer\Services\Planners\Planner;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;
use RefactorCircus\Roster\Domains\Transfer\Support\Csv\Reader;

/**
 * Check the file and plan every row. Changes nothing but the report.
 */
final class ValidateImport
{
    /**
     * @return array{valid: bool, expires_at?: string}
     */
    public function execute(string $transfer): array
    {
        $model = TransferModel::query()->findOrFail($transfer);
        ImportValidatingActionEvent::dispatch($model);

        $transfers = app(Transfers::class);
        $reader = new Reader($transfers->disk(), (string) $model->input_path, $model->type->columns());

        try {
            $count = $reader->validate();
        } catch (ValidationException $exception) {
            DB::transaction(fn (): bool => $model->update([
                'status' => TransferStatus::Failed,
                'finished_at' => now(),
                'report' => ['error' => (string) collect($exception->errors())->flatten()->first(), 'summary' => []],
            ]));

            ImportValidatedActionEvent::dispatch($model);

            return ['valid' => false];
        }

        $planner = $transfers->planner($model);
        $requester = $model->requester;
        $actor = $requester instanceof Model ? $requester : null;
        $expires = now()->addHours((int) config('roster.transfers.confirm_within_hours', 24));

        // Each batch of planned rows commits on its own, so a large file never
        // holds one long write lock while it is planned. The transfer only
        // becomes confirmable with the final report, and a rerun starts over
        // from an empty set, so a half-written plan is never acted on.
        DB::transaction(fn (): mixed => $model->lines()->delete());

        $summary = [];
        $rows = [];

        // Rows are planned 500 at a time: the planner looks up what the
        // batch needs in a few queries, then plans each row from that.
        foreach ($reader->rows() as $row) {
            $rows[] = $row;

            if (count($rows) === 500) {
                $summary = $this->plan($rows, $planner, $model, $actor, $summary);
                $rows = [];
            }
        }

        if ($rows !== []) {
            $summary = $this->plan($rows, $planner, $model, $actor, $summary);
        }

        ksort($summary);

        DB::transaction(fn (): bool => $model->update([
            'status' => TransferStatus::AwaitingConfirmation,
            'row_count' => $count,
            'expires_at' => $expires,
            'report' => ['summary' => $summary],
        ]));

        ImportValidatedActionEvent::dispatch($model);

        return ['valid' => true, 'expires_at' => $expires->toIso8601String()];
    }

    /**
     * Plan a batch of rows and store the plans; returns the running summary.
     *
     * @param  array<int, array{line: int, offset: int, values: array<string, string>}>  $rows
     * @param  array<string, int>  $summary
     * @return array<string, int>
     */
    private function plan(array $rows, Planner $planner, TransferModel $model, ?Model $actor, array $summary): array
    {
        $planner->prefetch(array_column($rows, 'values'), $model);
        $batch = [];

        foreach ($rows as $row) {
            $plan = $planner->plan($row['values'], $model, $actor);
            $summary[$plan['action']] = ($summary[$plan['action']] ?? 0) + 1;
            $batch[] = [
                'transfer_id' => $model->id,
                'line' => $row['line'],
                'values' => (string) json_encode($row['values']),
                'action' => $plan['action'],
                'reasons' => (string) json_encode($plan['reasons']),
            ];
        }

        DB::transaction(fn (): bool => TransferRowModel::query()->insert($batch));

        return $summary;
    }
}
