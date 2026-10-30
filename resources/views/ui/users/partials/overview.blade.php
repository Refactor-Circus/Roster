@use(RefactorCircus\Roster\Support\Statuses)
@use(RefactorCircus\Roster\Atrium\ScreenAccess)

{{--
    The user screen's own content: the page as it is with no registered tab,
    and the `overview` tab when one is visible.
--}}
<div class="grid gap-4 lg:grid-cols-2">
    @rosterCan('roster.users.update')
    <x-atrium::card :title="__('roster::roster.account')">
        <form method="POST" action="{{ route('atrium.roster.users.update', $user->getRouteKey()) }}" class="flex flex-col gap-4">
            @csrf
            @method('PATCH')

            @if ($directory->column('name') !== null)
                <x-atrium::form.input name="name" :label="__('roster::roster.name')" :value="old('name', $directory->name($user))" required />
            @endif
            <x-atrium::form.input name="email" type="email" :label="__('roster::roster.email')" :value="old('email', $directory->email($user))" required />
            <x-atrium::form.input name="password" type="password" :label="__('roster::roster.password')" :hint="__('roster::roster.password_hint')" />

            <div>
                <x-atrium::icon-button icon="check" :label="__('roster::roster.save')" variant="primary" type="submit" data-testid="save-account" />
            </div>
        </form>
    </x-atrium::card>
    @endrosterCan

    @rosterCan('roster.users.update', null, $user)
    <x-atrium::card :title="__('roster::roster.profile')">
        <form method="POST" action="{{ route('atrium.roster.users.profile', $user->getRouteKey()) }}" class="flex flex-col gap-4">
            @csrf
            @method('PATCH')

            @include('roster::ui.users.partials.profile-fields', ['profile' => $profile])

            <div>
                <x-atrium::icon-button icon="check" :label="__('roster::roster.save')" variant="primary" type="submit" data-testid="save-profile" />
            </div>
        </form>
    </x-atrium::card>
    @endrosterCan

    @if ($status === Statuses::users()::pending() && ScreenAccess::allows('roster.users.approve'))
        <x-atrium::card :title="__('roster::roster.awaiting_approval')" class="lg:col-span-2" data-testid="approval-card">
            <p class="mb-4 text-sm">{{ __('roster::roster.awaiting_approval_hint') }}</p>

            <div class="flex flex-wrap items-start gap-3">
                <form method="POST" action="{{ route('atrium.roster.users.approve', $user->getRouteKey()) }}">
                    @csrf
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="check" :label="__('roster::roster.approve')" variant="primary" type="submit" data-testid="approve-user" />
                    </x-atrium::form.actions>
                </form>

                <form method="POST" action="{{ route('atrium.roster.users.reject', $user->getRouteKey()) }}" class="flex items-start gap-2">
                    @csrf
                    <x-atrium::form.input name="reason" id="reject-reason" :label="__('roster::roster.reason')" wrapper="w-64" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="x-mark" :label="__('roster::roster.reject')" type="submit" variant="danger" data-testid="reject-user" />
                    </x-atrium::form.actions>
                </form>
            </div>
        </x-atrium::card>
    @endif

    @rosterCan('roster.users.manage-status')
    <x-atrium::card :title="__('roster::roster.status')" class="lg:col-span-2">
        @if ($profile?->status_reason)
            <p class="mb-4 text-sm">{{ __('roster::roster.reason') }}: {{ $profile->status_reason }}</p>
        @endif

        @php($choices = collect([Statuses::users()::active(), Statuses::users()::suspended(), Statuses::users()::deactivated()])
            ->reject(fn ($choice) => $choice === $status || ($status === Statuses::users()::pending() && $choice === Statuses::users()::active()))
            ->mapWithKeys(fn ($choice) => [$choice->value => $choice->label()]))

        {{-- One form: pick the new status, give a reason. Pending accounts are activated from the approval card. --}}
        <form method="POST" action="{{ route('atrium.roster.users.status', $user->getRouteKey()) }}" class="flex flex-wrap items-start gap-3" data-testid="status-form">
            @csrf
            <x-atrium::form.select
                name="status"
                id="new-status"
                :label="__('roster::roster.change_status_to')"
                :options="$choices"
                wrapper="w-56"
                required />
            <x-atrium::form.input name="reason" id="status-reason" :label="__('roster::roster.reason')" :hint="__('roster::roster.status_reason_hint')" wrapper="w-80" />
            <x-atrium::form.actions>
                <x-atrium::icon-button icon="arrow-path" :label="__('roster::roster.update_status')" type="submit" variant="secondary" data-testid="change-status" />
            </x-atrium::form.actions>
        </form>
    </x-atrium::card>
    @endrosterCan

    <x-atrium::card :title="__('roster::roster.memberships')" class="lg:col-span-2">
        @if ($memberships->isEmpty())
            <p class="text-sm">{{ __('roster::roster.no_organizations') }}</p>
        @else
            <ul class="mb-4 flex flex-col gap-1 text-sm">
                @foreach ($memberships as $membership)
                    <li>
                        <a class="font-medium underline-offset-2 hover:underline" href="{{ route('atrium.roster.organizations.show', $membership->organization) }}">{{ $membership->organization?->name }}</a>
                        @if ($currentOrganization?->is($membership->organization))
                            <x-atrium::badge variant="primary">{{ __('roster::roster.current') }}</x-atrium::badge>
                        @endif
                        @if ($membership->teams->isNotEmpty())
                            <span class="opacity-70">— {{ $membership->teams->pluck('name')->join(', ') }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>

            @rosterCan('roster.users.update', null, $user)
            <form method="POST" action="{{ route('atrium.roster.users.context', $user->getRouteKey()) }}" class="flex flex-wrap items-start gap-2">
                @csrf
                @method('PUT')
                <x-atrium::form.select
                    name="organization"
                    :label="__('roster::roster.organization')"
                    :options="$memberships->mapWithKeys(fn ($m) => [$m->organization?->slug => $m->organization?->name])"
                    :selected="$currentOrganization?->slug"
                    wrapper="w-56" />
                <x-atrium::form.select
                    name="team"
                    :label="__('roster::roster.team')"
                    :placeholder="__('roster::roster.no_team')"
                    :options="$memberships->flatMap(fn ($m) => $m->teams->mapWithKeys(fn ($t) => [$t->slug => $m->organization?->name.' / '.$t->name]))"
                    :selected="$currentTeam?->slug"
                    wrapper="w-56" />
                <x-atrium::form.actions>
                    <x-atrium::icon-button icon="arrows-right-left" :label="__('roster::roster.switch')" variant="primary" type="submit" data-testid="switch-context" />
                </x-atrium::form.actions>
            </form>
            @endrosterCan
        @endif

        @rosterCan('roster.users.update')
        <form method="POST" action="{{ route('atrium.roster.users.domain-join', $user->getRouteKey()) }}" class="mt-4">
            @csrf
            <x-atrium::icon-button icon="globe-alt" :label="__('roster::roster.run_domain_join')" type="submit" variant="ghost" data-testid="domain-join" />
        </form>
        @endrosterCan
    </x-atrium::card>

    @rosterCan('roster.roles.view', null, $user)
    <x-atrium::card :title="__('roster::roster.roles')" class="lg:col-span-2" data-testid="roles-card">
        @if ($effective['super_admin'])
            <p class="mb-3"><x-atrium::badge variant="danger">{{ __('roster::roster.super_admin') }}</x-atrium::badge></p>
        @endif

        @if ($assignments->isEmpty())
            <p class="text-sm">{{ __('roster::roster.no_roles') }}</p>
        @else
            <ul class="mb-4 flex flex-col gap-1 text-sm">
                @foreach ($assignments as $assignment)
                    <li class="flex items-center justify-between gap-2">
                        <span>
                            <span class="font-medium">{{ $assignment->role?->name }}</span>
                            <span class="opacity-70">— {{ $assignment->team ? $assignment->organization?->name.' / '.$assignment->team->name : ($assignment->organization?->name ?? __('roster::roster.scope_global')) }}</span>
                        </span>
                        @rosterCan('roster.roles.assign', $assignment->team ?? $assignment->organization)
                        <form method="POST" action="{{ route('atrium.roster.users.roles.destroy', [$user->getRouteKey(), $assignment->id]) }}">
                            @csrf
                            @method('DELETE')
                            <x-atrium::icon-button icon="no-symbol" :label="__('roster::roster.revoke')" type="submit" size="sm" variant="ghost" data-testid="revoke-role" />
                        </form>
                        @endrosterCan
                    </li>
                @endforeach
            </ul>
        @endif

        {{-- Assign where the actor may: globally, or in this user's organizations. --}}
        @php($assignGlobally = ScreenAccess::allows('roster.roles.assign'))
        @php($assignIn = $assignGlobally ? $memberships : $memberships->filter(fn ($m) => $m->organization && ScreenAccess::allows('roster.roles.assign', $m->organization)))

        @if ($assignGlobally || $assignIn->isNotEmpty())
        <form method="POST" action="{{ route('atrium.roster.users.roles.store', $user->getRouteKey()) }}" class="flex flex-wrap items-start gap-2">
            @csrf
            <x-atrium::form.select
                name="role"
                :label="__('roster::roster.role')"
                :options="$assignableRoles->mapWithKeys(fn ($role) => [$role->id => $role->name.' ('.$role->scope->label().($role->organization ? ', '.$role->organization->name : '').')'])"
                wrapper="w-72" />
            <x-atrium::form.select
                name="organization"
                :label="__('roster::roster.organization')"
                :placeholder="$assignGlobally ? __('roster::roster.scope_global') : null"
                :options="$assignIn->mapWithKeys(fn ($m) => [$m->organization?->slug => $m->organization?->name])"
                wrapper="w-56" />
            <x-atrium::form.select
                name="team"
                :label="__('roster::roster.team')"
                :placeholder="__('roster::roster.no_team')"
                :options="$assignIn->flatMap(fn ($m) => $m->teams->mapWithKeys(fn ($t) => [$t->slug => $m->organization?->name.' / '.$t->name]))"
                wrapper="w-56" />
            <x-atrium::form.actions>
                <x-atrium::icon-button icon="shield-check" :label="__('roster::roster.assign')" variant="primary" type="submit" data-testid="assign-role" />
            </x-atrium::form.actions>
        </form>
        @endif

        <details class="mt-4 text-sm">
            <summary class="cursor-pointer">{{ __('roster::roster.effective_permissions') }} ({{ count($effective['permissions']) }})</summary>
            <p class="mt-2 font-mono text-xs leading-relaxed">{{ implode(', ', $effective['permissions']) ?: __('roster::roster.none') }}</p>
        </details>
    </x-atrium::card>
    @endrosterCan

    @rosterCan('roster.users.impersonate')
        <x-atrium::card :title="__('roster::roster.impersonate')" class="lg:col-span-2" data-testid="impersonate-card">
            <form method="POST" action="{{ route('atrium.roster.users.impersonate', $user->getRouteKey()) }}" class="flex flex-wrap items-start gap-2">
                @csrf
                <x-atrium::form.input name="reason" id="impersonation-reason" :label="__('roster::roster.reason')" :hint="__('roster::roster.impersonation_reason_hint')" wrapper="w-80" required />
                <x-atrium::form.actions>
                    <x-atrium::icon-button icon="eye" :label="__('roster::roster.impersonate')" type="submit" variant="warning" data-testid="impersonate-user" />
                </x-atrium::form.actions>
            </form>
        </x-atrium::card>
    @endrosterCan

    @rosterCan('roster.users.delete')
    <x-atrium::card :title="__('roster::roster.danger_zone')" class="lg:col-span-2" data-testid="danger-zone">
        @if ($directory->softDeletes())
            <x-atrium::alert variant="warning" :title="__('roster::roster.delete_user_soft_title')">
                {{ __('roster::roster.delete_user_soft_warning') }}
            </x-atrium::alert>
        @else
            <x-atrium::alert variant="danger" :title="__('roster::roster.delete_user_warning_title')">
                {{ __('roster::roster.delete_user_warning') }}
            </x-atrium::alert>
        @endif

        <form method="POST" action="{{ route('atrium.roster.users.destroy', $user->getRouteKey()) }}" class="mt-4 flex flex-wrap items-center gap-4">
            @csrf
            @method('DELETE')
            <x-atrium::form.checkbox name="confirm" value="1" id="confirm-delete" :label="$directory->softDeletes() ? __('roster::roster.delete_user_soft_confirm') : __('roster::roster.delete_user_confirm')" required />
            <x-atrium::icon-button icon="trash" :label="__('roster::roster.delete_user')" type="submit" variant="danger" data-testid="delete-user" />
        </form>
    </x-atrium::card>
    @endrosterCan
</div>
