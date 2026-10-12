<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavGroup;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavItem;
use RefactorCircus\Atrium\Domains\Plugins\Support\Plugin;
use RefactorCircus\Atrium\Domains\Search\Data\SearchResult;
use RefactorCircus\Atrium\Domains\Search\Data\SearchSource;
use RefactorCircus\Atrium\Domains\Widgets\Data\WidgetDefinition;
use RefactorCircus\Atrium\Support\Icons;
use RefactorCircus\Roster\Atrium\Http\Controllers\ImpersonationUiController;
use RefactorCircus\Roster\Atrium\Http\Controllers\InvitationUiController;
use RefactorCircus\Roster\Atrium\Http\Controllers\OrganizationUiController;
use RefactorCircus\Roster\Atrium\Http\Controllers\PermissionUiController;
use RefactorCircus\Roster\Atrium\Http\Controllers\RedirectDomainUiController;
use RefactorCircus\Roster\Atrium\Http\Controllers\RoleUiController;
use RefactorCircus\Roster\Atrium\Http\Controllers\ScimUiController;
use RefactorCircus\Roster\Atrium\Http\Controllers\SsoUiController;
use RefactorCircus\Roster\Atrium\Http\Controllers\TeamUiController;
use RefactorCircus\Roster\Atrium\Http\Controllers\TransferUiController;
use RefactorCircus\Roster\Atrium\Http\Controllers\UserUiController;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;
use RefactorCircus\Roster\Support\Users;

/**
 * Registers Roster inside the Atrium dashboard.
 *
 * Access follows Atrium's own `viewAtrium` gate. Widgets declared here are
 * offered in Atrium's picker and never placed automatically.
 */
class RosterPlugin extends Plugin
{
    /**
     * Where the user-status widget's counts are cached for a minute; Roster
     * forgets them whenever one of its User actions finishes.
     */
    public const string STATUS_COUNTS_CACHE = 'roster:atrium:user-status-counts';

    public function key(): string
    {
        return 'roster';
    }

    public function label(): string
    {
        return __('roster::roster.label');
    }

    /**
     * Features from `roster.atrium.features` that switch Roster in Atrium on
     * and off as a whole. A feature class that is not installed, such as
     * RosterSupportFeature without refactor-circus/pennantplus, is skipped.
     *
     * @return array<int, string>
     */
    public function features(): array
    {
        return $this->featuresFromConfig('roster.atrium.features');
    }

    /**
     * The package's section in the sidebar rail: its icon and its place.
     */
    public function navigationGroups(): array
    {
        return [
            NavGroup::make(__('roster::roster.label'))->icon(Icons::svg('user-group'))->sort(10),
        ];
    }

    public function navigation(): array
    {
        return [
            NavItem::make(__('roster::roster.users'))
                ->icon(Icons::svg('users'))
                ->route('atrium.roster.users.index')
                ->group(__('roster::roster.label'))
                ->sort(10)
                ->authorize(fn (Request $request): bool => self::may($request, 'roster.users.view')),

            NavItem::make(__('roster::roster.organizations'))
                ->icon(Icons::svg('building-office'))
                ->route('atrium.roster.organizations.index')
                ->group(__('roster::roster.label'))
                ->sort(20)
                ->authorize(fn (Request $request): bool => $this->mayAnywhere($request, 'roster.organizations.view')),

            NavItem::make(__('roster::roster.roles'))
                ->icon(Icons::svg('shield-check'))
                ->route('atrium.roster.roles.index')
                ->group(__('roster::roster.label'))
                ->sort(30)
                ->authorize(fn (Request $request): bool => $this->mayAnywhere($request, 'roster.roles.view')),

            NavItem::make(__('roster::roster.permissions'))
                ->icon(Icons::svg('key'))
                ->route('atrium.roster.permissions.index')
                ->group(__('roster::roster.label'))
                ->sort(40)
                ->authorize(fn (Request $request): bool => self::may($request, 'roster.roles.view')),

            NavItem::make(__('roster::roster.impersonations'))
                ->icon(Icons::svg('eye'))
                ->route('atrium.roster.impersonations.index')
                ->group(__('roster::roster.label'))
                ->sort(45)
                ->authorize(fn (Request $request): bool => $this->mayAnywhere($request, 'roster.users.impersonate')),

            // Organization admins reach their organization's imports and
            // exports from its page.
            NavItem::make(__('roster::roster.transfers'))
                ->icon(Icons::svg('arrows-up-down'))
                ->route('atrium.roster.transfers.index')
                ->group(__('roster::roster.label'))
                ->sort(48)
                ->authorize(fn (Request $request): bool => collect(TransferType::cases())->contains(fn (TransferType $type): bool => $this->mayAnywhere($request, $type->permission()))),

            // Roster's own audit log, while Roster audits (Keen installed, roster.audit.enabled on).
            $this->historyNavItem('roster')->group(__('roster::roster.label'))->sort(90),
        ];
    }

