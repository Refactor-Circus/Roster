{{-- The `roster.user` screen: the overview holds the page. The tab bar shows
     only while a registered tab is visible, and an open one's panel replaces
     the overview; `aside` sits beside it only while it has sections to show. --}}
@php($title = $profile?->display_name ?? $directory->name($user) ?? $directory->email($user) ?? __('roster::roster.user'))
@php($aside = $resolved->openRegisteredTab === null && $resolved->sections('aside') !== [])

<x-atrium::layout :title="$title">
    <x-atrium::page-header :title="$title" :description="$directory->email($user)">
        <x-slot:actions>
            @include('roster::ui.partials.status-dot', ['status' => $status, 'testid' => 'user-status', 'variant' => null, 'label' => null])
            <x-atrium::screen-sections :screen="$resolved" slot="header.actions" />
        </x-slot:actions>
    </x-atrium::page-header>

    <x-atrium::screen-tabs :screen="$resolved" class="mt-4" />

    <div class="mt-5 flex flex-col gap-4">
        @include('roster::impersonation-banner', ['bannerClass' => 'rounded-radius'])
        <x-atrium::flash />

        @if ($resolved->openRegisteredTab !== null)
            <x-atrium::screen-tab-panel :screen="$resolved" />
        @else
            <x-atrium::screen-sections :screen="$resolved" slot="before" />

            @if ($aside)
                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2">
            @endif

            @include('roster::ui.users.partials.overview', [
                'user' => $user,
                'profile' => $profile,
                'status' => $status,
                'directory' => $directory,
                'memberships' => $memberships,
                'currentOrganization' => $currentOrganization,
                'currentTeam' => $currentTeam,
                'assignments' => $assignments,
                'effective' => $effective,
                'assignableRoles' => $assignableRoles,
            ])

            @if ($aside)
                    </div>
                    <aside class="flex flex-col gap-4" data-testid="screen-aside">
                        <x-atrium::screen-sections :screen="$resolved" slot="aside" />
                    </aside>
                </div>
            @endif

            <x-atrium::screen-sections :screen="$resolved" slot="after" />
        @endif
    </div>
</x-atrium::layout>
