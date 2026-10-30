<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Roster\Contracts\SignInRequirement;
use RefactorCircus\Roster\Data\ExternalSignIn;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;

/**
 * SSO enforcement: an email whose domain belongs to an organization with an
 * enabled, enforced connection must sign in through that connection. The
 * answer's key is the connection's slug, its label the organization's name,
 * its url the connection's start page, and its record the connection.
 *
 * Registered from SsoServiceProvider. Super-admins never reach it:
 * `Roster::externalSignInFor()` answers them first.
 */
final class SsoSignInRequirement implements SignInRequirement
{
    public function for(string $email): ?ExternalSignIn
    {
        if (! str_contains($email, '@')) {
            return null;
        }

        $domain = strtolower(substr($email, (int) strrpos($email, '@') + 1));

        $connection = SsoConnectionModel::query()
            ->where('enforced', true)
            ->where('enabled', true)
            ->whereHas('organization.domains', fn (Builder $query): Builder => $query->where('domain', $domain))
            ->first();

        if ($connection === null) {
            return null;
        }

        return new ExternalSignIn(
            key: $connection->slug,
            label: (string) $connection->organization?->name,
            url: Route::has('roster.sso.start') ? route('roster.sso.start', $connection->slug) : '',
            record: $connection,
        );
    }
}
