<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RefactorCircus\Roster\Domains\Organization\Actions\DeleteOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\SearchOrganizationsAction;
use RefactorCircus\Roster\Domains\Organization\Data\SearchOrganizationsData;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationsSearchedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationsSearchingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationStatsShowingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationStatsShownActionEvent;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Actions\DeleteTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;

// GET stats/organizations

it('counts live organizations, teams and pending invitations', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);
    app(DeleteOrganizationAction::class)->execute(organization(attributes: ['name' => 'Initech']));

    app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));
    app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Sales'));
    $gone = app(CreateTeamAction::class)->execute($globex, new CreateTeamData(name: 'Legacy'));
    app(CreateTeamAction::class)->execute($globex, new CreateTeamData(name: 'Support'));
    app(DeleteTeamAction::class)->execute($gone);

    invitationToken($acme);
    invitationToken($globex);
    invitationToken($acme, attributes: ['expires_at' => now()->subDay()]);
    invitationToken($acme, attributes: ['revoked_at' => now()]);
    invitationToken($acme, attributes: ['accepted_at' => now()]);
    invitationToken($globex, attributes: ['declined_at' => now()]);

    $this->getJson(route('roster.stats.organizations'))->assertOk()->assertExactJson([
        'data' => [
            'organizations' => 2,
            'teams' => 3,
            'pending_invitations' => 2,
        ],
    ]);
});

it('answers zeroes when there is nothing to count', function (): void {
    $this->getJson(route('roster.stats.organizations'))->assertOk()->assertExactJson([
        'data' => ['organizations' => 0, 'teams' => 0, 'pending_invitations' => 0],
    ]);
});

it('counts in a single query', function (): void {
    organization();
    DB::enableQueryLog();

    $this->getJson(route('roster.stats.organizations'))->assertOk();

    $counts = collect(DB::getQueryLog())->pluck('query')->filter(fn (string $sql): bool => str_contains(strtolower($sql), 'count(*)'));

    expect($counts)->toHaveCount(1);
});

it('announces showing the stats with a starting and a finished event', function (): void {
    Event::fake([OrganizationStatsShowingActionEvent::class, OrganizationStatsShownActionEvent::class]);
    organization();

    $this->getJson(route('roster.stats.organizations'))->assertOk();

    Event::assertDispatched(OrganizationStatsShowingActionEvent::class);
    Event::assertDispatched(OrganizationStatsShownActionEvent::class, fn (OrganizationStatsShownActionEvent $event): bool => $event->stats === ['organizations' => 1, 'teams' => 0, 'pending_invitations' => 0]);
});

// GET search/organizations

it('finds organizations whose name or slug starts with q, ordered by name', function (): void {
    organization(attributes: ['name' => 'Acme West']);
    organization(attributes: ['name' => 'Acme East']);
    organization(attributes: ['name' => 'Acorn']);
    organization(attributes: ['name' => 'Initech', 'slug' => 'acquired-initech']);
    // "ac" inside a field, never at its start.
    organization(attributes: ['name' => 'Big Acme', 'slug' => 'big-acme']);

    $names = collect($this->getJson(route('roster.search.organizations', ['q' => 'Ac']))->assertOk()->json('data'))->pluck('name')->all();

    expect($names)->toBe(['Acme East', 'Acme West', 'Acorn', 'Initech']);
});

it('answers organization resources', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $acme->links()->create(['source' => 'erp', 'external_id' => 'C-1']);

    $item = $this->getJson(route('roster.search.organizations', ['q' => 'acme']))->assertOk()->json('data.0');

    $shown = $this->getJson(route('roster.organizations.show', 'acme'))->assertOk()->json('data');

    expect($item)->toBe(collect($shown)->only(array_keys($item))->all())
        ->and(array_keys($item))->toContain('slug', 'name', 'owner', 'links', 'status')
        ->and($item['slug'])->toBe('acme')
        ->and($item['links'])->toBe([['source' => 'erp', 'external_id' => 'C-1', 'account_number' => null, 'synced_at' => null]]);
});

