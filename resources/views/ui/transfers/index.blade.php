@inject('directory', 'RefactorCircus\Roster\Support\Users')
{{-- The `roster.transfers` screen: an open registered tab's panel replaces the page. --}}

<x-atrium::layout :title="__('roster::roster.transfers')">
    <x-atrium::page-header :title="__('roster::roster.transfers')" :description="$organization?->name">
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

            @unless ($available)
                <x-atrium::alert variant="warning" :title="__('roster::roster.transfers_unavailable')">
                    <code>composer require refactor-circus/impex</code> · {{ __('roster::roster.transfers_unavailable_hint') }}
                </x-atrium::alert>
            @else
                {{-- Only the types the viewer may start here: each type's permission, in this organization when it needs one. --}}
                @php($allowed = fn (array $types) => collect($types)->filter(fn ($type) => \RefactorCircus\Roster\Atrium\ScreenAccess::allows($type->permission(), \RefactorCircus\Roster\Domains\Transfer\Services\Transfers::scope($type, $organization)))->values())
                @php($imports = $allowed($imports))
                @php($exports = $allowed($exports))

                <div class="grid gap-4 lg:grid-cols-2">
                    @if ($imports->isNotEmpty())
                    <x-atrium::card :title="__('roster::roster.new_import')" data-testid="import-card">
                        <form method="GET" action="{{ route('atrium.roster.transfers.template') }}" class="mb-4 flex flex-wrap items-start gap-3" data-testid="import-templates">
                            <x-atrium::form.select
                                name="type"
                                id="template-type"
                                :label="__('roster::roster.template')"
                                :options="$imports->mapWithKeys(fn ($type) => [$type->value => $type->label()])"
                                wrapper="w-56" />
                            <x-atrium::form.actions>
                                <x-atrium::icon-button icon="arrow-down-tray" :label="__('roster::roster.download_template')" type="submit" variant="secondary" data-testid="download-template" />
                            </x-atrium::form.actions>
                        </form>

                        <form method="POST" action="{{ route('atrium.roster.transfers.import') }}" enctype="multipart/form-data" class="flex flex-col gap-3">
                            @csrf
                            <x-atrium::form.select
                                name="type"
                                id="import-type"
                                :label="__('roster::roster.type')"
                                :options="$imports->mapWithKeys(fn ($type) => [$type->value => $type->label()])"
                                :selected="old('type')"
                                required />
                            <x-atrium::form.input name="organization" id="import-organization" :label="__('roster::roster.organization')" :value="old('organization', $organization?->slug)" :hint="__('roster::roster.import_organization_hint')" />
                            <x-atrium::form.file name="file" :label="__('roster::roster.csv_file')" :hint="__('roster::roster.import_columns_hint')" accept=".csv,text/csv" />
                            <div>
                                <x-atrium::icon-button icon="arrow-up-tray" :label="__('roster::roster.preview_import')" variant="primary" type="submit" data-testid="start-import" />
                            </div>
                        </form>
                    </x-atrium::card>
                    @endif

                    @if ($exports->isNotEmpty())
                    <x-atrium::card :title="__('roster::roster.new_export')" data-testid="export-card">
                        {{-- Alpine shows only the fields the picked export type uses. It reads the select on start, in case it changed before Alpine loaded. --}}
                        <form method="POST" action="{{ route('atrium.roster.transfers.export') }}" class="flex flex-col gap-3" x-data="{ type: @js(old('type', $exports->first()->value)) }" x-init="type = $el.querySelector('select[name=type]').value">
                            @csrf
                            <x-atrium::form.select
                                name="type"
                                id="export-type"
                                :label="__('roster::roster.type')"
                                :options="$exports->mapWithKeys(fn ($type) => [$type->value => $type->label()])"
                                x-model="type"
                                required />
                            <div x-show="type === 'export_members'" data-export-field="organization">
                                <x-atrium::form.input name="organization" id="export-organization" :label="__('roster::roster.organization')" :value="$organization?->slug" :hint="__('roster::roster.export_organization_hint')" />
                            </div>
                            <div x-show="type === 'export_organizations'" x-cloak data-export-field="external_source">
                                <x-atrium::form.input name="filters[external_source]" id="export-external-source" :label="__('roster::roster.external_source')" :hint="__('roster::roster.external_source_filter_hint')" />
                            </div>
                            <div>
                                <x-atrium::icon-button icon="arrow-down-tray" :label="__('roster::roster.start_export')" variant="primary" type="submit" data-testid="start-export" />
                            </div>
                        </form>
                    </x-atrium::card>
                    @endif
                </div>
            @endunless

            @if ($transfers->isEmpty())
                <x-atrium::empty-state :title="__('roster::roster.no_transfers')" />
            @else
                <x-atrium::table striped>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('roster::roster.type') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.organization') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.requested_by') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.status') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.rows') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.created') }}</x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>
                    @foreach ($transfers as $transfer)
                        <x-atrium::table.row>
                            <x-atrium::table.cell>
                                <a href="{{ route('atrium.roster.transfers.show', $transfer->id) }}" class="font-medium underline-offset-2 hover:underline">{{ $transfer->type->label() }}</a>
                            </x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $transfer->organization?->name ?? __('roster::roster.none') }}</x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $transfer->requester ? ($directory->name($transfer->requester) ?? $directory->email($transfer->requester)) : __('roster::roster.none') }}</x-atrium::table.cell>
                            <x-atrium::table.cell>@include('roster::ui.partials.status-dot', ['status' => $transfer->status, 'variant' => null, 'label' => null, 'testid' => null])</x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $transfer->row_count }}</x-atrium::table.cell>
                            <x-atrium::table.cell>
                                {{ $transfer->created_at?->diffForHumans() }}
                                <x-atrium::screen-sections :screen="$resolved" slot="row.actions" :row="$transfer" />
                            </x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>
                <x-atrium::pagination :paginator="$transfers" />
            @endif

            <x-atrium::screen-sections :screen="$resolved" slot="after" />
        @endif
    </div>
</x-atrium::layout>
