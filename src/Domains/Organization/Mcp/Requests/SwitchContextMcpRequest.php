<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Actions\SwitchContextAction;
use RefactorCircus\Roster\Domains\Organization\Data\SwitchContextData;
use RefactorCircus\Roster\Domains\User\Mcp\Requests\UserMcpRequest;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

final class SwitchContextMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.update';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    protected function rules(): array
    {
        return $this->userRules() + SwitchContextData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new UserResource(app(SwitchContextAction::class)->execute($this->targetUser(), SwitchContextData::fromValidated($validated))))->toStructuredResponse();
    }
}
