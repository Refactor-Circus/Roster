<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StopImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Data\StopImpersonationData;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;

/**
 * Ends impersonations whose time ran out but that nobody ended: the
 * impersonator never came back (their next request would have ended it), or
 * the one-time link was never used. Each ends through StopImpersonationAction
 * as `expired`, so the audit log records it. Schedule it, e.g. every five
 * minutes.
 */
final class EndExpiredImpersonationsCommand extends Command
{
    protected $signature = 'roster:end-expired-impersonations';

    protected $description = 'End impersonations and unused impersonation links that have expired';

    public function handle(StopImpersonationAction $stop): int
    {
        $ended = 0;

        ImpersonationModel::query()
            ->whereNull('ended_at')
            ->where(fn (Builder $query): Builder => $query
                ->where(fn (Builder $started): Builder => $started->whereNotNull('started_at')->where('expires_at', '<=', now()))
                ->orWhere(fn (Builder $unused): Builder => $unused->whereNull('started_at')->where('link_expires_at', '<=', now())))
            // By id, not offset: ending each one drops it from the set being paged.
            ->eachById(function (ImpersonationModel $impersonation) use ($stop, &$ended): void {
                $stop->execute($impersonation, new StopImpersonationData(why: ImpersonationModel::ENDED_EXPIRED));
                $ended++;
            });

        $this->components->info("Ended {$ended} expired impersonations.");

        return self::SUCCESS;
    }
}
