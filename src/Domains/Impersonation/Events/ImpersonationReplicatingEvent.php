<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;

/**
 * The ImpersonationModel `replicating` Eloquent event.
 */
final class ImpersonationReplicatingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public ImpersonationModel $impersonation) {}

    public function model(): Model
    {
        return $this->impersonation;
    }

    public function hook(): string
    {
        return 'replicating';
    }
}
