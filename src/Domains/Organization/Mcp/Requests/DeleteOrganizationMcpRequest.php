<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Roster\Domains\Organization\Actions\DeleteOrganizationAction;

final class DeleteOrganizationMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.delete';
    }

    protected function handle(array $validated): Response
    {
        app(DeleteOrganizationAction::class)->execute($this->organization());

        return Response::text('Organization deleted.');
    }
}
