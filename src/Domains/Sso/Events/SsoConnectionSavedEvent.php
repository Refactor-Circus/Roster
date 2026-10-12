<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;

/**
 * The SsoConnectionModel `saved` Eloquent event.
 */
final class SsoConnectionSavedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public SsoConnectionModel $ssoConnection) {}

    public function model(): Model
    {
        return $this->ssoConnection;
    }

    public function hook(): string
    {
        return 'saved';
    }
}