it('leaves deleted organizations out', function (): void {
    app(DeleteOrganizationAction::class)->execute(organization(attributes: ['name' => 'Acme']));

    $this->getJson(route('roster.search.organizations', ['q' => 'Acme']))->assertOk()->assertExactJson(['data' => []]);
});

it('answers five organizations unless asked for up to 25', function (): void {
    foreach (range(1, 7) as $i) {
        organization(attributes: ['name' => 'Sam '.$i]);
    }

    $this->getJson(route('roster.search.organizations', ['q' => 'Sam']))->assertJsonCount(5, 'data');
    $this->getJson(route('roster.search.organizations', ['q' => 'Sam', 'limit' => 2]))->assertJsonCount(2, 'data');
    $this->getJson(route('roster.search.organizations', ['q' => 'Sam', 'limit' => 25]))->assertJsonCount(7, 'data');
});

it('validates q and limit', function (array $query, string $field): void {
    $this->getJson(route('roster.search.organizations', $query))->assertUnprocessable()->assertJsonValidationErrors([$field]);
})->with([
    'q missing' => [[], 'q'],
    'q empty' => [['q' => ''], 'q'],
    'q over 100 characters' => [['q' => str_repeat('a', 101)], 'q'],
    'limit zero' => [['q' => 'a', 'limit' => 0], 'limit'],
    'limit over 25' => [['q' => 'a', 'limit' => 26], 'limit'],
    'limit not a number' => [['q' => 'a', 'limit' => 'many'], 'limit'],
]);

it('takes q of up to 100 characters', function (): void {
    $this->getJson(route('roster.search.organizations', ['q' => str_repeat('a', 100)]))->assertOk()->assertExactJson(['data' => []]);
});

it('matches %, _ and \\ in q literally', function (string $q, string $match, string $other): void {
    organization(attributes: ['name' => $match, 'slug' => 'first']);
    organization(attributes: ['name' => $other, 'slug' => 'second']);

    $this->getJson(route('roster.search.organizations', ['q' => $q]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', $match);
})->with([
    'percent' => ['100%', '100% Pure', '100 Proof'],
    'underscore' => ['a_', 'a_b', 'axb'],
    'backslash' => ['c\\', 'c\\d', 'cd'],
    'exclamation mark' => ['hey!', 'hey!you', 'heyyou'],
]);

it('matches an underscore in a slug literally', function (): void {
    organization(attributes: ['name' => 'One', 'slug' => 'ab_one']);
    organization(attributes: ['name' => 'Two', 'slug' => 'abxtwo']);

    expect(collect($this->getJson(route('roster.search.organizations', ['q' => 'ab_']))->assertOk()->json('data'))->pluck('slug')->all())->toBe(['ab_one']);
});

it('searches only within the given organizations', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    organization(attributes: ['name' => 'Acorn']);

    $found = app(SearchOrganizationsAction::class)->execute(new SearchOrganizationsData(q: 'Ac'), [$acme->getKey()]);

    expect($found->pluck('slug')->all())->toBe(['acme'])
        ->and(app(SearchOrganizationsAction::class)->execute(new SearchOrganizationsData(q: 'Ac'), [])->all())->toBe([])
        ->and(app(SearchOrganizationsAction::class)->execute(new SearchOrganizationsData(q: 'Ac'))->pluck('slug')->all())->toBe(['acme', 'acorn']);
});

it('announces a search with the input on its starting and finished events', function (): void {
    Event::fake([OrganizationsSearchingActionEvent::class, OrganizationsSearchedActionEvent::class]);

    $this->getJson(route('roster.search.organizations', ['q' => 'Acme', 'limit' => 3]))->assertOk();

    $carries = fn (SearchOrganizationsData $data): bool => $data->q === 'Acme' && $data->limit === 3;

    Event::assertDispatched(OrganizationsSearchingActionEvent::class, fn (OrganizationsSearchingActionEvent $event): bool => $carries($event->data));
    Event::assertDispatched(OrganizationsSearchedActionEvent::class, fn (OrganizationsSearchedActionEvent $event): bool => $carries($event->data));
});

it('builds search input with a default limit of five', function (): void {
    $data = SearchOrganizationsData::from(['q' => 'Acme']);

    expect($data->q)->toBe('Acme')
        ->and($data->limit)->toBe(5);
});
