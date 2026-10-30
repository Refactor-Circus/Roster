{{-- The `roster.roles` screen: an open registered tab's panel replaces the list. --}}
<x-atrium::layout :title="__('roster::roster.roles')">
    <x-atrium::page-header :title="__('roster::roster.roles')">
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

            <x-atrium::card>
                <form method="GET" action="{{ route('atrium.roster.roles.index') }}" class="flex flex-wrap items-start gap-3">
                    <x-atrium::form.select
                        name="scope"
                        :label="__('roster::roster.scope')"
                        :placeholder="__('roster::roster.all_scopes')"
                        :options="collect($scopes)->mapWithKeys(fn ($scope) => [$scope->value => $scope->label()])"
                        :selected="$filters->scope?->value"
                        wrapper="w-48" />
                    <x-atrium::form.input name="organization" :label="__('roster::roster.organization')" :value="$filters->organization" :hint="__('roster::roster.organization_slug_hint')" wrapper="w-56" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="funnel" :label="__('roster::roster.filter')" variant="primary" type="submit" />
                        <x-atrium::icon-button icon="x-mark" :label="__('roster::roster.clear')" variant="ghost" :href="route('atrium.roster.roles.index')" />
                    </x-atrium::form.actions>
                </form>
            </x-atrium::card>

            <x-atrium::table striped>
                <x-slot:head>
                    <x-atrium::table.row>
                        <x-atrium::table.cell heading>{{ __('roster::roster.role') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.scope') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.organization') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.permissions') }}</x-atrium::table.cell>
                    </x-atrium::table.row>
                </x-slot:head>

                @foreach ($roles as $role)
                    <x-atrium::table.row>
                        <x-atrium::table.cell>
                            <a class="font-medium underline-offset-2 hover:underline" href="{{ route('atrium.roster.roles.show', $role->id) }}">{{ $role->name }}</a>
                            @if ($role->super)
                                <x-atrium::badge variant="danger">{{ __('roster::roster.super') }}</x-atrium::badge>
                            @endif
                            @if ($role->system)
                                <x-atrium::badge>{{ __('roster::roster.built_in') }}</x-atrium::badge>
                            @endif
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $role->scope->label() }}</x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $role->organization?->name ?? __('roster::roster.shared') }}</x-atrium::table.cell>
                        <x-atrium::table.cell>
                            {{ $role->super ? __('roster::roster.everything') : $role->permissions->count() }}
                            <x-atrium::screen-sections :screen="$resolved" slot="row.actions" :row="$role" />
                        </x-atrium::table.cell>
                    </x-atrium::table.row>
                @endforeach
            </x-atrium::table>

            <x-atrium::pagination :paginator="$roles" />

            {{-- New roles in the filtered organization, or globally without a filter. --}}
            @rosterCan('roster.roles.manage', $newRoleScope)
            <x-atrium::card :title="__('roster::roster.new_role')" data-testid="new-role-card">
                <form method="POST" action="{{ route('atrium.roster.roles.store') }}" class="flex max-w-3xl flex-col gap-4">
                    @csrf
                    <div class="flex flex-wrap gap-3">
                        <x-atrium::form.input name="name" :label="__('roster::roster.name')" wrapper="w-56" required />
                        <x-atrium::form.select
                            name="scope"
                            :label="__('roster::roster.scope')"
                            :options="collect($scopes)->mapWithKeys(fn ($scope) => [$scope->value => $scope->label()])"
                            wrapper="w-48" />
                        <x-atrium::form.input name="organization" :label="__('roster::roster.organization')" :value="old('organization', $filters->organization)" :hint="__('roster::roster.role_organization_hint')" wrapper="w-56" />
                    </div>
                    <x-atrium::form.textarea name="description" :label="__('roster::roster.description')" rows="2" />
                    <fieldset class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <legend class="mb-1 text-sm font-medium">{{ __('roster::roster.permissions') }}</legend>
                        @foreach ($permissions as $permission)
                            <x-atrium::form.checkbox name="permissions[]" :value="$permission->name" :id="'new-role-'.$permission->name" :label="$permission->name" />
                        @endforeach
                    </fieldset>
                    <div>
                        <x-atrium::icon-button icon="plus" :label="__('roster::roster.create')" variant="primary" type="submit" data-testid="create-role" />
                    </div>
                </form>
            </x-atrium::card>
            @endrosterCan

            <x-atrium::screen-sections :screen="$resolved" slot="after" />
        @endif
    </div>
</x-atrium::layout>
