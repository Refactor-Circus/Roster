@inject('directory', 'RefactorCircus\Roster\Support\Users')
{{-- The `roster.impersonations` screen: an open registered tab's panel replaces the list. --}}

<x-atrium::layout :title="__('roster::roster.impersonations')">
    <x-atrium::page-header :title="__('roster::roster.impersonations')">
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

            @if ($impersonations->isEmpty())
                <x-atrium::empty-state :title="__('roster::roster.no_impersonations')" />
            @else
                <x-atrium::table striped>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('roster::roster.impersonator') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.user') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.reason') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.status') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading class="text-right">{{ __('roster::roster.actions') }}</x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>
                    @foreach ($impersonations as $impersonation)
                        <x-atrium::table.row>
                            <x-atrium::table.cell>{{ $impersonation->impersonator ? $directory->name($impersonation->impersonator) : __('roster::roster.none') }}</x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $impersonation->user ? $directory->name($impersonation->user) : __('roster::roster.none') }}</x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $impersonation->reason }}</x-atrium::table.cell>
                            <x-atrium::table.cell>
                                @if ($impersonation->isActive())
                                    @include('roster::ui.partials.status-dot', ['variant' => 'warning', 'label' => __('roster::roster.impersonation_active'), 'status' => null, 'testid' => null])
                                @elseif ($impersonation->ended_at)
                                    {{ $impersonation->end_reason }} · {{ $impersonation->ended_at->diffForHumans() }}
                                @else
                                    {{ __('roster::roster.impersonation_link_issued') }}
                                @endif
                            </x-atrium::table.cell>
                            <x-atrium::table.cell>
                                {{-- Your own impersonation, or anyone's with the permission in its organization. --}}
                                @if (! $impersonation->ended_at && \RefactorCircus\Roster\Atrium\ScreenAccess::allows('roster.users.impersonate', $impersonation->organization, (string) $impersonation->impersonator_id === (string) auth()->id() ? auth()->user() : null))
                                    <form method="POST" action="{{ route('atrium.roster.impersonations.stop', $impersonation->id) }}" class="flex justify-end">
                                        @csrf
                                        @method('DELETE')
                                        <x-atrium::icon-button icon="stop" :label="__('roster::roster.end')" type="submit" size="sm" variant="danger" data-testid="end-impersonation" />
                                    </form>
                                @endif
                                <x-atrium::screen-sections :screen="$resolved" slot="row.actions" :row="$impersonation" />
                            </x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>
                <x-atrium::pagination :paginator="$impersonations" />
            @endif

            <x-atrium::screen-sections :screen="$resolved" slot="after" />
        @endif
    </div>
</x-atrium::layout>
