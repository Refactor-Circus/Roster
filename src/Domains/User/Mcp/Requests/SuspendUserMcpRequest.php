<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\User\Actions\SuspendUserAction;
use RefactorCircus\Roster\Domains\User\Data\SuspendUserData;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

final class SuspendUserMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.manage-status';
    }

    protected function rules(): array
    {
        return $this->userRules() + SuspendUserData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new UserResource(app(SuspendUserAction::class)->execute($this->targetUser(), SuspendUserData::fromValidated($validated), $this->actor())))->toStructuredResponse();
    }
}
