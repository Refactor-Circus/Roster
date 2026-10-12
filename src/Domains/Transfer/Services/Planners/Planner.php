<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Services\Planners;

use Closure;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Services\PlanCache;

/**
 * Decides what one imported row would do, and does it.
 *
 * The same plan() runs for the preview and again right before apply(), so a
 * confirmed import acts on the state at that moment, not at upload.
 */
abstract class Planner
{
    public const string CREATE = 'create';

    public const string LINK = 'link';

    public const string INVITE = 'invite';

    public const string UPDATE = 'update';

    public const string SKIP = 'skip';

    public const string ERROR = 'error';

    /**
     * @param  array<string, string>  $values
     * @return array{action: string, reasons: array<int, string>}
     */
    abstract public function plan(array $values, TransferModel $transfer, ?Model $actor): array;

    /**
     * Look up what plan() needs about a batch of rows at once, before the
     * preview plans them one by one. Only the preview uses it: apply() always
     * plans against the state at that moment.
     *
     * @param  array<int, array<string, string>>  $rows
     */
    public function prefetch(array $rows, TransferModel $transfer): void {}

    /**
     * Apply the row; returns the action actually taken and why.
     *
     * @param  array<string, string>  $values
     * @return array{action: string, reasons: array<int, string>}
     */
    abstract public function apply(array $values, TransferModel $transfer, ?Model $actor): array;

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $resolve
     * @return TValue
     */
    protected function remember(TransferModel $transfer, string $key, Closure $resolve): mixed
    {
        return app(PlanCache::class)->remember($transfer->id, $key, $resolve);
    }

    /**
     * @return array<int, string>
     */
    protected function list(string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[;,|]/', $value) ?: [])));
    }

    /**
     * @return array{action: string, reasons: array<int, string>}
     */
    protected function outcome(string $action, string ...$reasons): array
    {
        return ['action' => $action, 'reasons' => array_values($reasons)];
    }
}
