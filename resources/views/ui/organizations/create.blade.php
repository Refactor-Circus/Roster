{{-- The `roster.organization-create` screen: an open registered tab's panel
     replaces the form; `aside` sits beside it only while it has sections to show. --}}
@php($aside = $resolved->openRegisteredTab === null && $resolved->sections('aside') !== [])

<x-atrium::layout :title="__('roster::roster.new_organization')">
    <x-atrium::page-header :title="__('roster::roster.new_organization')">
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
                            <form method="POST" action="{{ route('atrium.roster.organizations.store') }}" class="flex flex-col gap-4">
                                @csrf

                                <x-atrium::form.input name="name" :label="__('roster::roster.name')" :value="old('name')" required />
                                <x-atrium::form.input name="slug" :label="__('roster::roster.slug')" :value="old('slug')" :hint="__('roster::roster.slug_hint')" />
                                <x-atrium::form.input name="owner" :label="__('roster::roster.owner')" :value="old('owner', auth()->id())" :hint="__('roster::roster.owner_optional_hint')" />
                                <x-atrium::form.textarea name="domains" :label="__('roster::roster.domains')" :value="old('domains')" :hint="__('roster::roster.domains_hint')" rows="3" />
                                <x-atrium::form.checkbox name="auto_join" value="1" :label="__('roster::roster.auto_join')" />

                                <div>
                                    <x-atrium::icon-button icon="plus" :label="__('roster::roster.create')" variant="primary" type="submit" data-testid="create-organization" />
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
