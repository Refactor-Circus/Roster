<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Actions\UnlinkOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;

final class UnlinkOrganizationMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.update';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + ['source' => ['required', 'string']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new OrganizationResource(app(UnlinkOrganizationAction::class)->execute($this->organization(), (string) $validated['source'])))->toStructuredResponse();
    }
}
