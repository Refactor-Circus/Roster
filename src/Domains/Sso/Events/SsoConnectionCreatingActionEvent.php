<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Sso\Data\CreateSsoConnectionData;

/**
 * An SSO connection is about to be created.
 */
final class SsoConnectionCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OrganizationModel $organization,
        public CreateSsoConnectionData $data,
    ) {}
}
