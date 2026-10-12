<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\Impersonation\Data\StopImpersonationData;
use RefactorCircus\Roster\Domains\Impersonation\Events\ImpersonationStoppedActionEvent;
use RefactorCircus\Roster\Domains\Impersonation\Events\ImpersonationStoppingActionEvent;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;

final class StopImpersonationAction
{
    /**
     * End an impersonation, or cancel an unused link. Ending an ended one
     * changes nothing. The impersonator's browser is switched back on its
     * next request.
     */
    public function execute(ImpersonationModel $impersonation, StopImpersonationData $data = new StopImpersonationData): ImpersonationModel
    {
        if ($impersonation->ended_at !== null) {
            return $impersonation;
        }

        $why = $data->why;

        ImpersonationStoppingActionEvent::dispatch($impersonation, $data);

        DB::transaction(fn () => $impersonation->update([
            'token_hash' => null,
            'ended_at' => now(),
            'end_reason' => $why,
        ]));

        ImpersonationStoppedActionEvent::dispatch($impersonation, $why);

        return $impersonation->load(['user', 'impersonator', 'organization']);
    }
}
