<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use RefactorCircus\Roster\Support\Statuses;
use RefactorCircus\Roster\Support\Users;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects suspended and deactivated users, and those awaiting approval. Registered as `roster.active`.
 */
final class EnsureUserIsActive
{
    public function __construct(private readonly Users $users) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof Model) {
            $status = $this->users->status($user);

            if ($status === Statuses::users()::pending()) {
                abort(403, __('roster::roster.account_pending'));
            }

            if (! $status->allowsAccess()) {
                abort(403, __('roster::roster.account_inactive'));
            }
        }

        return $next($request);
    }
}
