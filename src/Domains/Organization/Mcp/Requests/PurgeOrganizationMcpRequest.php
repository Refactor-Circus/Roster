<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Roster\Domains\Organization\Actions\PurgeOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * Works on deleted records too.
 */
final class PurgeOrganizationMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.purge';
    }

    protected function scope(): OrganizationModel
    {
        return $this->trashedOrganization();
    }

    protected function handle(array $validated): Response
    {
        app(PurgeOrganizationAction::class)->queue($this->trashedOrganization());

        return Response::text(__('roster::roster.organization_purge_queued'));
    }
}
