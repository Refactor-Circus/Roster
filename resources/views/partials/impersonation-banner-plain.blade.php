{{--
    The impersonation banner without the Atrium dashboard, included by
    roster::impersonation-banner: plain HTML, styled inline because it shows
    on the application's own pages.
--}}
@php($directory = app(\RefactorCircus\Roster\Support\Users::class))
<div role="status" data-testid="impersonation-banner" @if ($bannerClass ?? null) class="{{ $bannerClass }}" @endif style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;padding:.5rem 1rem;background:#b45309;color:#ffffff;font:500 .875rem/1.4 system-ui,sans-serif;">
    <span>
        {{ __('roster::roster.impersonation_banner', [
            'user' => $impersonation->user ? ($directory->name($impersonation->user) ?? $directory->email($impersonation->user)) : '?',
            'impersonator' => $impersonation->impersonator ? ($directory->name($impersonation->impersonator) ?? $directory->email($impersonation->impersonator)) : '?',
        ]) }}
        · {{ __('roster::roster.impersonation_ends', ['time' => $impersonation->expires_at?->diffForHumans()]) }}
    </span>

    <form method="POST" action="{{ route('roster.impersonation.leave') }}" style="margin:0;">
        @csrf
        <button type="submit" data-testid="leave-impersonation" style="cursor:pointer;padding:.25rem .75rem;border:1px solid currentColor;border-radius:.375rem;background:transparent;color:inherit;font:inherit;">{{ __('roster::roster.impersonation_leave') }}</button>
    </form>
</div>
