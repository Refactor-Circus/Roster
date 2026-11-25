<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\SearchOrganizationsRequest;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\ShowOrganizationStatsRequest;

/**
 * Questions about the organizations as a whole: how many organizations,
 * teams and pending invitations there are, and a typeahead search by name
 * or slug.
 */
final class OrganizationDirectoryController
{
    public function stats(ShowOrganizationStatsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function search(SearchOrganizationsRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
