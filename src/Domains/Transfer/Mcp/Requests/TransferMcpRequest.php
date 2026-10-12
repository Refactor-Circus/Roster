<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Mcp\Request;

/**
 * A call about one transfer: allowed for whoever started it, or anyone
 * holding the transfer type's permission in its organization.
 */
abstract class TransferMcpRequest extends Request
{
    private ?TransferModel $resolvedTransfer = null;

    protected function ability(): string
    {
        return $this->transfer()->type->permission();
    }

    protected function scope(): ?OrganizationModel
    {
        return $this->transfer()->organization;
    }

    protected function self(): ?Model
    {
        return $this->transfer()->requester;
    }

    protected function transfer(): TransferModel
    {
        return $this->resolvedTransfer ??= TransferModel::query()->whereKey($this->get('transfer'))->firstOrFail();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function transferRules(): array
    {
        return ['transfer' => ['required', 'string']];
    }

    /**
     * Every transfer tool names its transfer.
     */
    protected function rules(): array
    {
        return $this->transferRules();
    }
}
