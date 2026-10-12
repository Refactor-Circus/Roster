<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Transfer\Actions\ListTransfersAction;
use RefactorCircus\Roster\Domains\Transfer\Data\ListTransfersData;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;
use RefactorCircus\Roster\Mcp\Request;
use RefactorCircus\Roster\Support\Scopes;

/**
 * Everyone may list their own transfers. An organization's transfers need
 * `roster.members.view` there; everyone's need `roster.users.view`.
 */
final class ListTransfersMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.members.view';
    }

    protected function scope(): ?OrganizationModel
    {
        return Scopes::organization($this->get('organization'));
    }

    protected function self(): ?Model
    {
        return $this->scope() === null ? $this->actor() : null;
    }

    protected function rules(): array
    {
        return ListTransfersData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $transfers = app(ListTransfersAction::class)->execute(ListTransfersData::fromValidated($validated), ...app(Transfers::class)->visibleTo($this->actor(), $this->scope()));

        return TransferResource::collection($transfers)->toStructuredResponse();
    }
}
