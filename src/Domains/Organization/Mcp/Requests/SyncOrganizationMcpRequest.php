<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Actions\SyncOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\SyncOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;
use RefactorCircus\Roster\Mcp\Request;

final class SyncOrganizationMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.sync';
    }

    protected function rules(): array
    {
        return SyncOrganizationData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $result = app(SyncOrganizationAction::class)->execute(SyncOrganizationData::fromValidated($validated));

        return (new OrganizationResource($result->organization))->additional(['outcome' => $result->outcome])->toStructuredResponse();
    }
}
