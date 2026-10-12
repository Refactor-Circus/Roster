<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\Scim\Events\ScimTokenRevokedActionEvent;
use RefactorCircus\Roster\Domains\Scim\Events\ScimTokenRevokingActionEvent;
use RefactorCircus\Roster\Domains\Scim\Models\ScimTokenModel;

final class RevokeScimTokenAction
{
    /**
     * Stop a token working. Revoking twice changes nothing.
     */
    public function execute(ScimTokenModel $token): ScimTokenModel
    {
        if ($token->revoked_at !== null) {
            return $token;
        }

        ScimTokenRevokingActionEvent::dispatch($token);

        DB::transaction(fn () => $token->update(['revoked_at' => now()]));

        ScimTokenRevokedActionEvent::dispatch($token);

        return $token->load(['organization', 'ssoConnection']);
    }
}
