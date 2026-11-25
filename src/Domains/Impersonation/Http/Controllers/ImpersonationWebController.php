<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Roster\Domains\Impersonation\Actions\EnterImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Data\EnterImpersonationData;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;
use RefactorCircus\Roster\Domains\Impersonation\Services\Impersonator;

/**
 * The browser side of impersonation: using the one-time link, and returning
 * to your own account. Leaving ends the record through
 * \RefactorCircus\Roster\Domains\Impersonation\Actions\StopImpersonationAction (via Impersonator::leave()).
 */
final class ImpersonationWebController
{
    public function __construct(private readonly Impersonator $impersonator) {}

    public function enter(Request $request, string $token): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof Model, 401);

        $impersonation = app(EnterImpersonationAction::class)->execute(EnterImpersonationData::from(['token' => $token]), $actor);

        $this->impersonator->enter($impersonation, $request);

        return redirect((string) config('roster.impersonation.redirect', '/'));
    }

    public function leave(Request $request): RedirectResponse
    {
        $this->impersonator->leave($request, ImpersonationModel::ENDED_STOPPED);

        return redirect($this->returnTo())->with('status', __('roster::roster.impersonation_left'));
    }

    /**
     * `roster.impersonation.return_to`, or the site's root when it is not set.
     */
    private function returnTo(): string
    {
        $configured = config('roster.impersonation.return_to');

        return is_string($configured) && $configured !== '' ? $configured : '/';
    }
}
