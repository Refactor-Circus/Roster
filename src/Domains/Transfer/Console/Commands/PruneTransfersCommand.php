<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;

final class PruneTransfersCommand extends Command
{
    protected $signature = 'roster:prune-transfers {--days= : Keep this many days instead of roster.transfers.retention_days}';

    protected $description = 'Delete the files of old imports and exports';

    public function handle(Transfers $transfers): int
    {
        $option = $this->option('days');
        $days = is_numeric($option) ? (int) $option : (int) config('roster.transfers.retention_days', 7);
        $pruned = 0;

        TransferModel::query()
            ->where('created_at', '<', now()->subDays($days))
            ->where(fn (Builder $query): Builder => $query->whereNotNull('input_path')->orWhereNotNull('output_path'))
            // By id, not offset: clearing the paths drops each row from the set being paged.
            ->eachById(function (TransferModel $transfer) use ($transfers, &$pruned): void {
                $transfers->disk()->deleteDirectory('roster/transfers/'.$transfer->id);
                // The rows hold the imported values (emails, names) too.
                $transfer->lines()->delete();
                $transfer->update(['input_path' => null, 'output_path' => null]);
                $pruned++;
            });

        $this->components->info("Deleted the files of {$pruned} transfers older than {$days} days.");

        return self::SUCCESS;
    }
}
