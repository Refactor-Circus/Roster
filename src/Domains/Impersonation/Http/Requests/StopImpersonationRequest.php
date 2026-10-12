<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StopImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Data\StopImpersonationData;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;
use RefactorCircus\Roster\Domains\Impersonation\Resources\ImpersonationResource;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Http\Request;

final class StopImpersonationRequest extends Request
{
    private ?ImpersonationModel $resolved = null;

    protected function ability(): string
    {
        return 'roster.users.impersonate';
    }

    protected function scope(): ?OrganizationModel
    {
        return $this->impersonation()->organization;
    }

    /**
     * Impersonators may always end their own.
     */
    protected function self(): ?Model
    {
        $actor = $this->actor();

        return $this->impersonation()->startedBy($actor) ? $actor : null;
    }

    public function persist(): JsonResponse
    {
        $impersonation = app(StopImpersonationAction::class)->execute($this->impersonation(), new StopImpersonationData(
            why: $this->impersonation()->endReasonFor($this->actor()),
        ));

        return (new ImpersonationResource($impersonation))->response();
    }

    private function impersonation(): ImpersonationModel
    {
        return $this->resolved ??= ImpersonationModel::query()->whereKey($this->route('impersonation'))->firstOrFail();
    }
}
