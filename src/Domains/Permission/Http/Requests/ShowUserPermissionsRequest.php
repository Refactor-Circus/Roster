<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Permission\Actions\ListUserPermissionsAction;
use RefactorCircus\Roster\Domains\Permission\Data\ListUserPermissionsData;
use RefactorCircus\Roster\Domains\User\Http\Requests\UserRequest;

final class ShowUserPermissionsRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    public function rules(): array
    {
        return ListUserPermissionsData::rules();
    }

    public function persist(): JsonResponse
    {
        return response()->json(['data' => app(ListUserPermissionsAction::class)->execute($this->targetUser(), ListUserPermissionsData::fromValidated($this->validated()))]);
    }
}
