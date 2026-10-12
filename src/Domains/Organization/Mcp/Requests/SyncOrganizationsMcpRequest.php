<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Actions\SyncOrganizationsAction;
use RefactorCircus\Roster\Domains\Organization\Data\SyncOrganizationsData;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationSyncResults;
use RefactorCircus\Roster\Mcp\Request;

final class SyncOrganizationsMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.sync';
    }

    protected function rules(): array
    {
        return SyncOrganizationsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->structuredCollection(OrganizationSyncResults::present(app(SyncOrganizationsAction::class)->execute(SyncOrganizationsData::fromValidated($validated))));
    }
}
