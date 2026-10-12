<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\User\Actions\UpdateUserAction;
use RefactorCircus\Roster\Domains\User\Data\UpdateUserData;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

final class UpdateUserMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.update';
    }

    protected function rules(): array
    {
        return $this->userRules() + UpdateUserData::rules($this->targetUser());
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new UserResource(app(UpdateUserAction::class)->execute($this->targetUser(), UpdateUserData::fromValidated($validated))))->toStructuredResponse();
    }
}
