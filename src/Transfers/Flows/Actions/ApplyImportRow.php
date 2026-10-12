<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Transfers\Flows\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Support\Surface;
use RefactorCircus\Roster\Domains\Transfer\Events\ImportRowAppliedActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Events\ImportRowApplyingActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferRowModel;
use RefactorCircus\Roster\Domains\Transfer\Services\PlanCache;
use RefactorCircus\Roster\Domains\Transfer\Services\Planners\Planner;
use RefactorCircus\Roster\Domains\Transfer\Services\TransferContext;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;

/**
 * Apply one row, as the person who confirmed the import, so every change is
 * attributed to them in the audit log.
 */
final class ApplyImportRow
{
    public function __construct(
        private readonly Auth $auth,
        private readonly TransferContext $context,
        private readonly Surface $surface,
    ) {}

    /**
     * @param  array{transfer: string, line: int, values: array<string, string>}  $item
     * @return array{line: int, action: string, reasons: array<int, string>}
     */
    public function execute(array $item): array
    {
        // Without the report: rows never need it, and it can be megabytes.
        $transfer = TransferModel::query()->withoutReport()->findOrFail($item['transfer']);
        $requester = app(PlanCache::class)->remember($transfer->id, 'requester', fn (): ?Model => $transfer->requester);
        $actor = $requester instanceof Model ? $requester : null;
        $guard = $this->auth->guard();
        $previous = $guard->user();

        if ($actor instanceof Authenticatable) {
            $guard->setUser($actor);
        }

        $this->context->transfer = $transfer;

        // Both events fire as the importer, like the changes the row makes.
        try {
            ImportRowApplyingActionEvent::dispatch($transfer, $item['line'], $item['values']);

            // The row's changes and its recorded result commit together.
            $outcome = DB::transaction(function () use ($item, $transfer, $actor): array {
                try {
                    $outcome = $this->surface->using('import', fn (): array => app(Transfers::class)->planner($transfer)->apply($item['values'], $transfer, $actor));
                } catch (ValidationException $exception) {
                    $outcome = ['action' => Planner::ERROR, 'reasons' => [(string) collect($exception->errors())->flatten()->first()]];
                }

                // Each row records its own result; retries just write it again.
                TransferRowModel::query()
                    ->where('transfer_id', $transfer->id)
                    ->where('line', $item['line'])
                    ->update(['result' => $outcome['action'], 'result_reasons' => json_encode($outcome['reasons'])]);

                return $outcome;
            });

            ImportRowAppliedActionEvent::dispatch($transfer, $item['line'], $outcome);
        } finally {
            $this->context->transfer = null;

            if ($previous instanceof Authenticatable) {
                $guard->setUser($previous);
            } elseif (method_exists($guard, 'forgetUser')) {
                $guard->forgetUser();
            }
        }

        return ['line' => $item['line']] + $outcome;
    }
}
