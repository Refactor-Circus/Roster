<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Actions\TransferOwnershipAction;
use RefactorCircus\Roster\Domains\Organization\Data\TransferOwnershipData;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;

final class TransferOwnershipMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.transfer';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + TransferOwnershipData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new OrganizationResource(app(TransferOwnershipAction::class)->execute($this->organization(), TransferOwnershipData::fromValidated($validated))))->toStructuredResponse();
    }
}
