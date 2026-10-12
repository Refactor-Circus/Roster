<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Sso\Models\SsoIdentityModel;

/**
 * The SsoIdentityModel `created` Eloquent event.
 */
final class SsoIdentityCreatedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public SsoIdentityModel $ssoIdentity) {}

    public function model(): Model
    {
        return $this->ssoIdentity;
    }

    public function hook(): string
    {
        return 'created';
    }
}
