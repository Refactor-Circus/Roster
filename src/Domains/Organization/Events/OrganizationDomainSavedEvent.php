<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationDomainModel;

/**
 * The OrganizationDomainModel `saved` Eloquent event.
 */
final class OrganizationDomainSavedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public OrganizationDomainModel $organizationDomain) {}

    public function model(): Model
    {
        return $this->organizationDomain;
    }

    public function hook(): string
    {
        return 'saved';
    }
}
