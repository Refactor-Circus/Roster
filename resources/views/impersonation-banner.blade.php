{{--
    The banner shown while someone is impersonating, with a button to leave.
    Include it in the application's layout:

        @include('roster::impersonation-banner')

    It renders nothing otherwise. It shows on the application's own pages,
    so it is styled inline: Atrium's standalone banner while Atrium is
    installed (roster::ui.impersonation-banner), a plain one without it
    (roster::partials.impersonation-banner-plain). Pass `bannerClass` to add
    classes.
--}}
@php($impersonation = $rosterImpersonation ?? app(\RefactorCircus\Roster\Domains\Impersonation\Services\ImpersonationContext::class)->active())

@if ($impersonation)
    @include(($rosterUsesAtrium ?? false) ? 'roster::ui.impersonation-banner' : 'roster::partials.impersonation-banner-plain', [
        'impersonation' => $impersonation,
        'bannerClass' => $bannerClass ?? null,
    ])
@endif
