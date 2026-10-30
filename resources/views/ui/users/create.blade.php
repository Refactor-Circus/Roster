{{-- The `roster.user-create` screen: an open registered tab's panel replaces
     the form; `aside` sits beside it only while it has sections to show. --}}
@php($aside = $resolved->openRegisteredTab === null && $resolved->sections('aside') !== [])

<x-atrium::layout :title="__('roster::roster.new_user')">
    <x-atrium::page-header :title="__('roster::roster.new_user')">
        @if ($resolved->sections('header.actions') !== [])
            <x-slot:actions>
                <x-atrium::screen-sections :screen="$resolved" slot="header.actions" />
            </x-slot:actions>
        @endif
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
                    <div class="flex flex-col gap-4 lg:col-span-2">
            @else
                    <div class="flex max-w-2xl flex-col gap-4">
            @endif
                        <x-atrium::card>
                            <form method="POST" action="{{ route('atrium.roster.users.store') }}" class="flex flex-col gap-4">
                                @csrf

                                @if ($hasName)
                                    <x-atrium::form.input name="name" :label="__('roster::roster.name')" :value="old('name')" required />
                                @endif
                                <x-atrium::form.input name="email" type="email" :label="__('roster::roster.email')" :value="old('email')" required />
                                <x-atrium::form.input name="password" type="password" :label="__('roster::roster.password')" :hint="__('roster::roster.password_create_hint')" />
                                @php($accountStatuses = \RefactorCircus\Roster\Support\Statuses::users())
                                <x-atrium::form.select
                                    name="status"
                                    :label="__('roster::roster.initial_status')"
                                    :options="[$accountStatuses::active()->value => $accountStatuses::active()->label(), $accountStatuses::pending()->value => $accountStatuses::pending()->label()]"
                                    :selected="old('status', $accountStatuses::active()->value)"
                                    data-testid="initial-status" />

                                @include('roster::ui.users.partials.profile-fields', ['profile' => null])

                                <div>
                                    <x-atrium::icon-button icon="plus" :label="__('roster::roster.create')" variant="primary" type="submit" data-testid="create-user" />
                                </div>
                            </form>
                        </x-atrium::card>
                    </div>

            @if ($aside)
                    <aside class="flex flex-col gap-4" data-testid="screen-aside">
                        <x-atrium::screen-sections :screen="$resolved" slot="aside" />
                    </aside>
                </div>
            @endif

            <x-atrium::screen-sections :screen="$resolved" slot="after" />
        @endif
    </div>
</x-atrium::layout>
