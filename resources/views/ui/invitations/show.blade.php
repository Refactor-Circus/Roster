{{-- The `roster.invitation` screen, Atrium's guest page: an open registered
     tab's panel replaces the invitation; `aside` sits beside it only while it
     has sections to show. The plain page is roster::invitations.show. --}}
@php($heading = __('roster::roster.invitation_title', ['organization' => $organization->name]))
@php($aside = $resolved->openRegisteredTab === null && $resolved->sections('aside') !== [])

<x-atrium::guest :title="$heading" :heading="$heading">
    @if ($resolved->sections('header.actions') !== [])
        <div class="flex flex-wrap gap-3">
            <x-atrium::screen-sections :screen="$resolved" slot="header.actions" />
        </div>
    @endif

    <x-atrium::screen-tabs :screen="$resolved" />

    @if ($resolved->openRegisteredTab !== null)
        <x-atrium::screen-tab-panel :screen="$resolved" />
    @else
        <x-atrium::screen-sections :screen="$resolved" slot="before" />

        @if ($aside)
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="flex flex-col gap-4 lg:col-span-2">
        @endif

        <p>{{ __('roster::roster.invitation_prompt', ['organization' => $organization->name]) }}</p>

        @if ($teams->isNotEmpty())
            <p>{{ __('roster::roster.invitation_teams', ['teams' => $teams->join(', ')]) }}</p>
        @endif

        @if ($errors->any())
            <x-atrium::alert variant="danger">{{ $errors->first() }}</x-atrium::alert>
        @endif

        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('roster.invitations.page.accept', $token) }}">
                @csrf
                <x-atrium::button type="submit" data-testid="accept-invitation">{{ __('roster::roster.accept') }}</x-atrium::button>
            </form>
            <form method="POST" action="{{ route('roster.invitations.page.decline', $token) }}">
                @csrf
                <x-atrium::button type="submit" variant="outline" data-testid="decline-invitation">{{ __('roster::roster.decline') }}</x-atrium::button>
            </form>
        </div>

        @if ($aside)
                </div>
                <aside class="flex flex-col gap-4" data-testid="screen-aside">
                    <x-atrium::screen-sections :screen="$resolved" slot="aside" />
                </aside>
            </div>
        @endif

        <x-atrium::screen-sections :screen="$resolved" slot="after" />
    @endif
</x-atrium::guest>
