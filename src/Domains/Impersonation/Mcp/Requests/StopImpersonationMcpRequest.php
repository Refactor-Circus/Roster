<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StopImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Data\StopImpersonationData;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;
use RefactorCircus\Roster\Domains\Impersonation\Resources\ImpersonationResource;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Mcp\Request;

final class StopImpersonationMcpRequest extends Request
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

    protected function self(): ?Model
    {
        $actor = $this->actor();

        return $this->impersonation()->startedBy($actor) ? $actor : null;
    }

    protected function rules(): array
    {
        return ['impersonation' => ['required', 'string']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $impersonation = app(StopImpersonationAction::class)->execute($this->impersonation(), new StopImpersonationData(
            why: $this->impersonation()->endReasonFor($this->actor()),
        ));

        return (new ImpersonationResource($impersonation))->toStructuredResponse();
    }

    private function impersonation(): ImpersonationModel
    {
        return $this->resolved ??= ImpersonationModel::query()->whereKey($this->get('impersonation'))->firstOrFail();
    }
}
