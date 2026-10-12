<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;

/**
 * The MembershipModel `saving` Eloquent event.
 */
final class MembershipSavingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public MembershipModel $membership) {}

    public function model(): Model
    {
        return $this->membership;
    }

    public function hook(): string
    {
        return 'saving';
    }
}
