<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;

/**
 * The ProfileModel `updating` Eloquent event.
 */
final class ProfileUpdatingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public ProfileModel $profile) {}

    public function model(): Model
    {
        return $this->profile;
    }

    public function hook(): string
    {
        return 'updating';
    }
}
