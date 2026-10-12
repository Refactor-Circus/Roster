
<x-atrium::layout :title="__('roster::roster.users')">
    <x-atrium::page-header :title="__('roster::roster.users')">
        <x-slot:actions>
            <x-atrium::icon-button icon="arrows-up-down" :label="__('roster::roster.import_export')" :href="route('atrium.roster.transfers.index')" data-testid="user-transfers" />
            @rosterCan('roster.users.create')
                <x-atrium::icon-button icon="plus" variant="primary" :label="__('roster::roster.new_user')" :href="route('atrium.roster.users.create')" data-testid="new-user" />
            @endrosterCan
        </x-slot:actions>
    </x-atrium::page-header>

    <div class="mt-5 flex flex-col gap-4">
        @include('roster::impersonation-banner', ['bannerClass' => 'rounded-radius'])
        <x-atrium::flash />

        <x-atrium::card>
            <form method="GET" action="{{ route('atrium.roster.users.index') }}" class="flex flex-wrap items-start gap-3">
                <x-atrium::form.input name="search" :label="__('roster::roster.search')" :value="$filters->search" wrapper="w-64" />

                <x-atrium::form.select
                    name="status"
                    :label="__('roster::roster.status')"
                    :placeholder="__('roster::roster.all_statuses')"
                    :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])"
                    :selected="$filters->status?->value"
                    wrapper="w-48" />

                @if ($softDeletes)
                    <x-atrium::form.select
                        name="trashed"
                        :label="__('roster::roster.show')"
                        :placeholder="__('roster::roster.show_current')"
                        :options="['only' => __('roster::roster.show_deleted')]"
                        :selected="$filters->trashed"
                        wrapper="w-40"
                        data-testid="show-filter" />
                @endif

                <x-atrium::form.actions>
                    <x-atrium::icon-button icon="funnel" :label="__('roster::roster.filter')" variant="primary" type="submit" data-testid="filter-users" />
                    <x-atrium::icon-button icon="x-mark" :label="__('roster::roster.clear')" variant="ghost" :href="route('atrium.roster.users.index')" />
                </x-atrium::form.actions>
            </form>
        </x-atrium::card>

        @if ($users->isEmpty())
            <x-atrium::empty-state :title="__('roster::roster.no_users')" />
        @else
            <x-atrium::table striped>
                <x-slot:head>
                    <x-atrium::table.row>
                        <x-atrium::table.cell heading>{{ __('roster::roster.name') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.email') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.status') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading>{{ __('roster::roster.created') }}</x-atrium::table.cell>
                        <x-atrium::table.cell heading class="text-right">{{ __('roster::roster.actions') }}</x-atrium::table.cell>
                    </x-atrium::table.row>
                </x-slot:head>

                @foreach ($users as $user)
                    @php($status = $directory->status($user))
                    <x-atrium::table.row>
                        <x-atrium::table.cell>
                            <a class="flex items-center gap-2 font-medium underline-offset-2 hover:underline"
                               href="{{ route('atrium.roster.users.show', $user->getRouteKey()) }}">
                                <x-atrium::avatar size="sm" :src="$user->rosterProfile?->avatar_url" :initials="str($user->rosterProfile?->display_name ?? $directory->name($user) ?? $directory->email($user))->substr(0, 2)->upper()" />
                                {{ $user->rosterProfile?->display_name ?? $directory->name($user) ?? __('roster::roster.none') }}
                            </a>
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $directory->email($user) ?? __('roster::roster.none') }}</x-atrium::table.cell>
                        <x-atrium::table.cell>
                            @if ($directory->trashed($user))
                                <x-atrium::badge>{{ __('roster::roster.deleted') }}</x-atrium::badge>
                            @else
                                @include('roster::ui.users.partials.status-cell', ['user' => $user, 'status' => $status])
                            @endif
                        </x-atrium::table.cell>
                        <x-atrium::table.cell>{{ $user->created_at?->diffForHumans() ?? __('roster::roster.none') }}</x-atrium::table.cell>
                        <x-atrium::table.cell>
                            <div class="flex justify-end gap-2">
                                @if ($directory->trashed($user))
                                    @rosterCan('roster.users.delete')
                                    <form method="POST" action="{{ route('atrium.roster.users.restore', $user->getRouteKey()) }}">
                                        @csrf
                                        <x-atrium::icon-button icon="arrow-uturn-left" :label="__('roster::roster.restore')" variant="primary" type="submit" size="sm" data-testid="restore-user" />
                                    </form>
                                    @endrosterCan
                                @else
                                    @include('roster::ui.users.partials.activate', ['user' => $user, 'status' => $status])
                                @endif
                            </div>
                        </x-atrium::table.cell>
                    </x-atrium::table.row>
                @endforeach
            </x-atrium::table>

            <x-atrium::pagination :paginator="$users" />
        @endif
    </div>
</x-atrium::layout>
