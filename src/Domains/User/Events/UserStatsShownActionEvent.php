<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Contracts\ReadActionEvent;

/**
 * The user counts per status have been shown.
 */
final class UserStatsShownActionEvent implements ActionFinishedEvent, ReadActionEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array{total: int, by_status: array<string, int>}  $stats
     */
    public function __construct(
        public array $stats,
    ) {}
}
