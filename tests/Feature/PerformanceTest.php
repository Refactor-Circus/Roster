<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Atrium\RosterPlugin;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\RemoveMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\SwitchContextAction;
use RefactorCircus\Roster\Domains\Organization\Actions\SyncOrganizationsAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Data\SwitchContextData;
use RefactorCircus\Roster\Domains\Organization\Data\SyncOrganizationsData;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Actions\AssignRoleAction;
use RefactorCircus\Roster\Domains\Role\Data\AssignRoleData;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\Transfer\Actions\ConfirmImportAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartExportAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Roster\Domains\Transfer\Data\StartExportData;
use RefactorCircus\Roster\Domains\Transfer\Data\StartImportData;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Support\CaseInsensitive;
use RefactorCircus\Roster\Support\Users;
use Workbench\App\Models\User;

/*
 * Query budgets: the paths that run on every request, or once per row of a
 * list or a bulk job, must not grow with the number of rows. Each test
 * measures the same path at two sizes.
 */

function queries(Closure $callback): int
{
    // A fresh request's worth of per-request caches.
    app()->forgetScopedInstances();
    DB::flushQueryLog();
    DB::enableQueryLog();
    $callback();
    DB::disableQueryLog();

    return count(DB::getQueryLog());
}

/**
 * Acme (on acme.test) with team Ops and `$count` members seated on it, each
 * with Acme/Ops as their current context.
 *
 * @return array{0: User, 1: OrganizationModel}
 */
function crowd(int $count): array
{
    $owner = user(['email' => 'owner@acme.test']);
    $acme = organization($owner, ['name' => 'Acme', 'domains' => ['acme.test']]);
    $ops = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));

    foreach (range(1, $count) as $i) {
        $member = user(['email' => "m{$i}@acme.test"]);
        app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $member->getRouteKey()));
        app(AddTeamMemberAction::class)->execute($ops, new AddTeamMemberData(user: $member->getRouteKey()));
        app(SwitchContextAction::class)->execute($member, new SwitchContextData(organization: 'acme', team: 'ops'));
    }

    return [$owner, $acme];
}

it('resolves Gate checks without a scope once per request', function (): void {
    crowd(1);
    $member = User::query()->where('email', 'm1@acme.test')->firstOrFail();

    $checks = fn (int $times): int => queries(function () use ($member, $times): void {
        $fresh = $member->fresh();

        foreach (range(1, $times) as $ignored) {
            $fresh->can('roster.members.view');
        }
    });

    expect($checks(20))->toBe($checks(5))->toBeLessThanOrEqual(10);
});

it('answers every permission in every organization from one load per user', function (int $organizations): void {
    config()->set('roster.authorization', true);
    $admin = user();

    foreach (range(1, $organizations) as $i) {
        grant($admin, 'admin', organization(attributes: ['name' => 'Org '.$i]));
    }

    $scopes = OrganizationModel::query()->get()->all();

    $count = queries(function () use ($admin, $scopes): void {
        $authorizer = app(Authorizer::class);

        foreach (['roster.members.view', 'roster.teams.manage', 'roster.roles.view', 'roster.invitations.manage'] as $permission) {
            $authorizer->organizationsWith($admin, $permission);

            foreach ($scopes as $scope) {
                $authorizer->check($admin, $permission, $scope);
            }
        }
    });

    // Permission names, the user's assignments, super-admin and owned organizations.
    expect($count)->toBeLessThanOrEqual(4);
})->with([2, 10]);

it('lists users with context in a fixed number of queries', function (int $size): void {
    [$owner] = crowd($size);
    $this->actingAs($owner);

    expect(queries(fn () => $this->getJson(route('roster.users.index', ['per_page' => 100]))->assertOk()))->toBeLessThanOrEqual(8);
})->with([5, 30]);

it('renders each organization tab in a fixed number of queries', function (string $tab): void {
    [$owner] = crowd(30);
    $this->actingAs($owner);

    expect(queries(fn () => $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => $tab]))->assertOk()))->toBeLessThanOrEqual(16);
})->with(['members', 'teams', 'invitations', 'roles', 'activity', 'settings']);

it('lists transfers in a fixed number of queries', function (int $size): void {
    $owner = user();
    TransferModel::factory()->count($size)->create(['requested_by' => $owner->getKey(), 'output_path' => 'missing.csv']);
    $this->actingAs($owner);

    expect(queries(fn () => $this->getJson(route('roster.transfers.index'))->assertOk()))->toBeLessThanOrEqual(6);
})->with([2, 20]);

