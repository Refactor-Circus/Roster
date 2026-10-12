<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use RefactorCircus\Roster\Domains\Organization\Events\OrganizationShowingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationShownActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class ShowOrganizationAction
{
    public function execute(OrganizationModel $organization): OrganizationModel
    {
        OrganizationShowingActionEvent::dispatch($organization);

        $organization->load(['domains', 'links', 'owner'])->loadCount(['memberships', 'teams']);

        OrganizationShownActionEvent::dispatch($organization);

        return $organization;
    }
}
