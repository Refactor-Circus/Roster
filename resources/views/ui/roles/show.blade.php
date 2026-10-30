{{-- The `roster.role` screen: an open registered tab's panel replaces the
     page; `aside` sits beside it only while it has sections to show. --}}
@php($aside = $resolved->openRegisteredTab === null && $resolved->sections('aside') !== [])

<x-atrium::layout :title="$role->name">
    <x-atrium::page-header :title="$role->name" :description="$role->scope->label().' · '.($role->organization?->name ?? __('roster::roster.shared'))">
        <x-slot:actions>
            <x-atrium::icon-button icon="arrow-left" :label="__('roster::roster.roles')" variant="ghost" :href="route('atrium.roster.roles.index')" />
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
                    <div class="flex flex-col gap-4 lg:col-span-2">
            @endif

            <x-atrium::card>
                @if ($role->super)
                    <p class="text-sm">{{ __('roster::roster.super_role_hint') }}</p>
                @elseif (! \RefactorCircus\Roster\Atrium\ScreenAccess::allows('roster.roles.manage', $role->organization))
                    {{-- Read only for those who may view roles but not change them. --}}
                    @if ($role->description)
                        <p class="mb-3 text-sm">{{ $role->description }}</p>
                    @endif
                    <p class="font-mono text-xs leading-relaxed" data-testid="role-permissions">{{ $role->permissions->pluck('name')->sort()->implode(', ') ?: __('roster::roster.none') }}</p>
                @else
                    <form method="POST" action="{{ route('atrium.roster.roles.update', $role->id) }}" class="flex max-w-3xl flex-col gap-4">
                        @csrf
                        @method('PATCH')
                        <x-atrium::form.input name="name" :label="__('roster::roster.name')" :value="old('name', $role->name)" required />
                        <x-atrium::form.textarea name="description" :label="__('roster::roster.description')" :value="old('description', $role->description)" rows="2" />
                        <fieldset class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <legend class="mb-1 text-sm font-medium">{{ __('roster::roster.permissions') }}</legend>
                            @foreach ($permissions as $permission)
                                <x-atrium::form.checkbox
                                    name="permissions[]"
                                    :value="$permission->name"
                                    :id="'role-'.$permission->name"
                                    :label="$permission->name"
                                    :checked="$role->permissions->contains('name', $permission->name)" />
                            @endforeach
                        </fieldset>
                        <div>
                            <x-atrium::icon-button icon="check" :label="__('roster::roster.save')" variant="primary" type="submit" data-testid="save-role" />
                        </div>
                    </form>
                @endif
            </x-atrium::card>

            @if (! $role->system && \RefactorCircus\Roster\Atrium\ScreenAccess::allows('roster.roles.manage', $role->organization))
                <form method="POST" action="{{ route('atrium.roster.roles.destroy', $role->id) }}">
                    @csrf
                    @method('DELETE')
                    <x-atrium::icon-button icon="trash" :label="__('roster::roster.delete')" type="submit" variant="danger" data-testid="delete-role" />
                </form>
            @endif

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
