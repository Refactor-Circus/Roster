<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;

/**
 * The SsoConnectionModel `updating` Eloquent event.
 */
final class SsoConnectionUpdatingEvent implements ModelLifecycleEvent
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
        return 'updating';
    }
}
