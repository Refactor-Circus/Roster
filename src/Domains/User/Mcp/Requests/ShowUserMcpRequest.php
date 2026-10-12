<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\User\Actions\ShowUserAction;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

final class ShowUserMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.view';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new UserResource(app(ShowUserAction::class)->execute($this->targetUser())))->toStructuredResponse();
    }
}