    public function routes(): void
    {
        Route::name('roster.')->group(function (): void {
            Route::get('roster/users', [UserUiController::class, 'index'])->name('users.index');
            Route::get('roster/users/create', [UserUiController::class, 'create'])->name('users.create');
            Route::post('roster/users', [UserUiController::class, 'store'])->name('users.store');
            Route::get('roster/users/{user}', [UserUiController::class, 'show'])->name('users.show');
            Route::patch('roster/users/{user}', [UserUiController::class, 'update'])->name('users.update');
            Route::delete('roster/users/{user}', [UserUiController::class, 'destroy'])->name('users.destroy');
            Route::post('roster/users/{user}/restore', [UserUiController::class, 'restore'])->name('users.restore');
            Route::delete('roster/users/{user}/purge', [UserUiController::class, 'purge'])->name('users.purge');
            Route::post('roster/users/{user}/redirect-domains', [RedirectDomainUiController::class, 'storeForUser'])->name('users.redirect-domains.store');
            Route::delete('roster/users/{user}/redirect-domains/{domain}', [RedirectDomainUiController::class, 'destroyForUser'])->name('users.redirect-domains.destroy');
            Route::patch('roster/users/{user}/profile', [UserUiController::class, 'profile'])->name('users.profile');
            Route::post('roster/users/{user}/suspend', [UserUiController::class, 'suspend'])->name('users.suspend');
            Route::post('roster/users/{user}/deactivate', [UserUiController::class, 'deactivate'])->name('users.deactivate');
            Route::post('roster/users/{user}/reactivate', [UserUiController::class, 'reactivate'])->name('users.reactivate');
            Route::post('roster/users/{user}/status', [UserUiController::class, 'status'])->name('users.status');
            Route::post('roster/users/{user}/approve', [UserUiController::class, 'approve'])->name('users.approve');
            Route::post('roster/users/{user}/reject', [UserUiController::class, 'reject'])->name('users.reject');
            Route::put('roster/users/{user}/context', [UserUiController::class, 'switchContext'])->name('users.context');
            Route::post('roster/users/{user}/domain-join', [UserUiController::class, 'domainJoin'])->name('users.domain-join');

            Route::get('roster/organizations', [OrganizationUiController::class, 'index'])->name('organizations.index');
            Route::get('roster/organizations/create', [OrganizationUiController::class, 'create'])->name('organizations.create');
            Route::post('roster/organizations', [OrganizationUiController::class, 'store'])->name('organizations.store');
            Route::get('roster/organizations/{organization}', [OrganizationUiController::class, 'show'])->name('organizations.show');
            Route::patch('roster/organizations/{organization}', [OrganizationUiController::class, 'update'])->name('organizations.update');
            Route::delete('roster/organizations/{organization}', [OrganizationUiController::class, 'destroy'])->name('organizations.destroy');
            Route::post('roster/organizations/{organization}/restore', [OrganizationUiController::class, 'restore'])->name('organizations.restore');
            Route::delete('roster/organizations/{organization}/purge', [OrganizationUiController::class, 'purge'])->name('organizations.purge');
            Route::post('roster/organizations/{organization}/transfer', [OrganizationUiController::class, 'transfer'])->name('organizations.transfer');
            Route::post('roster/organizations/{organization}/members', [OrganizationUiController::class, 'addMember'])->name('organizations.members.store');
            Route::post('roster/organizations/{organization}/links', [OrganizationUiController::class, 'link'])->name('organizations.links.store');
            Route::delete('roster/organizations/{organization}/links/{source}', [OrganizationUiController::class, 'unlink'])->name('organizations.links.destroy');
            Route::delete('roster/organizations/{organization}/members/{user}', [OrganizationUiController::class, 'removeMember'])->name('organizations.members.destroy');
            Route::post('roster/organizations/{organization}/redirect-domains', [RedirectDomainUiController::class, 'storeForOrganization'])->name('organizations.redirect-domains.store');
            Route::delete('roster/organizations/{organization}/redirect-domains/{domain}', [RedirectDomainUiController::class, 'destroyForOrganization'])->name('organizations.redirect-domains.destroy');

            Route::post('roster/organizations/{organization}/teams', [TeamUiController::class, 'store'])->name('teams.store');
            Route::get('roster/organizations/{organization}/teams/{team}', [TeamUiController::class, 'show'])->name('teams.show');
            Route::patch('roster/organizations/{organization}/teams/{team}', [TeamUiController::class, 'update'])->name('teams.update');
            Route::delete('roster/organizations/{organization}/teams/{team}', [TeamUiController::class, 'destroy'])->name('teams.destroy');
            Route::post('roster/organizations/{organization}/teams/{team}/members', [TeamUiController::class, 'addMember'])->name('teams.members.store');
            Route::delete('roster/organizations/{organization}/teams/{team}/members/{user}', [TeamUiController::class, 'removeMember'])->name('teams.members.destroy');

            Route::post('roster/organizations/{organization}/invitations', [InvitationUiController::class, 'store'])->name('invitations.store');
            Route::delete('roster/organizations/{organization}/invitations/{invitation}', [InvitationUiController::class, 'revoke'])->name('invitations.revoke');

            Route::get('roster/roles', [RoleUiController::class, 'index'])->name('roles.index');
            Route::post('roster/roles', [RoleUiController::class, 'store'])->name('roles.store');
            Route::get('roster/roles/{role}', [RoleUiController::class, 'show'])->name('roles.show');
            Route::patch('roster/roles/{role}', [RoleUiController::class, 'update'])->name('roles.update');
            Route::delete('roster/roles/{role}', [RoleUiController::class, 'destroy'])->name('roles.destroy');
            Route::post('roster/users/{user}/roles', [RoleUiController::class, 'assign'])->name('users.roles.store');
            Route::delete('roster/users/{user}/roles/{assignment}', [RoleUiController::class, 'revoke'])->name('users.roles.destroy');

            Route::get('roster/permissions', [PermissionUiController::class, 'index'])->name('permissions.index');
            Route::post('roster/permissions', [PermissionUiController::class, 'store'])->name('permissions.store');
            Route::patch('roster/permissions/{permission}', [PermissionUiController::class, 'update'])->name('permissions.update');
            Route::delete('roster/permissions/{permission}', [PermissionUiController::class, 'destroy'])->name('permissions.destroy');

            Route::post('roster/organizations/{organization}/sso', [SsoUiController::class, 'store'])->name('sso.store');
            Route::get('roster/sso/{connection}', [SsoUiController::class, 'show'])->name('sso.show');
            Route::patch('roster/sso/{connection}', [SsoUiController::class, 'update'])->name('sso.update');
            Route::delete('roster/sso/{connection}', [SsoUiController::class, 'destroy'])->name('sso.destroy');
            Route::delete('roster/sso-identities/{identity}', [SsoUiController::class, 'unlink'])->name('sso-identities.destroy');

            Route::post('roster/organizations/{organization}/scim-tokens', [ScimUiController::class, 'store'])->name('scim-tokens.store');
            Route::delete('roster/scim-tokens/{token}', [ScimUiController::class, 'revoke'])->name('scim-tokens.revoke');

            Route::post('roster/users/{user}/impersonate', [ImpersonationUiController::class, 'start'])->name('users.impersonate');
            Route::get('roster/impersonations', [ImpersonationUiController::class, 'index'])->name('impersonations.index');
            Route::delete('roster/impersonations/{impersonation}', [ImpersonationUiController::class, 'stop'])->name('impersonations.stop');

            Route::get('roster/transfers', [TransferUiController::class, 'index'])->name('transfers.index');
            Route::post('roster/imports', [TransferUiController::class, 'import'])->name('transfers.import');
            Route::get('roster/imports/templates/{type?}', [TransferUiController::class, 'template'])->name('transfers.template');
            Route::post('roster/exports', [TransferUiController::class, 'export'])->name('transfers.export');
            Route::get('roster/transfers/{transfer}', [TransferUiController::class, 'show'])->name('transfers.show');
            Route::post('roster/transfers/{transfer}/confirm', [TransferUiController::class, 'confirm'])->name('transfers.confirm');
            Route::delete('roster/transfers/{transfer}', [TransferUiController::class, 'cancel'])->name('transfers.cancel');
            Route::get('roster/transfers/{transfer}/download', [TransferUiController::class, 'download'])->name('transfers.download');
        });
    }

