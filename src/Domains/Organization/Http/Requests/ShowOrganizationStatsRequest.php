<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Actions\ShowOrganizationStatsAction;
use RefactorCircus\Roster\Domains\Organization\Http\Resources\OrganizationStatsResource;
use RefactorCircus\Roster\Http\Request;

final class ShowOrganizationStatsRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.view';
    }

    public function persist(): JsonResponse
    {
        return (new OrganizationStatsResource(app(ShowOrganizationStatsAction::class)->execute()))->response();
    }
}
