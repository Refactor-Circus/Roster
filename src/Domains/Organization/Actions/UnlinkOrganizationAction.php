<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationUnlinkedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationUnlinkingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class UnlinkOrganizationAction
{
    /**
     * Forget the organization's record in one external system. The next sync
     * of that record creates a new organization unless it names this one.
     */
    public function execute(OrganizationModel $organization, string $source): OrganizationModel
    {
        $link = $organization->links()->where('source', $source)->first()
            ?? throw ValidationException::withMessages(['source' => __('roster::roster.not_linked', ['source' => $source])]);

        OrganizationUnlinkingActionEvent::dispatch($organization, $source);

        DB::transaction(fn () => $link->delete());

        $organization = $organization->refresh()->load(['domains', 'links'])->loadCount(['memberships', 'teams']);

        OrganizationUnlinkedActionEvent::dispatch($organization, $source);

        return $organization;
    }
}