    public function widgets(): array
    {
        return [
            WidgetDefinition::make('roster.user-status')
                ->label(__('roster::roster.widget_user_status'))
                ->description(__('roster::roster.widget_user_status_description'))
                ->defaultSize(6, 2)
                ->view('roster::ui.widgets.user-status')
                ->authorize(fn (Request $request): bool => self::may($request, 'roster.users.view'))
                ->resolve(fn (): array => ['counts' => $this->statusCounts()]),

            WidgetDefinition::make('roster.organizations')
                ->label(__('roster::roster.widget_organizations'))
                ->description(__('roster::roster.widget_organizations_description'))
                ->defaultSize(6, 1)
                ->view('roster::ui.widgets.organizations')
                ->authorize(fn (Request $request): bool => self::may($request, 'roster.organizations.view'))
                // One query for all three counts.
                ->resolve(fn (): array => array_map('intval', (array) DB::query()
                    ->selectSub(OrganizationModel::query()->toBase()->selectRaw('count(*)'), 'organizations')
                    ->selectSub(TeamModel::query()->toBase()->selectRaw('count(*)'), 'teams')
                    ->selectSub(InvitationModel::query()->pending()->toBase()->selectRaw('count(*)'), 'pending')
                    ->first())),
        ];
    }

    /**
     * Users and organizations as two sources: each fills its own
     * `atrium.search.results.per_source`, and classification can pick one.
     *
     * The closures are static and resolve what they need when they run,
     * because Atrium may serialize them into a child process.
     *
     * Both match from the start of each field (`ada` finds Ada Lovelace and
     * ada@example.com), which the columns' indexes can serve on every
     * keystroke; the Users and Organizations screens' own filters still
     * match anywhere in a field.
     *
     * @return array<int, SearchSource>
     */
    public function search(): array
    {
        return [
            SearchSource::make('roster-users')
                ->label(__('roster::roster.users'))
                ->description('People: user accounts, by name or email address.')
                ->authorize(static fn (Request $request): bool => self::may($request, 'roster.users.view'))
                ->using(static function (string $query): array {
                    $users = app(Users::class);
                    $columns = array_filter([$users->column('name'), $users->column('email')]);

                    return $users->query()
                        ->where(function (Builder $builder) use ($columns, $query): void {
                            foreach ($columns as $column) {
                                $builder->orWhere($column, 'like', $query.'%');
                            }
                        })
                        ->limit(self::searchLimit())
                        ->get()
                        ->map(fn (Model $user): SearchResult => SearchResult::make(
                            $users->name($user) ?? $users->email($user) ?? (string) $user->getRouteKey(),
                            route('atrium.roster.users.show', $user->getRouteKey()),
                        )->subtitle((string) $users->email($user))->group(__('roster::roster.users')))
                        ->all();
                }),

            SearchSource::make('roster-organizations')
                ->label(__('roster::roster.organizations'))
                ->description('Organizations (tenants, customers, accounts), by name or slug.')
                ->authorize(static fn (Request $request): bool => self::may($request, 'roster.organizations.view') || self::organizationsFor($request->user()) !== [])
                ->using(static fn (string $query): array => OrganizationModel::query()
                    // Only the organizations the searcher may view, when not all of them.
                    ->when(self::organizationsFor(auth()->user()), fn (Builder $builder, array $within): Builder => $builder->whereIn('id', $within))
                    ->where(fn (Builder $builder): Builder => $builder
                        ->where('name', 'like', $query.'%')
                        ->orWhere('slug', 'like', $query.'%'))
                    ->orderBy('name')
                    ->limit(self::searchLimit())
                    ->get()
                    ->map(fn (OrganizationModel $organization): SearchResult => SearchResult::make(
                        $organization->name,
                        route('atrium.roster.organizations.show', $organization),
                    )->subtitle($organization->slug)->group(__('roster::roster.organizations')))
                    ->all()),
        ];
    }

