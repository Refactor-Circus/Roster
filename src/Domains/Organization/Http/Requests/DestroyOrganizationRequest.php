<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\Response;
use RefactorCircus\Roster\Domains\Organization\Actions\DeleteOrganizationAction;

final class DestroyOrganizationRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.delete';
    }

    public function persist(): Response
    {
        app(DeleteOrganizationAction::class)->execute($this->organization());

        return response()->noContent();
    }
}