it('exports members in a fixed number of queries per chunk', function (int $size): void {
    [$owner] = crowd($size);

    expect(queries(fn () => app(StartExportAction::class)->execute(new StartExportData(type: TransferType::ExportMembers, organization: 'acme'), $owner)))->toBeLessThanOrEqual(55);
})->with([5, 30]);

it('previews imports with at most a couple of queries per row', function (): void {
    [$owner] = crowd(0);
    $csv = fn (int $rows): string => "email,name,teams\n".implode("\n", array_map(fn (int $i): string => "new{$i}@acme.test,New {$i},ops", range(1, $rows)));

    $ten = queries(fn () => app(StartImportAction::class)->execute(new StartImportData(type: TransferType::ImportMembers, organization: 'acme', content: $csv(10)), $owner));
    $forty = queries(fn () => app(StartImportAction::class)->execute(new StartImportData(type: TransferType::ImportMembers, organization: 'acme', content: $csv(40)), $owner));

    expect(($forty - $ten) / 30)->toBeLessThanOrEqual(2);
});

it('applies imports with a bounded number of queries per row', function (): void {
    [$owner] = crowd(0);
    $import = fn (int $rows, string $prefix): TransferModel => app(StartImportAction::class)->execute(new StartImportData(
        type: TransferType::ImportMembers,
        organization: 'acme',
        content: "email,name,teams\n".implode("\n", array_map(fn (int $i): string => "{$prefix}{$i}@acme.test,New {$i},ops", range(1, $rows))),
    ), $owner);

    $ten = $import(10, 'a');
    $forty = $import(40, 'b');

    $perRow = (queries(fn () => app(ConfirmImportAction::class)->execute($forty, $owner)) - queries(fn () => app(ConfirmImportAction::class)->execute($ten, $owner))) / 30;

    // Most of a row is its writes and their audit entries; reads are cached.
    expect($perRow)->toBeLessThanOrEqual(30);
});

it('re-syncs unchanged organizations cheaply', function (): void {
    $records = array_map(fn (int $i): array => ['source' => 'erp', 'external_id' => "C-{$i}", 'name' => "Org {$i}"], range(1, 20));
    app(SyncOrganizationsAction::class)->execute(new SyncOrganizationsData(records: $records));

    // The links are looked up together and stamped in one update.
    expect(queries(fn () => app(SyncOrganizationsAction::class)->execute(new SyncOrganizationsData(records: $records))))->toBeLessThanOrEqual(4);
});

it('finds a user by email with one indexed lookup, and by other casing after it', function (): void {
    $ada = user(['email' => 'ada@example.com']);
    $grace = user(['email' => 'Grace@Example.com']);
    $users = app(Users::class);

    expect(queries(fn () => expect($users->findByEmail('ADA@example.com')?->is($ada))->toBeTrue()))->toBe(1)
        ->and($users->findByEmail('grace@example.com')?->is($grace))->toBeTrue()
        ->and($users->findByEmail('a_a@example.com'))->toBeNull()
        ->and(array_keys($users->findManyByEmail(['ADA@example.com', 'grace@example.com', 'nobody@example.com'])))->toEqualCanonicalizing(['ada@example.com', 'grace@example.com'])
        ->and(queries(fn () => $users->findManyByEmail(array_map(fn (int $i): string => "u{$i}@example.com", range(1, 50)))))->toBe(2);
});

it('remembers permissions between requests when the permission cache is on', function (): void {
    config()->set('roster.permission_cache.ttl', 60);
    crowd(1);
    $member = User::query()->where('email', 'm1@acme.test')->firstOrFail();
    $check = fn (): int => queries(fn () => $member->fresh()->can('roster.members.view'));

    $cold = $check();
    $warm = $check();

    expect($warm)->toBeLessThan($cold);

    // A role change reaches the next request at once.
    $role = roleWith(['roster.users.view']);
    app(AssignRoleAction::class)->execute($member, new AssignRoleData(role: (string) $role->id));
    app()->forgetScopedInstances();

    expect($member->fresh()->can('roster.users.view'))->toBeTrue();
});

it('keeps the permission names when one user\'s access changes', function (): void {
    [$owner, $acme] = crowd(2);
    $member = User::query()->where('email', 'm1@acme.test')->firstOrFail();
    app()->forgetScopedInstances();
    $permissions = app(Permissions::class);
    $permissions->for($owner, $acme);

    app(RemoveMemberAction::class)->execute($acme, $member);

    // The owner's answers and the permission names survive; only the member's go.
    expect(queries(fn () => $permissions->for($owner, $acme)))->toBe(0);
});

