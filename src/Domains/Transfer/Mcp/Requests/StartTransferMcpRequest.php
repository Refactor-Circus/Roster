<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Mcp\Requests;

use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;
use RefactorCircus\Roster\Mcp\Request;
use RefactorCircus\Roster\Support\Scopes;

/**
 * Starting an import or export needs the type's permission, in the
 * organization for organization-wide types.
 */
abstract class StartTransferMcpRequest extends Request
{
    /**
     * The permission checked while the type is not valid yet; validation
     * then rejects it.
     */
    abstract protected function fallbackAbility(): string;

    protected function ability(): string
    {
        return $this->type()?->permission() ?? $this->fallbackAbility();
    }

    protected function scope(): ?OrganizationModel
    {
        $type = $this->type();
        $organization = Scopes::organization($this->get('organization'));

        return $type === null || $organization === null ? null : Transfers::scope($type, $organization);
    }

    private function type(): ?TransferType
    {
        $type = $this->get('type');

        return is_string($type) ? TransferType::tryFrom($type) : null;
    }
}
