@php($title = $profile?->display_name ?? $directory->name($user) ?? $directory->email($user) ?? __('roster::roster.user'))

<x-atrium::layout :title="$title">
    <x-atrium::page-header :title="$title" :description="$directory->email($user)">
        <x-slot:actions>
            @include('roster::ui.partials.status-dot', ['status' => $status, 'testid' => 'user-status', 'variant' => null, 'label' => null])
        </x-slot:actions>
    </x-atrium::page-header>

    {{-- The tab bar only while a registered user tab is visible; otherwise the page is the overview alone. --}}
    @if ($tabs !== [])
        <div class="mt-5 flex flex-col gap-4">
            @include('roster::ui.partials.tabs', [
                'label' => $title,
                'tabs' => $tabs,
                'tab' => $tab,
                'icons' => ['overview' => 'user-circle'],
                'registered' => $registeredTabs,
                'href' => fn (string $name): string => route('atrium.roster.users.show', [$user->getRouteKey(), 'tab' => $name]),
            ])
        </div>
    @endif

    @if ($registeredTab !== null)
        <div class="mt-5 flex flex-col gap-4">
            @include('roster::impersonation-banner', ['bannerClass' => 'rounded-radius'])
            <x-atrium::flash />

            @include('roster::ui.partials.registered-tab', [
                'registeredTab' => $registeredTab,
                'data' => $registeredData,
                'roster' => ['user' => $user, 'tab' => $tab, 'tabs' => $tabs, 'registeredTab' => $registeredTab, 'errors' => $errors],
            ])
        </div>
    @else
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
            'ssoIdentities' => $ssoIdentities,
        ])
    @endif
</x-atrium::layout>