it('answers a repeated authorization check from memory', function (): void {
    [$owner, $acme] = crowd(1);

    $count = fn (int $times): int => queries(function () use ($owner, $acme, $times): void {
        foreach (range(1, $times) as $ignored) {
            app(Authorizer::class)->check($owner, 'roster.teams.manage', $acme);
        }
    });

    expect($count(30))->toBe($count(1));
});

it('checks policies on records without loading each record\'s organization', function (int $size): void {
    [$owner, $acme] = crowd($size);
    $memberships = MembershipModel::query()->where('organization_id', $acme->getKey())->get();

    expect(queries(fn () => $memberships->each(fn (MembershipModel $membership): bool => $owner->can('view', $membership))))->toBeLessThanOrEqual(6);
})->with([3, 20]);

it('shows a team in a fixed number of queries, with at most its first members', function (int $size): void {
    [$owner] = crowd($size);
    $this->actingAs($owner);

    expect(queries(fn () => $this->getJson(route('roster.organizations.teams.show', ['acme', 'ops']))->assertOk()->assertJsonPath('data.members_count', $size)))
        ->toBeLessThanOrEqual(10);
})->with([3, 30]);

it('renders a team page in a fixed number of queries', function (int $size): void {
    [$owner] = crowd($size);
    $this->actingAs($owner);

    expect(queries(fn () => $this->get(route('atrium.roster.teams.show', ['acme', 'ops']))->assertOk()))->toBeLessThanOrEqual(18);
})->with([3, 30]);

it('previews member imports without a query per row', function (): void {
    [$owner] = crowd(20);
    $csv = fn (int $rows): string => "email,name,teams\n".implode("\n", array_map(fn (int $i): string => ($i <= 20 ? "m{$i}" : "new{$i}")."@acme.test,Person {$i},ops", range(1, $rows)));

    $ten = queries(fn () => app(StartImportAction::class)->execute(new StartImportData(type: TransferType::ImportMembers, organization: 'acme', content: $csv(10)), $owner));
    $forty = queries(fn () => app(StartImportAction::class)->execute(new StartImportData(type: TransferType::ImportMembers, organization: 'acme', content: $csv(40)), $owner));

    expect($forty - $ten)->toBeLessThanOrEqual(2);
});

it('previews user and organization imports with no lookups per row', function (TransferType $type, Closure $row, int $perRow): void {
    $owner = user(['email' => 'owner@acme.test']);
    $columns = implode(',', array_merge(...array_values($type->columns())));
    $csv = fn (int $rows): string => $columns."\n".implode("\n", array_map($row, range(1, $rows)));

    $ten = queries(fn () => app(StartImportAction::class)->execute(new StartImportData(type: $type, content: $csv(10)), $owner));
    $forty = queries(fn () => app(StartImportAction::class)->execute(new StartImportData(type: $type, content: $csv(40)), $owner));

    expect(intdiv($forty - $ten, 30))->toBeLessThanOrEqual($perRow);
})->with([
    'users' => [TransferType::ImportUsers, fn (int $i): string => "u{$i}@acme.test,User {$i},", 0],
    // The owner's key is still validated row by row, as any sync record's.
    'organizations' => [TransferType::ImportOrganizations, fn (int $i): string => "erp,C-{$i},Org {$i},,,,owner@acme.test", 1],
]);

it('caches the user-status widget counts until a user changes', function (): void {
    user();
    $widget = collect(app(RosterPlugin::class)->widgets())->firstOrFail(fn ($definition): bool => $definition->key === 'roster.user-status');

    $widget->resolveData();

    expect(queries(fn () => $widget->resolveData()))->toBe(0);

    app(CreateUserAction::class)->execute(new CreateUserData(name: 'Ada', email: 'ada@example.com'));

    expect($widget->resolveData()['counts']['active'])->toBe(2);
});

it('compares lower(column) on PostgreSQL, where an expression index can serve it', function (): void {
    config()->set('database.connections.roster_pgsql', ['driver' => 'pgsql', 'host' => '127.0.0.1', 'database' => 'roster', 'username' => 'roster', 'password' => '', 'prefix' => '']);

    $query = CaseInsensitive::where(User::on('roster_pgsql'), 'email', 'Ada@Example.com');

    expect($query->toSql())->toContain('lower("email") = ?')
        ->and($query->getBindings())->toBe(['ada@example.com']);
});
