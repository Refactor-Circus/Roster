<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * The OrganizationModel `forceDeleting` Eloquent event.
 */
final class OrganizationForceDeletingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public OrganizationModel $organization) {}

    public function model(): Model
    {
        return $this->organization;
    }

    public function hook(): string
    {
        return 'forceDeleting';
    }
}
