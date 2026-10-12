<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Transfers\Flows\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Impex\Domains\Flow\Support\ResumableAction;
use RefactorCircus\Roster\Domains\Transfer\Events\ExportBuildingActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Events\ExportBuiltActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;
use RefactorCircus\Roster\Domains\Transfer\Support\Csv\Writer;

/**
 * Write the export in chunks, yielding when the step's time runs short and
 * resuming from the last row written.
 */
final class BuildExport extends ResumableAction
{
    public function execute(string $transfer): mixed
    {
        $model = TransferModel::query()->findOrFail($transfer);
        $transfers = app(Transfers::class);
        $exporter = $transfers->exporter($model);
        $disk = $transfers->disk();
        $path = 'roster/transfers/'.$model->id.'/export.csv';
        $state = $this->cursor();
        [$after, $written] = $state === null ? [null, 0] : [explode('|', $state, 2)[1] ?: null, (int) explode('|', $state, 2)[0]];

        // A resumed step carries on the export it already announced.
        if ($state === null) {
            ExportBuildingActionEvent::dispatch($model);
            $disk->put($path, '');
            DB::transaction(fn (): bool => $model->update(['output_path' => $path]));
        }

        $writer = new Writer($disk->path($path), append: true);

        if ($state === null) {
            $writer->write($exporter->headers());
        }

        try {
            while (true) {
                $rows = $exporter->rows($model, $after, (int) config('roster.transfers.chunk', 200));

                if ($rows === []) {
                    ExportBuiltActionEvent::dispatch($model, $written);

                    return ['rows' => $written];
                }

                foreach ($rows as $row) {
                    $writer->write($row['cells']);
                    $after = $row['cursor'];
                    $written++;
                }

                if ($this->shouldYield()) {
                    return $this->yieldTo($written.'|'.$after);
                }
            }
        } finally {
            $writer->close();
        }
    }
}
