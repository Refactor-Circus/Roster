<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Roster\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Roster\Atrium\ScreenAccess;
use RefactorCircus\Roster\Domains\Invitation\Actions\ListInvitationsAction;
use RefactorCircus\Roster\Domains\Invitation\Data\ListInvitationsData;
use RefactorCircus\Roster\Domains\Invitation\Enums\InvitationStatus;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\DeleteOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\LinkOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\ListMembersAction;
use RefactorCircus\Roster\Domains\Organization\Actions\ListOrganizationsAction;
use RefactorCircus\Roster\Domains\Organization\Actions\PurgeOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\RemoveMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\RestoreOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\ShowOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\TransferOwnershipAction;
use RefactorCircus\Roster\Domains\Organization\Actions\UnlinkOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\UpdateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Data\LinkOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Data\ListMembersData;
use RefactorCircus\Roster\Domains\Organization\Data\ListOrganizationsData;
use RefactorCircus\Roster\Domains\Organization\Data\TransferOwnershipData;
use RefactorCircus\Roster\Domains\Organization\Data\UpdateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Actions\ListRoleAssignmentsAction;
use RefactorCircus\Roster\Domains\Role\Actions\ListRolesAction;
use RefactorCircus\Roster\Domains\Role\Data\ListRoleAssignmentsData;
use RefactorCircus\Roster\Domains\Role\Data\ListRolesData;
use RefactorCircus\Roster\Domains\Team\Actions\ListTeamsAction;
use RefactorCircus\Roster\Domains\Team\Data\ListTeamsData;
use RefactorCircus\Roster\Support\Users;

/**
 * The Atrium screens for organizations and their members. Each screen
 * builds its Action's input Data, which validates itself, and calls that
 * Action.
 */
final class OrganizationUiController
{
    use AuthorizesScreens;

    /**
     * Each built-in tab, in order, and the permission, in the organization,
     * that opens it. Other packages' tabs (Keen's history, Cortex's MCP
     * domains) are theirs to register on Atrium's registry.
     */
    private const array TAB_PERMISSIONS = [
        'members' => 'roster.members.view',
        'teams' => 'roster.teams.view',
        'invitations' => 'roster.invitations.view',
        'roles' => 'roster.roles.view',
        'settings' => 'roster.organizations.view',
    ];

    /**
     * Each built-in tab's Atrium icon.
     */
    private const array TAB_ICONS = [
        'members' => 'users',
        'teams' => 'user-group',
        'invitations' => 'envelope',
        'roles' => 'shield-check',
        'settings' => 'cog',
    ];

    public function __construct(private readonly Users $users) {}

