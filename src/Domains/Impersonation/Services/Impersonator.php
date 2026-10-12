<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StopImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Data\StopImpersonationData;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;

/**
 * Swaps the browser session between the impersonator and the user they act
 * as. Only Roster's web routes and middleware call this; Actions record and
 * guard the impersonation itself.
 */
final class Impersonator
{
    public function __construct(
        private readonly Auth $auth,
        private readonly ImpersonationContext $context,
    ) {}

    public function enter(ImpersonationModel $impersonation, Request $request): void
    {
        $user = $impersonation->user;

        if (! $user instanceof Authenticatable) {
            return;
        }

        $request->session()->put(ImpersonationContext::SESSION_KEY, $impersonation->id);
        $this->auth->guard()->login($user);
        $request->session()->regenerate();
        $this->context->forget();
    }

    /**
     * End the session's impersonation and sign the impersonator back in.
     */
    public function leave(Request $request, string $why = ImpersonationModel::ENDED_STOPPED): ?ImpersonationModel
    {
        $id = $this->context->sessionId();
        $impersonation = $id === null ? null : ImpersonationModel::query()->find($id);

        $request->session()->forget(ImpersonationContext::SESSION_KEY);
        $this->context->forget();

        if ($impersonation === null) {
            return null;
        }

        if ($impersonation->ended_at === null) {
            app(StopImpersonationAction::class)->execute($impersonation, new StopImpersonationData(why: $why));
        }

        $impersonator = $impersonation->impersonator;

        if ($impersonator instanceof Authenticatable) {
            $this->auth->guard()->login($impersonator);
            $request->session()->regenerate();
        }

        return $impersonation;
    }
}
