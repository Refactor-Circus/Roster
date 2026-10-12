<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\User\Actions\UpdateProfileAction;
use RefactorCircus\Roster\Domains\User\Data\UpdateProfileData;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

final class UpdateProfileMcpRequest extends UserMcpRequest
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
        return $this->userRules() + UpdateProfileData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new UserResource(app(UpdateProfileAction::class)->execute($this->targetUser(), UpdateProfileData::fromValidated($validated))))->toStructuredResponse();
    }
}
