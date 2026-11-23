<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Data\ListRoleAssignmentsData;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use Workbench\App\Models\User;

/**
 * Acme with an organization assignment and two team assignments on `ops`, plus
 * a global assignment and a Globex assignment that must stay out of Acme's list.
 *
 * @return array{acme: OrganizationModel, globex: OrganizationModel, ada: User, grace: User}
 */
function organizationAssignmentsWorld(): array
{
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);

    $ada = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));
    grant($ada, 'admin', $acme);

    $grace = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $grace->getRouteKey()));
    $ops = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));
    app(AddTeamMemberAction::class)->execute($ops, new AddTeamMemberData(user: $grace->getRouteKey()));
    grant($grace, 'lead', team: $ops);
    grant($ada, 'lead', team: $ops);

    // Outside Acme: a global role, and a role in another organization.
    grant($grace, 'super-admin');
    app(AddMemberAction::class)->execute($globex, new AddMemberData(user: $ada->getRouteKey()));
    grant($ada, 'admin', $globex);

    return ['acme' => $acme, 'globex' => $globex, 'ada' => $ada, 'grace' => $grace];
}

/**
 * The ids of every assignment stored in an organization, oldest first.
 *
 * @return array<int, mixed>
 */
function assignmentIdsIn(OrganizationModel $organization): array
{
    return RoleAssignmentModel::query()
        ->where('organization_id', $organization->getKey())
        ->oldest()
        ->oldest('id')
        ->pluck('id')
        ->all();
}

it('lists every role assignment in the organization and its teams', function (): void {
    ['acme' => $acme, 'globex' => $globex] = organizationAssignmentsWorld();

    $response = $this->getJson(route('roster.organizations.role-assignments.index', 'acme'))->assertOk();

    $items = collect($response->json('data'));

    expect($items->pluck('id')->all())->toBe(assignmentIdsIn($acme))
        ->and($items->pluck('organization')->unique()->values()->all())->toBe(['acme'])
        ->and($items->where('team', 'ops')->pluck('role.slug')->unique()->values()->all())->toBe(['lead'])
        ->and($items->where('team', 'ops')->count())->toBe(2)
        ->and($items->where('team', null)->pluck('role.slug')->all())->toContain('admin')
        ->and($items->pluck('id')->intersect(assignmentIdsIn($globex))->all())->toBe([])
        ->and($items->pluck('role.slug')->all())->not->toContain('super-admin');

    $response->assertJsonPath('meta.total', count(assignmentIdsIn($acme)));
});

it('pages like ListRoleAssignmentsData', function (): void {
    ['acme' => $acme] = organizationAssignmentsWorld();
    $ids = assignmentIdsIn($acme);

    expect(count($ids))->toBeGreaterThan(2);

    $this->getJson(route('roster.organizations.role-assignments.index', 'acme'))
        ->assertOk()
        ->assertJsonPath('meta.per_page', ListRoleAssignmentsData::pagination()->default);

    $first = $this->getJson(route('roster.organizations.role-assignments.index', ['acme', 'per_page' => 2]))
        ->assertOk()
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.total', count($ids))
        ->assertJsonPath('meta.last_page', (int) ceil(count($ids) / 2));

    $second = $this->getJson(route('roster.organizations.role-assignments.index', ['acme', 'per_page' => 2, 'page' => 2]))
        ->assertOk()
        ->assertJsonPath('meta.current_page', 2);

    expect([...collect($first->json('data'))->pluck('id'), ...collect($second->json('data'))->pluck('id')])
        ->toBe(array_slice($ids, 0, 4));

    $this->getJson(route('roster.organizations.role-assignments.index', ['acme', 'per_page' => ListRoleAssignmentsData::pagination()->max + 1]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('per_page');
});

it('answers each assignment exactly as the user roles endpoint does', function (): void {
    ['ada' => $ada, 'grace' => $grace] = organizationAssignmentsWorld();

    $items = collect($this->getJson(route('roster.organizations.role-assignments.index', 'acme'))->assertOk()->json('data'))->keyBy('id');

    $perUser = collect([$ada, $grace])
        ->flatMap(fn (User $user): array => $this->getJson(route('roster.users.roles.index', $user->getRouteKey()))->assertOk()->json('data'))
        ->keyBy('id');

    expect($items->count())->toBeGreaterThan(0);

    foreach ($items as $id => $item) {
        if ($perUser->has($id)) {
            expect($item)->toBe($perUser->get($id));
        }
    }

    // Ada's and Grace's three Acme assignments are compared, not just skipped.
    expect($items->keys()->intersect($perUser->keys())->count())->toBeGreaterThanOrEqual(3);
});

it('answers 404 for an unknown organization', function (): void {
    $this->getJson(route('roster.organizations.role-assignments.index', 'nowhere'))->assertNotFound();
});

it('answers 404 for a deleted organization', function (): void {
    ['globex' => $globex] = organizationAssignmentsWorld();
    $globex->delete();

    $this->getJson(route('roster.organizations.role-assignments.index', 'globex'))->assertNotFound();
});