    public function index(Request $request): View
    {
        $within = $this->authorizeList('roster.organizations.view');

        $resolved = Atrium::screens()->resolve($this->screen('roster.organizations', Screen::LIST)->tabs($this->overviewTab()), $request);
        $filters = ListOrganizationsData::from($request->query());

        /** @var view-string $view */
        $view = 'roster::ui.organizations.index';

        return view($view, [
            'resolved' => $resolved,
            'filters' => $filters,
            // The list only while it is the open tab.
            ...match ($resolved->openTab) {
                Screen::OVERVIEW => ['organizations' => app(ListOrganizationsAction::class)->execute($filters, $within)->withQueryString()],
                default => [],
            },
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeScreen('roster.organizations.create');

        /** @var view-string $view */
        $view = 'roster::ui.organizations.create';

        return view($view, [
            'resolved' => Atrium::screens()->resolve($this->screen('roster.organization-create', Screen::DETAIL)->tabs($this->overviewTab()), $request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('roster.organizations.create');

        $this->splitDomains($request);

        $organization = app(CreateOrganizationAction::class)->execute(CreateOrganizationData::from($request->all()));

        return redirect()
            ->route('atrium.roster.organizations.show', $organization)
            ->with('status', __('roster::roster.organization_created'));
    }

    public function show(Request $request, string $organization): View
    {
        $model = OrganizationModel::withTrashed()->where('slug', $organization)->firstOrFail();
        $this->authorizeScreen('roster.organizations.view', $model);

        if ($model->trashed()) {
            /** @var view-string $deleted */
            $deleted = 'roster::ui.organizations.deleted';

            return view($deleted, ['organization' => $model]);
        }

        $model = app(ShowOrganizationAction::class)->execute($model);

        // A built-in tab the viewer may not open is refused before any
        // host closure runs; ?tab=activity, ?tab=mcp and unknown keys are
        // no built-in, so they open the default tab.
        $requested = $request->query('tab');

        if (is_string($requested) && array_key_exists($requested, self::TAB_PERMISSIONS)) {
            $this->authorizeScreen(self::TAB_PERMISSIONS[$requested], $model);
        }

        // Only the built-in tabs the viewer may open, in order; the first is
        // the default. Registered tabs follow them and are never the default.
        // Every built-in key is reserved for every viewer, so a registered
        // tab named like one the viewer may not open is skipped too (D-031).
        $open = array_keys(array_filter(self::TAB_PERMISSIONS, fn (string $permission): bool => ScreenAccess::allows($permission, $model)));

        $screen = $this->screen('roster.organization', Screen::DETAIL, $model)
            ->tabs(array_map(fn (string $key): array => ['key' => $key, 'label' => __('roster::roster.'.$key), 'icon' => self::TAB_ICONS[$key]], $open))
            ->defaultTab($open[0] ?? 'settings')
            ->reserve(...array_keys(self::TAB_PERMISSIONS));

        $resolved = Atrium::screens()->resolve($screen, $request);

        /** @var view-string $view */
        $view = 'roster::ui.organizations.show';

        // Only the open built-in tab's data, each list paginated on its own;
        // none while a registered tab is open.
        $page = $request->integer('page', 1);

        return view($view, [
            'resolved' => $resolved,
            'organization' => $model,
            'pending' => InvitationStatus::Pending,
            'directory' => $this->users,
            ...match ($resolved->openTab) {
                'members' => ['members' => app(ListMembersAction::class)->execute($model, new ListMembersData(perPage: 25, page: $page))->withQueryString()],
                'teams' => ['teams' => app(ListTeamsAction::class)->execute($model, new ListTeamsData(perPage: 25, page: $page))->withQueryString()],
                'invitations' => [
                    'invitations' => app(ListInvitationsAction::class)->execute($model, new ListInvitationsData(perPage: 25, page: $page))->withQueryString(),
                    // Every team, as the invitation form's options.
                    'teams' => $model->teams()->orderBy('name')->get(['id', 'organization_id', 'name', 'slug']),
                ],
                'roles' => [
                    'roles' => app(ListRolesAction::class)->execute(new ListRolesData(organization: $model, perPage: 100)),
                    'assignments' => app(ListRoleAssignmentsAction::class)->execute(new ListRoleAssignmentsData(organization: $model, perPage: 25, page: $page))->withQueryString(),
                ],
                default => [],
            },
        ]);
    }

    public function update(Request $request, string $organization): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen('roster.organizations.update', $model);

        $request->merge(['auto_join' => $request->boolean('auto_join')]);
        $this->splitDomains($request);

        $model = app(UpdateOrganizationAction::class)->execute($model, UpdateOrganizationData::from($request->all(), $model));

        return $this->backTo($model, 'settings', 'roster::roster.organization_updated');
    }

    public function destroy(Request $request, string $organization): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen('roster.organizations.delete', $model);
        // The page asks for a ticked "I understand" before deleting.
        $request->validate(['confirm' => ['accepted']]);

        app(DeleteOrganizationAction::class)->execute($model);

        return redirect()
            ->route('atrium.roster.organizations.index', ['trashed' => 'only'])
            ->with('status', __('roster::roster.organization_deleted'));
    }

    public function transfer(Request $request, string $organization): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen('roster.organizations.transfer', $model);

        $model = app(TransferOwnershipAction::class)->execute($model, TransferOwnershipData::from($request->all()));

        return $this->backTo($model, 'members', 'roster::roster.ownership_transferred');
    }

    public function addMember(Request $request, string $organization): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen('roster.members.manage', $model);

        app(AddMemberAction::class)->execute($model, AddMemberData::from($request->all()));

        return $this->backTo($model, 'members', 'roster::roster.member_added');
    }

    public function removeMember(string $organization, string $user): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen('roster.members.manage', $model);

        app(RemoveMemberAction::class)->execute($model, $this->users->findOrFail($user));

        return $this->backTo($model, 'members', 'roster::roster.member_removed');
    }

    public function link(Request $request, string $organization): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen('roster.organizations.update', $model);

        app(LinkOrganizationAction::class)->execute($model, LinkOrganizationData::from($request->all()));

        return $this->backTo($model, 'settings', 'roster::roster.organization_linked');
    }

    public function unlink(string $organization, string $source): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen('roster.organizations.update', $model);

        app(UnlinkOrganizationAction::class)->execute($model, $source);

        return $this->backTo($model, 'settings', 'roster::roster.organization_unlinked');
    }

    public function restore(string $organization): RedirectResponse
    {
        $model = OrganizationModel::withTrashed()->where('slug', $organization)->firstOrFail();
        $this->authorizeScreen('roster.organizations.delete', $model);

        app(RestoreOrganizationAction::class)->execute($model);

        return $this->backTo($model, 'members', 'roster::roster.organization_restored');
    }

    public function purge(Request $request, string $organization): RedirectResponse
    {
        $model = OrganizationModel::withTrashed()->where('slug', $organization)->firstOrFail();
        $this->authorizeScreen('roster.organizations.purge', $model);
        $request->validate(['confirm' => ['accepted']]);

        app(PurgeOrganizationAction::class)->queue($model);

        return redirect()
            ->route('atrium.roster.organizations.index', ['trashed' => 'only'])
            ->with('status', __('roster::roster.organization_purge_queued'));
    }

    private function backTo(OrganizationModel $organization, string $tab, string $message): RedirectResponse
    {
        return redirect()
            ->route('atrium.roster.organizations.show', [$organization, 'tab' => $tab])
            ->with('status', __($message));
    }

    /**
     * The form sends domains as one line each.
     */
    private function splitDomains(Request $request): void
    {
        $domains = $request->input('domains');

        if (is_string($domains)) {
            $request->merge(['domains' => array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $domains) ?: [])))]);
        } elseif ($domains === null && $request->has('domains')) {
            $request->merge(['domains' => []]);
        }
    }
}
