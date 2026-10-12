<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationLinkModel;

/**
 * The OrganizationLinkModel `creating` Eloquent event.
 */
final class OrganizationLinkCreatingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public OrganizationLinkModel $organizationLink) {}

    public function model(): Model
    {
        return $this->organizationLink;
    }

    public function hook(): string
    {
        return 'creating';
    }
}
