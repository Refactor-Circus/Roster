<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Role\Http\Requests\IndexOrganizationRoleAssignmentsRequest;

final class OrganizationRoleAssignmentController
{
    public function index(IndexOrganizationRoleAssignmentsRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
