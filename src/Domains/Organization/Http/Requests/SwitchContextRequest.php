<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Actions\SwitchContextAction;
use RefactorCircus\Roster\Domains\Organization\Data\SwitchContextData;
use RefactorCircus\Roster\Domains\User\Http\Requests\UserRequest;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

final class SwitchContextRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.update';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    public function rules(): array
    {
        return SwitchContextData::rules();
    }

    public function persist(): JsonResponse
    {
        $user = app(SwitchContextAction::class)->execute($this->targetUser(), SwitchContextData::fromValidated($this->validated()));

        return (new UserResource($user))->response();
    }
}
