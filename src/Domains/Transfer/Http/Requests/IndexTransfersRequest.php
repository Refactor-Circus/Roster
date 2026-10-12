<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Transfer\Actions\ListTransfersAction;
use RefactorCircus\Roster\Domains\Transfer\Data\ListTransfersData;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;
use RefactorCircus\Roster\Http\Request;
use RefactorCircus\Roster\Support\Scopes;

/**
 * Everyone may list their own transfers. An organization's transfers need
 * `roster.members.view` there; everyone's need `roster.users.view`.
 */
final class IndexTransfersRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.members.view';
    }

    protected function scope(): ?OrganizationModel
    {
        return Scopes::organization($this->input('organization'));
    }

    protected function self(): ?Model
    {
        return $this->scope() === null ? $this->actor() : null;
    }

    public function rules(): array
    {
        return ListTransfersData::rules();
    }

    public function persist(): JsonResponse
    {
        return TransferResource::collection(app(ListTransfersAction::class)->execute(ListTransfersData::fromValidated($this->validated()), ...app(Transfers::class)->visibleTo($this->actor(), $this->scope())))->response();
    }
}
