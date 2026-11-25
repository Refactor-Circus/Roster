<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\User\Actions\SearchUsersAction;
use RefactorCircus\Roster\Domains\User\Data\SearchUsersData;
use RefactorCircus\Roster\Domains\User\Resources\UserSummaryResource;
use RefactorCircus\Roster\Http\Request;

final class SearchUsersRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.users.view';
    }

    public function rules(): array
    {
        return SearchUsersData::rules();
    }

    public function persist(): JsonResponse
    {
        return UserSummaryResource::collection(app(SearchUsersAction::class)->execute(SearchUsersData::fromValidated($this->validated())))->response();
    }
}
