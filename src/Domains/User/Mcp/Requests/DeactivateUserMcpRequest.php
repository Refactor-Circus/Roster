<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\User\Actions\DeactivateUserAction;
use RefactorCircus\Roster\Domains\User\Data\DeactivateUserData;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

final class DeactivateUserMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.manage-status';
    }

    protected function rules(): array
    {
        return $this->userRules() + DeactivateUserData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new UserResource(app(DeactivateUserAction::class)->execute($this->targetUser(), DeactivateUserData::fromValidated($validated), $this->actor())))->toStructuredResponse();
    }
}
