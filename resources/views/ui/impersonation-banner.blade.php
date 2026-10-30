{{--
    Atrium's standalone banner for an active impersonation, included by
    roster::impersonation-banner while Atrium is installed. Standalone, so
    Atrium's banner styles itself inline on pages without its stylesheet.
--}}
@php($directory = app(\RefactorCircus\Roster\Support\Users::class))
<x-atrium::banner standalone :class="$bannerClass ?? null" data-testid="impersonation-banner">
    {{ __('roster::roster.impersonation_banner', [
        'user' => $impersonation->user ? ($directory->name($impersonation->user) ?? $directory->email($impersonation->user)) : '?',
        'impersonator' => $impersonation->impersonator ? ($directory->name($impersonation->impersonator) ?? $directory->email($impersonation->impersonator)) : '?',
    ]) }}
    · {{ __('roster::roster.impersonation_ends', ['time' => $impersonation->expires_at?->diffForHumans()]) }}

    <x-slot:actions>
        <form method="POST" action="{{ route('roster.impersonation.leave') }}">
            @csrf
            <x-atrium::banner.button standalone data-testid="leave-impersonation">{{ __('roster::roster.impersonation_leave') }}</x-atrium::banner.button>
        </form>
    </x-slot:actions>
</x-atrium::banner>
