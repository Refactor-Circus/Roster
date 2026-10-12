<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\Response;
use RefactorCircus\Roster\Domains\Organization\Actions\RemoveMemberAction;
use RefactorCircus\Roster\Support\Users;

final class DestroyMemberRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.members.manage';
    }

    public function persist(): Response
    {
        app(RemoveMemberAction::class)->execute($this->organization(), app(Users::class)->findOrFail($this->route('user')));

        return response()->noContent();
    }
}
