<x-atrium::layout :title="$organization->name">
    <x-atrium::page-header :title="$organization->name" :description="$organization->slug">
        <x-slot:actions>
            {{-- Members and teams are what an organization imports and exports. --}}
            @if (in_array($tab, ['members', 'teams'], true) && \RefactorCircus\Roster\Atrium\ScreenAccess::allows('roster.members.view', $organization))
                <x-atrium::icon-button icon="arrows-up-down" :label="__('roster::roster.import_export')" :href="route('atrium.roster.transfers.index', ['organization' => $organization->slug])" data-testid="organization-transfers" />
            @endif
        </x-slot:actions>
    </x-atrium::page-header>

    <div class="mt-5 flex flex-col gap-4">
        @include('roster::impersonation-banner', ['bannerClass' => 'rounded-radius'])
        <x-atrium::flash />

        <nav class="flex flex-wrap gap-2" aria-label="{{ $organization->name }}">
            @php($tabIcons = ['members' => 'users', 'teams' => 'user-group', 'invitations' => 'envelope', 'roles' => 'shield-check', 'sso' => 'arrow-right-end-on-rectangle', 'scim' => 'server-stack', 'mcp' => 'globe-alt', 'activity' => 'clipboard-document-list', 'settings' => 'cog'])
            @foreach ($tabs as $name)
                <x-atrium::icon-button
                    :icon="$tabIcons[$name]"
                    :label="__('roster::roster.'.$name)"
                    :variant="$tab === $name ? 'primary' : 'ghost'"
                    :href="route('atrium.roster.organizations.show', [$organization, 'tab' => $name])"
                    :aria-current="$tab === $name ? 'page' : null"
                    data-testid="tab-{{ $name }}" />
            @endforeach
        </nav>

        @if ($tab === 'members')
            @if ($organization->owner_id === null)
                <x-atrium::alert variant="warning" data-testid="no-owner">{{ __('roster::roster.no_owner') }}</x-atrium::alert>
            @endif

            @rosterCan('roster.members.manage', $organization)
            <x-atrium::card :title="__('roster::roster.add_member')">
                <form method="POST" action="{{ route('atrium.roster.organizations.members.store', $organization) }}" class="flex items-start gap-2">
                    @csrf
                    <x-atrium::form.input name="user" :label="__('roster::roster.user')" :hint="__('roster::roster.user_key_hint')" wrapper="w-64" required />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="user-plus" :label="__('roster::roster.add_member')" variant="primary" type="submit" data-testid="add-member" />
                    </x-atrium::form.actions>
                </form>
            </x-atrium::card>
            @endrosterCan

            @if ($members->isEmpty())
                <x-atrium::empty-state :title="__('roster::roster.no_members')" />
            @else
                <x-atrium::table striped>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('roster::roster.member') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.status') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.teams') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.source') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading class="text-right">{{ __('roster::roster.actions') }}</x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>

                    @foreach ($members as $membership)
                        @php($member = $membership->user)
                        <x-atrium::table.row>
                            <x-atrium::table.cell>
                                @if ($member)
                                    <a class="font-medium underline-offset-2 hover:underline" href="{{ route('atrium.roster.users.show', $member->getRouteKey()) }}">{{ $directory->name($member) ?? $directory->email($member) }}</a>
                                    @if ($organization->isOwnedBy($member))
                                        <x-atrium::badge variant="primary">{{ __('roster::roster.owner') }}</x-atrium::badge>
                                    @endif
                                @endif
                            </x-atrium::table.cell>
                            <x-atrium::table.cell>
                                @if ($member)
                                    @include('roster::ui.users.partials.status-cell', ['user' => $member, 'status' => $directory->status($member)])
                                @endif
                            </x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $membership->teams->pluck('name')->join(', ') ?: __('roster::roster.none') }}</x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $membership->source->value }}</x-atrium::table.cell>
                            <x-atrium::table.cell>
                                <div class="flex justify-end gap-2">
                                    @if ($member)
                                        @include('roster::ui.users.partials.activate', ['user' => $member, 'status' => $directory->status($member)])
                                    @endif
                                    @if ($member && ! $organization->isOwnedBy($member))
                                        @if (! $organization->personal && \RefactorCircus\Roster\Atrium\ScreenAccess::allows('roster.organizations.transfer', $organization))
                                            <form method="POST" action="{{ route('atrium.roster.organizations.transfer', $organization) }}">
                                                @csrf
                                                <input type="hidden" name="user" value="{{ $member->getRouteKey() }}">
                                                <x-atrium::icon-button icon="key" :label="__('roster::roster.make_owner')" type="submit" size="sm" variant="ghost" data-testid="make-owner" />
                                            </form>
                                        @endif
                                        @rosterCan('roster.members.manage', $organization)
                                        <form method="POST" action="{{ route('atrium.roster.organizations.members.destroy', [$organization, $member->getRouteKey()]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-atrium::icon-button icon="user-minus" :label="__('roster::roster.remove')" type="submit" size="sm" variant="danger" data-testid="remove-member" />
                                        </form>
                                        @endrosterCan
                                    @endif
                                </div>
                            </x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>
                <x-atrium::pagination :paginator="$members" />
            @endif
        @elseif ($tab === 'teams')
            @rosterCan('roster.teams.manage', $organization)
            <x-atrium::card :title="__('roster::roster.new_team')">
                <form method="POST" action="{{ route('atrium.roster.teams.store', $organization) }}" class="flex flex-wrap items-start gap-2">
                    @csrf
                    <x-atrium::form.input name="name" :label="__('roster::roster.name')" wrapper="w-64" required />
                    <x-atrium::form.input name="slug" :label="__('roster::roster.slug')" :hint="__('roster::roster.slug_hint')" wrapper="w-64" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="plus" :label="__('roster::roster.create')" variant="primary" type="submit" data-testid="create-team" />
                    </x-atrium::form.actions>
                </form>
            </x-atrium::card>
            @endrosterCan

            @if ($teams->isEmpty())
                <x-atrium::empty-state :title="__('roster::roster.no_teams')" />
            @else
                <x-atrium::table striped>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('roster::roster.team') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.members') }}</x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>

                    @foreach ($teams as $team)
                        <x-atrium::table.row>
                            <x-atrium::table.cell>
                                <a class="font-medium underline-offset-2 hover:underline" href="{{ route('atrium.roster.teams.show', [$organization, $team->slug]) }}">{{ $team->name }}</a>
                            </x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $team->seats_count }}</x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>
                <x-atrium::pagination :paginator="$teams" />
            @endif
        @elseif ($tab === 'invitations')
            @rosterCan('roster.invitations.manage', $organization)
            <x-atrium::card :title="__('roster::roster.invite')">
                <form method="POST" action="{{ route('atrium.roster.invitations.store', $organization) }}" class="flex max-w-2xl flex-col gap-3">
                    @csrf
                    <x-atrium::form.input name="email" type="email" :label="__('roster::roster.email')" wrapper="w-80" required />

                    @if ($teams->isNotEmpty())
                        <fieldset class="flex flex-wrap gap-4">
                            <legend class="mb-1 text-sm font-medium">{{ __('roster::roster.teams') }}</legend>
                            @foreach ($teams as $team)
                                <x-atrium::form.checkbox name="teams[]" :value="$team->slug" :id="'invite-team-'.$team->slug" :label="$team->name" />
                            @endforeach
                        </fieldset>
                    @endif

                    <div>
                        <x-atrium::icon-button icon="paper-airplane" :label="__('roster::roster.invite')" variant="primary" type="submit" data-testid="send-invitation" />
                    </div>
                </form>
            </x-atrium::card>
            @endrosterCan

            @if ($invitations->isEmpty())
                <x-atrium::empty-state :title="__('roster::roster.no_invitations')" />
            @else
                <x-atrium::table striped>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('roster::roster.email') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.status') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.expires') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading class="text-right">{{ __('roster::roster.actions') }}</x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>

                    @foreach ($invitations as $invitation)
                        <x-atrium::table.row>
                            <x-atrium::table.cell>{{ $invitation->email }}</x-atrium::table.cell>
                            <x-atrium::table.cell>@include('roster::ui.partials.status-dot', ['status' => $invitation->status(), 'testid' => 'invitation-status', 'variant' => null, 'label' => null])</x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $invitation->expires_at->diffForHumans() }}</x-atrium::table.cell>
                            <x-atrium::table.cell>
                                @if ($invitation->status() === $pending && \RefactorCircus\Roster\Atrium\ScreenAccess::allows('roster.invitations.manage', $organization))
                                    <form method="POST" action="{{ route('atrium.roster.invitations.revoke', [$organization, $invitation->id]) }}" class="flex justify-end">
                                        @csrf
                                        @method('DELETE')
                                        <x-atrium::icon-button icon="no-symbol" :label="__('roster::roster.revoke')" type="submit" size="sm" variant="ghost" data-testid="revoke-invitation" />
                                    </form>
                                @endif
                            </x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>
                <x-atrium::pagination :paginator="$invitations" />
            @endif
        @elseif ($tab === 'sso')
            @if ($ssoConnections->isEmpty())
                <x-atrium::empty-state :title="__('roster::roster.no_sso_connections')" />
            @else
                <x-atrium::table striped>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('roster::roster.name') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.protocol') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.sso_identities') }}</x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>
                    @foreach ($ssoConnections as $connection)
                        <x-atrium::table.row>
                            <x-atrium::table.cell>
                                <a class="font-medium underline-offset-2 hover:underline" href="{{ route('atrium.roster.sso.show', $connection->slug) }}">{{ $connection->name }}</a>
                                @if ($connection->enforced)
                                    <x-atrium::badge variant="warning">{{ __('roster::roster.enforced') }}</x-atrium::badge>
                                @endif
                                @unless ($connection->enabled)
                                    <x-atrium::badge>{{ __('roster::roster.none') }}</x-atrium::badge>
                                @endunless
                            </x-atrium::table.cell>
                            <x-atrium::table.cell>{{ __('roster::roster.protocol_'.$connection->protocol) }}</x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $connection->identities_count }}</x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>
                <x-atrium::pagination :paginator="$ssoConnections" />
            @endif

            @rosterCan('roster.sso.manage', $organization)
            <x-atrium::card :title="__('roster::roster.new_sso_connection')">
                <form method="POST" action="{{ route('atrium.roster.sso.store', $organization) }}" class="flex max-w-3xl flex-col gap-4" x-data="{ protocol: @js(old('protocol', 'oidc')) }" x-init="protocol = $el.querySelector('select[name=protocol]').value">
                    @csrf
                    <div class="flex flex-wrap gap-3">
                        <x-atrium::form.input name="name" :label="__('roster::roster.name')" wrapper="w-56" required />
                        <x-atrium::form.select
                            name="protocol"
                            :label="__('roster::roster.protocol')"
                            :options="['oidc' => __('roster::roster.protocol_oidc'), 'azure' => __('roster::roster.protocol_azure'), 'saml' => __('roster::roster.protocol_saml')]"
                            :selected="old('protocol', 'oidc')"
                            x-model="protocol"
                            wrapper="w-56" />
                    </div>
                    @include('roster::ui.sso.partials.fields', ['settings' => [], 'editing' => false])
                    <div>
                        <x-atrium::icon-button icon="plus" :label="__('roster::roster.create')" variant="primary" type="submit" data-testid="create-sso" />
                    </div>
                </form>
            </x-atrium::card>
            @endrosterCan
        @elseif ($tab === 'scim')
            @if (session('roster_scim_token'))
                <x-atrium::alert variant="warning" :title="__('roster::roster.scim_token_created')">
                    <code class="break-all font-mono text-sm" data-testid="scim-token">{{ session('roster_scim_token') }}</code>
                </x-atrium::alert>
            @endif

            <x-atrium::card :title="__('roster::roster.scim_base_url')">
                <code class="break-all font-mono text-sm" data-testid="scim-base-url">{{ url(trim((string) config('roster.scim.prefix', 'scim/v2'), '/').'/'.$organization->slug) }}</code>
            </x-atrium::card>

            @if ($scimTokens->isEmpty())
                <x-atrium::empty-state :title="__('roster::roster.no_scim_tokens')" />
            @else
                <x-atrium::table striped>
                    <x-slot:head>
                        <x-atrium::table.row>
                            <x-atrium::table.cell heading>{{ __('roster::roster.name') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.last_login') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading>{{ __('roster::roster.expires') }}</x-atrium::table.cell>
                            <x-atrium::table.cell heading class="text-right">{{ __('roster::roster.actions') }}</x-atrium::table.cell>
                        </x-atrium::table.row>
                    </x-slot:head>
                    @foreach ($scimTokens as $token)
                        <x-atrium::table.row>
                            <x-atrium::table.cell>
                                {{ $token->name }}
                                @if ($token->revoked_at)
                                    <x-atrium::badge>{{ __('roster::roster.revoked') }}</x-atrium::badge>
                                @endif
                            </x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $token->last_used_at?->diffForHumans() ?? __('roster::roster.never') }}</x-atrium::table.cell>
                            <x-atrium::table.cell>{{ $token->expires_at?->toFormattedDateString() ?? __('roster::roster.never') }}</x-atrium::table.cell>
                            <x-atrium::table.cell>
                                @unless ($token->revoked_at)
                                    <form method="POST" action="{{ route('atrium.roster.scim-tokens.revoke', $token->id) }}" class="flex justify-end">
                                        @csrf
                                        @method('DELETE')
                                        <x-atrium::icon-button icon="no-symbol" :label="__('roster::roster.revoke')" type="submit" size="sm" variant="danger" />
                                    </form>
                                @endunless
                            </x-atrium::table.cell>
                        </x-atrium::table.row>
                    @endforeach
                </x-atrium::table>
                <x-atrium::pagination :paginator="$scimTokens" />
            @endif

            <x-atrium::card :title="__('roster::roster.new_scim_token')">
                <form method="POST" action="{{ route('atrium.roster.scim-tokens.store', $organization) }}" class="flex flex-wrap items-start gap-3">
                    @csrf
                    <x-atrium::form.input name="name" id="scim-token-name" :label="__('roster::roster.name')" wrapper="w-64" required />
                    <x-atrium::form.input name="expires_in_days" type="number" :label="__('roster::roster.expires_in_days')" wrapper="w-40" />
                    <x-atrium::form.select
                        name="sso_connection"
                        :label="__('roster::roster.link_sso')"
                        :placeholder="__('roster::roster.none')"
                        :options="$ssoConnections->mapWithKeys(fn ($c) => [$c->slug => $c->name])"
                        wrapper="w-56" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="plus" :label="__('roster::roster.create')" variant="primary" type="submit" data-testid="create-scim-token" />
                    </x-atrium::form.actions>
                </form>
            </x-atrium::card>
        @elseif ($tab === 'mcp')
            @include('roster::ui.partials.redirect-domains', [
                'domains' => $redirectDomains,
                'manage' => true,
                'storeRoute' => route('atrium.roster.organizations.redirect-domains.store', $organization),
                'destroyRoute' => fn ($domain) => route('atrium.roster.organizations.redirect-domains.destroy', [$organization, $domain->id]),
            ])
        @elseif ($tab === 'activity')
            <x-atrium::audit-trail source="roster" :scope="$organization" />
        @elseif ($tab === 'roles')
            <x-atrium::card :title="__('roster::roster.assignments')">
                @if ($assignments->isEmpty())
                    <p class="text-sm">{{ __('roster::roster.no_roles') }}</p>
                @else
                    <ul class="flex flex-col gap-1 text-sm">
                        @foreach ($assignments as $assignment)
                            <li>
                                @if ($assignment->user)
                                    <a class="font-medium underline-offset-2 hover:underline" href="{{ route('atrium.roster.users.show', $assignment->user->getRouteKey()) }}">{{ $directory->name($assignment->user) ?? $directory->email($assignment->user) }}</a>
                                @endif
                                — {{ $assignment->role?->name }}@if ($assignment->team) ({{ $assignment->team->name }})@endif
                            </li>
                        @endforeach
                    </ul>
                    <x-atrium::pagination :paginator="$assignments" />
                @endif
            </x-atrium::card>

            <x-atrium::card :title="__('roster::roster.roles')">
                <ul class="flex flex-col gap-1 text-sm">
                    @foreach ($roles as $role)
                        <li>
                            <a class="font-medium underline-offset-2 hover:underline" href="{{ route('atrium.roster.roles.show', $role->id) }}">{{ $role->name }}</a>
                            <span class="opacity-70">— {{ $role->scope->label() }}, {{ $role->organization ? __('roster::roster.own_role') : __('roster::roster.shared') }}</span>
                        </li>
                    @endforeach
                </ul>
                @rosterCan('roster.roles.manage', $organization)
                    <x-atrium::icon-button icon="arrow-right" :label="__('roster::roster.manage_roles')" class="mt-3" variant="ghost" :href="route('atrium.roster.roles.index', ['organization' => $organization->slug])" data-testid="manage-roles" />
                @endrosterCan
            </x-atrium::card>
        @else
            @rosterCan('roster.organizations.update', $organization)
            <x-atrium::card :title="__('roster::roster.settings')">
                <form method="POST" action="{{ route('atrium.roster.organizations.update', $organization) }}" class="flex max-w-2xl flex-col gap-4">
                    @csrf
                    @method('PATCH')

                    <x-atrium::form.input name="name" :label="__('roster::roster.name')" :value="old('name', $organization->name)" required />
                    <x-atrium::form.input name="slug" :label="__('roster::roster.slug')" :value="old('slug', $organization->slug)" required />
                    <x-atrium::form.textarea name="domains" :label="__('roster::roster.domains')" :value="old('domains', $organization->domains->pluck('domain')->join(PHP_EOL))" :hint="__('roster::roster.domains_hint')" rows="3" />
                    <x-atrium::form.checkbox name="auto_join" value="1" :checked="$organization->auto_join" :label="__('roster::roster.auto_join')" :hint="__('roster::roster.auto_join_hint')" />
                    @php($accountStatuses = \RefactorCircus\Roster\Support\Statuses::users())
                    <x-atrium::form.select
                        name="provisioned_status"
                        :label="__('roster::roster.provisioned_status')"
                        :hint="__('roster::roster.provisioned_status_hint')"
                        :options="[$accountStatuses::active()->value => $accountStatuses::active()->label(), $accountStatuses::pending()->value => $accountStatuses::pending()->label()]"
                        :selected="old('provisioned_status', $organization->provisioned_status)"
                        wrapper="w-72" />
                    @include('roster::ui.partials.status-fields', ['statuses' => \RefactorCircus\Roster\Support\Statuses::organizations()::cases(), 'record' => $organization])

                    <div>
                        <x-atrium::icon-button icon="check" :label="__('roster::roster.save')" variant="primary" type="submit" data-testid="save-organization" />
                    </div>
                </form>
            </x-atrium::card>
            @endrosterCan

            <x-atrium::card :title="__('roster::roster.external_links')">
                @if ($organization->links->isEmpty())
                    <p class="text-sm">{{ __('roster::roster.no_external_links') }}</p>
                @else
                    <x-atrium::table>
                        <x-slot:head>
                            <x-atrium::table.row>
                                <x-atrium::table.cell heading>{{ __('roster::roster.external_source') }}</x-atrium::table.cell>
                                <x-atrium::table.cell heading>{{ __('roster::roster.external_id') }}</x-atrium::table.cell>
                                <x-atrium::table.cell heading>{{ __('roster::roster.account_number') }}</x-atrium::table.cell>
                                <x-atrium::table.cell heading>{{ __('roster::roster.synced') }}</x-atrium::table.cell>
                                <x-atrium::table.cell heading class="text-right">{{ __('roster::roster.actions') }}</x-atrium::table.cell>
                            </x-atrium::table.row>
                        </x-slot:head>
                        @foreach ($organization->links->sortBy('source') as $link)
                            <x-atrium::table.row data-testid="external-link">
                                <x-atrium::table.cell><code>{{ $link->source }}</code></x-atrium::table.cell>
                                <x-atrium::table.cell>{{ $link->external_id }}</x-atrium::table.cell>
                                <x-atrium::table.cell>{{ $link->account_number ?? __('roster::roster.none') }}</x-atrium::table.cell>
                                <x-atrium::table.cell>{{ $link->synced_at?->diffForHumans() ?? __('roster::roster.never') }}</x-atrium::table.cell>
                                <x-atrium::table.cell>
                                    @rosterCan('roster.organizations.update', $organization)
                                    <form method="POST" action="{{ route('atrium.roster.organizations.links.destroy', [$organization, $link->source]) }}" class="flex justify-end">
                                        @csrf
                                        @method('DELETE')
                                        <x-atrium::icon-button icon="link-slash" :label="__('roster::roster.unlink')" type="submit" size="sm" variant="ghost" data-testid="unlink-organization" />
                                    </form>
                                    @endrosterCan
                                </x-atrium::table.cell>
                            </x-atrium::table.row>
                        @endforeach
                    </x-atrium::table>
                @endif

                @rosterCan('roster.organizations.update', $organization)
                <form method="POST" action="{{ route('atrium.roster.organizations.links.store', $organization) }}" class="mt-4 flex flex-wrap items-start gap-3">
                    @csrf
                    <x-atrium::form.input name="source" id="link-source" :label="__('roster::roster.external_source')" :hint="__('roster::roster.external_source_hint')" wrapper="w-40" required />
                    <x-atrium::form.input name="external_id" id="link-external-id" :label="__('roster::roster.external_id')" wrapper="w-56" required />
                    <x-atrium::form.input name="account_number" id="link-account-number" :label="__('roster::roster.account_number')" wrapper="w-48" />
                    <x-atrium::form.actions>
                        <x-atrium::icon-button icon="link" :label="__('roster::roster.link')" variant="primary" type="submit" data-testid="link-organization" />
                    </x-atrium::form.actions>
                </form>
                @endrosterCan
            </x-atrium::card>

            @if (! $organization->personal && \RefactorCircus\Roster\Atrium\ScreenAccess::allows('roster.organizations.delete', $organization))
                <x-atrium::card :title="__('roster::roster.danger_zone')" data-testid="danger-zone">
                    <x-atrium::alert variant="warning" :title="__('roster::roster.delete_organization_soft_title')">
                        {{ __('roster::roster.delete_organization_soft_warning') }}
                    </x-atrium::alert>

                    <form method="POST" action="{{ route('atrium.roster.organizations.destroy', $organization) }}" class="mt-4 flex flex-wrap items-center gap-4">
                        @csrf
                        @method('DELETE')
                        <x-atrium::form.checkbox name="confirm" value="1" id="confirm-delete-organization" :label="__('roster::roster.delete_organization_soft_confirm')" required />
                        <x-atrium::icon-button icon="trash" :label="__('roster::roster.delete_organization')" type="submit" variant="danger" data-testid="delete-organization" />
                    </form>
                </x-atrium::card>
            @endif
        @endif
    </div>
</x-atrium::layout>
