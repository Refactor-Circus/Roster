<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Actions\ShowOrganizationStatsAction;
use RefactorCircus\Roster\Domains\Organization\Http\Resources\OrganizationStatsResource;
use RefactorCircus\Roster\Mcp\Request;

final class ShowOrganizationStatsMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.view';
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new OrganizationStatsResource(app(ShowOrganizationStatsAction::class)->execute()))->toStructuredResponse();
    }
}
