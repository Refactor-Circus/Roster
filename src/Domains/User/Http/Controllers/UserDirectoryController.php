<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\User\Http\Requests\SearchUsersRequest;
use RefactorCircus\Roster\Domains\User\Http\Requests\ShowUserStatsRequest;

/**
 * Questions about the user directory as a whole: how many users hold each
 * status, and a typeahead search by name or email.
 */
final class UserDirectoryController
{
    public function stats(ShowUserStatsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function search(SearchUsersRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
