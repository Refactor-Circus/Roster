<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Contracts\ReadActionEvent;

/**
 * The organization, team and pending invitation counts have been shown.
 */
final class OrganizationStatsShownActionEvent implements ActionFinishedEvent, ReadActionEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array{organizations: int, teams: int, pending_invitations: int}  $stats
     */
    public function __construct(
        public array $stats,
    ) {}
}
