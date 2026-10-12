<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\User\Actions\RejectUserAction;
use RefactorCircus\Roster\Domains\User\Data\RejectUserData;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

final class RejectUserMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.approve';
    }

    protected function rules(): array
    {
        return $this->userRules() + RejectUserData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new UserResource(app(RejectUserAction::class)->execute($this->targetUser(), RejectUserData::fromValidated($validated), $this->actor())))->toStructuredResponse();
    }
}
