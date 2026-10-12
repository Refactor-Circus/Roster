<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Actions\PurgeOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * Works on deleted organizations too.
 */
final class PurgeOrganizationRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.purge';
    }

    protected function scope(): OrganizationModel
    {
        return $this->trashedOrganization();
    }

    /**
     * 202: the organization is purged by a queued job, shortly.
     */
    public function persist(): JsonResponse
    {
        app(PurgeOrganizationAction::class)->queue($this->trashedOrganization());

        return new JsonResponse(['message' => __('roster::roster.organization_purge_queued')], 202);
    }
}