    /**
     * As many results as Atrium keeps per source.
     */
    private static function searchLimit(): int
    {
        $limit = config('atrium.search.results.per_source');

        return is_int($limit) && $limit > 0 ? $limit : 5;
    }

    /**
     * Whether the request's user holds a global Roster permission. Atrium
     * asks with the request it is serving, so this reads the user from it
     * rather than from the current request as ScreenAccess does.
     */
    private static function may(Request $request, string $permission): bool
    {
        $user = $request->user();

        return app(Authorizer::class)->check($user instanceof Model ? $user : null, $permission);
    }

    /**
     * Whether the signed-in user holds a Roster permission globally or in any
     * organization - enough for a list page limited to those organizations.
     */
    private function mayAnywhere(Request $request, string $permission): bool
    {
        $user = $request->user();

        return self::may($request, $permission)
            || app(Authorizer::class)->organizationsWith($user instanceof Model ? $user : null, $permission) !== [];
    }

    /**
     * The organizations a user may view, or null when not limited to any.
     *
     * @return array<int, int|string>|null
     */
    private static function organizationsFor(mixed $user): ?array
    {
        return app(Authorizer::class)->organizationsWith($user instanceof Model ? $user : null, 'roster.organizations.view');
    }

    /**
     * Users per status. Users without a profile row count as active. Counted
     * at most once a minute: counting every user on every dashboard render
     * gets slow on large tables.
     *
     * @return array<string, int>
     */
    private function statusCounts(): array
    {
        return Cache::remember(self::STATUS_COUNTS_CACHE, 60, fn (): array => $this->countStatuses());
    }

    /**
     * @return array<string, int>
     */
    private function countStatuses(): array
    {
        $total = app(Users::class)->query()->count();

        $counts = ProfileModel::query()
            ->where('status', '!=', UserStatus::Active)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (mixed $count): int => is_numeric($count) ? (int) $count : 0)
            ->all();

        $others = array_sum($counts);

        $result = [UserStatus::Active->value => max(0, $total - $others)];

        foreach (UserStatus::cases() as $status) {
            if ($status !== UserStatus::Active) {
                $result[$status->value] = $counts[$status->value] ?? 0;
            }
        }

        return $result;
    }
}
