<x-atrium::layout :title="$team->name">
    <x-atrium::page-header :title="$team->name" :description="$organization->name">
        <x-slot:actions>
            <x-atrium::icon-button icon="arrow-left" :label="$organization->name" variant="ghost" :href="route('atrium.roster.organizations.show', [$organization, 'tab' => 'teams'])" />
        </x-slot:actions>
    </x-atrium::page-header>

    <div class="mt-5 grid gap-4 lg:grid-cols-2">
        <div class="flex flex-col gap-4 lg:col-span-2">
            @include('roster::impersonation-banner', ['bannerClass' => 'rounded-radius'])
            <x-atrium::flash />
        </div>

        <x-atrium::card :title="__('roster::roster.members')">
            @if ($members->isEmpty())
                <p class="text-sm">{{ __('roster::roster.no_members') }}</p>
            @else
                <ul class="flex flex-col gap-2">
                    @foreach ($members as $membership)
                        @if ($membership->user)
                            <li class="flex items-center justify-between gap-2">
                                <span>{{ $directory->name($membership->user) ?? $directory->email($membership->user) }}</span>
                                @rosterCan('roster.teams.manage', $team)
                                <form method="POST" action="{{ route('atrium.roster.teams.members.destroy', [$organization, $team->slug, $membership->user->getRouteKey()]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-atrium::icon-button icon="user-minus" :label="__('roster::roster.remove')" type="submit" size="sm" variant="ghost" data-testid="remove-team-member" />
                                </form>
                                @endrosterCan
                            </li>
                        @endif
                    @endforeach
                </ul>
                <x-atrium::pagination :paginator="$members" />
            @endif

            @rosterCan('roster.teams.manage', $team)
            <form method="POST" action="{{ route('atrium.roster.teams.members.store', [$organization, $team->slug]) }}" class="mt-4 flex items-start gap-2">
                @csrf
                <x-atrium::form.select
                    name="user"
                    :label="__('roster::roster.add_member')"
                    :options="$candidates"
                    wrapper="w-64" />
                <x-atrium::form.actions>
                    <x-atrium::icon-button icon="user-plus" :label="__('roster::roster.add_member')" variant="primary" type="submit" data-testid="add-team-member" />
                </x-atrium::form.actions>
            </form>
            @endrosterCan
        </x-atrium::card>

        @rosterCan('roster.teams.manage', $team)
        <x-atrium::card :title="__('roster::roster.settings')" data-testid="team-settings">
            <form method="POST" action="{{ route('atrium.roster.teams.update', [$organization, $team->slug]) }}" class="flex flex-col gap-4">
                @csrf
                @method('PATCH')
                <x-atrium::form.input name="name" :label="__('roster::roster.name')" :value="old('name', $team->name)" required />
                <x-atrium::form.input name="slug" :label="__('roster::roster.slug')" :value="old('slug', $team->slug)" required />
                <div>
                    <x-atrium::icon-button icon="check" :label="__('roster::roster.save')" variant="primary" type="submit" data-testid="save-team" />
                </div>
            </form>

            @rosterCan('roster.teams.manage', $organization)
            <form method="POST" action="{{ route('atrium.roster.teams.destroy', [$organization, $team->slug]) }}" class="mt-4">
                @csrf
                @method('DELETE')
                <x-atrium::icon-button icon="trash" :label="__('roster::roster.delete')" type="submit" variant="danger" data-testid="delete-team" />
            </form>
            @endrosterCan
        </x-atrium::card>
        @endrosterCan

        @rosterCan('roster.audit.view', $team)
        <x-atrium::audit-trail source="roster" :subject="$team" class="lg:col-span-2" />
        @endrosterCan
    </div>
</x-atrium::layout>
