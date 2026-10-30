@use(RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus)
@inject('directory', 'RefactorCircus\Roster\Support\Users')
{{-- The `roster.transfer` screen: an open registered tab's panel replaces the
     page; `aside` sits beside it only while it has sections to show. --}}
@php($aside = $resolved->openRegisteredTab === null && $resolved->sections('aside') !== [])

<x-atrium::layout :title="$transfer->type->label()">
    <x-atrium::page-header :title="$transfer->type->label()" :description="$transfer->organization?->name">
        <x-slot:actions>
            @if ($downloadable)
                <x-atrium::icon-button icon="arrow-down-tray" :label="__('roster::roster.download_csv')" variant="primary" :href="route('atrium.roster.transfers.download', $transfer->id)" data-testid="download-transfer" />
            @endif
            <x-atrium::icon-button icon="arrow-left" :label="__('roster::roster.transfers')" variant="ghost" :href="route('atrium.roster.transfers.index', array_filter(['organization' => $transfer->organization?->slug]))" />
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
                <x-atrium::description-list>
                    <x-atrium::description-list.item :term="__('roster::roster.status')">@include('roster::ui.partials.status-dot', ['status' => $transfer->status, 'testid' => 'transfer-status', 'variant' => null, 'label' => null])</x-atrium::description-list.item>
                    <x-atrium::description-list.item :term="__('roster::roster.requested_by')">{{ $transfer->requester ? ($directory->name($transfer->requester) ?? $directory->email($transfer->requester)) : __('roster::roster.none') }}</x-atrium::description-list.item>
                    <x-atrium::description-list.item :term="__('roster::roster.rows')">{{ $transfer->row_count }}</x-atrium::description-list.item>
                    @if ($transfer->expires_at && $transfer->status === TransferStatus::AwaitingConfirmation)
                        <x-atrium::description-list.item :term="__('roster::roster.confirm_by')">{{ $transfer->expires_at->toDayDateTimeString() }}</x-atrium::description-list.item>
                    @endif
                    @if ($transfer->finished_at)
                        <x-atrium::description-list.item :term="__('roster::roster.finished')">{{ $transfer->finished_at->toDayDateTimeString() }}</x-atrium::description-list.item>
                    @endif
                </x-atrium::description-list>

                @if ($transfer->report['error'] ?? null)
                    <x-atrium::alert variant="danger" class="mt-4">{{ $transfer->report['error'] }}</x-atrium::alert>
                @endif

                @if ($progress && $transfer->status === TransferStatus::Running)
                    <x-atrium::progress class="mt-4" :value="$progress['done']" :max="max(1, $progress['total'])" :label="__('roster::roster.rows_applied', $progress)" />
                @endif

                @if ($transfer->summary())
                    <div class="mt-4 flex flex-wrap gap-2" data-testid="transfer-summary">
                        @foreach ($transfer->summary() as $action => $count)
                            <x-atrium::badge>{{ __('roster::roster.row_'.$action) }}: {{ $count }}</x-atrium::badge>
                        @endforeach
                    </div>
                @endif

                @if (! $transfer->status->isFinished())
                    <div class="mt-4 flex gap-2">
                        @if ($transfer->status === TransferStatus::AwaitingConfirmation && $canConfirm)
                            <form method="POST" action="{{ route('atrium.roster.transfers.confirm', $transfer->id) }}">
                                @csrf
                                <x-atrium::icon-button icon="check" :label="__('roster::roster.confirm_import')" variant="primary" type="submit" data-testid="confirm-import" />
                            </form>
                        @endif
                        <form method="POST" action="{{ route('atrium.roster.transfers.cancel', $transfer->id) }}">
                            @csrf
                            @method('DELETE')
                            <x-atrium::icon-button icon="x-mark" :label="__('roster::roster.cancel')" type="submit" variant="danger" data-testid="cancel-transfer" />
                        </form>
                    </div>
                @endif
            </x-atrium::card>

            @if ($rows->isNotEmpty())
                <x-atrium::table striped>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('roster::roster.line') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.row') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.planned') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.result') }}</x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>
                    @foreach ($rows as $row)
                        <x-atrium::table.row>
                            <x-atrium::table.cell>{{ $row['line'] ?? '' }}</x-atrium::table.cell>
                            <x-atrium::table.cell class="text-xs">{{ collect((array) ($row['values'] ?? []))->filter()->join(' · ') }}</x-atrium::table.cell>
                            <x-atrium::table.cell>
                                <x-atrium::badge>{{ __('roster::roster.row_'.($row['action'] ?? 'skip')) }}</x-atrium::badge>
                                <span class="text-xs opacity-70">{{ implode(' ', (array) ($row['reasons'] ?? [])) }}</span>
                            </x-atrium::table.cell>
                            <x-atrium::table.cell>
                                @isset($row['result'])
                                    <x-atrium::badge>{{ __('roster::roster.row_'.$row['result']) }}</x-atrium::badge>
                                    <span class="text-xs opacity-70">{{ implode(' ', (array) ($row['result_reasons'] ?? [])) }}</span>
                                @endisset
                            </x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>
                <x-atrium::pagination :paginator="$rows" />
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
