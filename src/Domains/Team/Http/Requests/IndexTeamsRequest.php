<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use RefactorCircus\Roster\Domains\Team\Actions\ListTeamsAction;
use RefactorCircus\Roster\Domains\Team\Data\ListTeamsData;
use RefactorCircus\Roster\Domains\Team\Resources\TeamResource;

final class IndexTeamsRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.teams.view';
    }

    public function rules(): array
    {
        return ListTeamsData::rules();
    }

    public function persist(): JsonResponse
    {
        return TeamResource::collection(app(ListTeamsAction::class)->execute($this->organization(), ListTeamsData::fromValidated($this->validated())))->response();
    }
}
