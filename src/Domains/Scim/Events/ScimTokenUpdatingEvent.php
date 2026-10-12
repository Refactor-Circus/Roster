<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Scim\Models\ScimTokenModel;

/**
 * The ScimTokenModel `updating` Eloquent event.
 */
final class ScimTokenUpdatingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public ScimTokenModel $scimToken) {}

    public function model(): Model
    {
        return $this->scimToken;
    }

    public function hook(): string
    {
        return 'updating';
    }
}
