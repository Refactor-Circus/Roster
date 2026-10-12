<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Http\Requests;

use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;
use RefactorCircus\Roster\Http\Request;
use RefactorCircus\Roster\Support\Scopes;

/**
 * Starting an import or export needs the type's permission, in the
 * organization for organization-wide types.
 */
abstract class StartTransferRequest extends Request
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
        $organization = Scopes::organization($this->input('organization'));

        return $type === null || $organization === null ? null : Transfers::scope($type, $organization);
    }

    private function type(): ?TransferType
    {
        $type = $this->input('type');

        return is_string($type) ? TransferType::tryFrom($type) : null;
    }
}
