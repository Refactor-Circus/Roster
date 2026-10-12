<x-atrium::layout :title="__('roster::roster.organizations')">
    <x-atrium::page-header :title="__('roster::roster.organizations')">
        <x-slot:actions>
            <x-atrium::icon-button icon="arrows-up-down" :label="__('roster::roster.import_export')" :href="route('atrium.roster.transfers.index')" data-testid="organization-index-transfers" />
            @rosterCan('roster.organizations.create')
            <x-atrium::icon-button icon="plus" variant="primary" :label="__('roster::roster.new_organization')" :href="route('atrium.roster.organizations.create')" data-testid="new-organization" />
            @endrosterCan
        </x-slot:actions>
    </x-atrium::page-header>

    <div class="mt-5 flex flex-col gap-4">
        @include('roster::impersonation-banner', ['bannerClass' => 'rounded-radius'])
        <x-atrium::flash />

        <x-atrium::card>
            <form method="GET" action="{{ route('atrium.roster.organizations.index') }}" class="flex flex-wrap items-start gap-3">
                <x-atrium::form.input name="search" :label="__('roster::roster.search')" :value="$filters->search" wrapper="w-64" />
                <x-atrium::form.input name="source" :label="__('roster::roster.external_source')" :value="$filters->source" wrapper="w-40" />
                <x-atrium::form.input name="external_id" :label="__('roster::roster.external_id')" :value="$filters->externalId" wrapper="w-48" />
                <x-atrium::form.input name="account_number" :label="__('roster::roster.account_number')" :value="$filters->accountNumber" wrapper="w-48" />
                <x-atrium::form.select
                    name="trashed"
                    :label="__('roster::roster.show')"
                    :placeholder="__('roster::roster.show_current')"
                    :options="['only' => __('roster::roster.show_deleted')]"
                    :selected="$filters->trashed"
                    wrapper="w-40"
                    data-testid="show-filter" />

                <x-atrium::form.actions>
                    <x-atrium::icon-button icon="funnel" :label="__('roster::roster.filter')" variant="primary" type="submit" />
                    <x-atrium::icon-button icon="x-mark" :label="__('roster::roster.clear')" variant="ghost" :href="route('atrium.roster.organizations.index')" />
                </x-atrium::form.actions>
            </form>
        </x-atrium::card>

        @if ($organizations->isEmpty())
            <x-atrium::empty-state :title="__('roster::roster.no_organizations')" />
        @else
            <x-atrium::table striped>
                <x-slot:head>
                    <x-atrium::table.row>
                        <x-atrium::table.cell heading>{{ __('roster::roster.name') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.slug') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.members') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.teams') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.external') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading class="text-right">{{ __('roster::roster.actions') }}</x-atrium::table.cell>
                    </x-atrium::table.row>
                </x-slot:head>

                @foreach ($organizations as $organization)
                    <x-atrium::table.row>
                        <x-atrium::table.cell>
                            <a class="font-medium underline-offset-2 hover:underline" href="{{ route('atrium.roster.organizations.show', $organization) }}">{{ $organization->name }}</a>
                            @if ($organization->personal)
                                <x-atrium::badge>{{ __('roster::roster.personal') }}</x-atrium::badge>
                            @endif
                            @if ($organization->trashed())
                                <x-atrium::badge>{{ __('roster::roster.deleted') }}</x-atrium::badge>
                            @endif
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $organization->slug }}</x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $organization->memberships_count }}</x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $organization->teams_count }}</x-atrium::table.cell>
                        <x-atrium::table.cell class="text-xs">
                            @foreach ($organization->links->sortBy('source') as $link)
                                <div><code>{{ $link->source }}</code> {{ $link->external_id }}@if ($link->account_number) · {{ $link->account_number }}@endif</div>
                            @endforeach
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>
                            <div class="flex justify-end gap-2">
                                @if ($organization->trashed() && \RefactorCircus\Roster\Atrium\ScreenAccess::allows('roster.organizations.delete', $organization))
                                    <form method="POST" action="{{ route('atrium.roster.organizations.restore', $organization) }}">
                                        @csrf
                                        <x-atrium::icon-button icon="arrow-uturn-left" :label="__('roster::roster.restore')" variant="primary" type="submit" size="sm" data-testid="restore-organization" />
                                    </form>
                                @endif
                            </div>
                        </x-atrium::table.cell>
                    </x-atrium::table.row>
                @endforeach
            </x-atrium::table>

            <x-atrium::pagination :paginator="$organizations" />
        @endif
    </div>
</x-atrium::layout>
