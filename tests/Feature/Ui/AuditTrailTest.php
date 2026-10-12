<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use RefactorCircus\Keystone\Audit\Contracts\AuditTrail;
use RefactorCircus\Keystone\Audit\Data\AuditEntry;
use RefactorCircus\Keystone\Audit\Data\AuditFilter;
use RefactorCircus\Keystone\Audit\Data\AuditPage;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;

/**
 * An installed audit log holding one entry, whatever is asked.
 */
function fakeAuditTrail(): void
{
    app()->instance(AuditTrail::class, new class implements AuditTrail
    {
        public function available(): bool
        {
            return true;
        }

        public function entries(AuditFilter $filter): AuditPage
        {
            return new AuditPage([new AuditEntry(1, 'roster', 'record.changed', 'atrium', CarbonImmutable::now())]);
        }
    });
}

it('shows each record\'s history on its screen once an audit log is installed', function (): void {
    fakeAuditTrail();
    $this->actingAs($ada = user(['name' => 'Ada']));
    $acme = organization($ada);
    $team = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));
    $role = roleWith(['roster.users.view']);

    foreach ([
        route('atrium.roster.users.show', $ada->getRouteKey()),
        route('atrium.roster.organizations.show', [$acme, 'tab' => 'activity']),
        route('atrium.roster.teams.show', [$acme, $team->slug]),
        route('atrium.roster.roles.show', $role->id),
    ] as $url) {
        $this->get($url)->assertOk()->assertSee('data-testid="audit-trail"', false)->assertSee('record.changed');
    }
});

it('shows no history, and no activity tab, without an audit log', function (): void {
    $this->actingAs($ada = user(['name' => 'Ada']));
    $acme = organization($ada);

    $this->get(route('atrium.roster.users.show', $ada->getRouteKey()))->assertOk()->assertDontSee('data-testid="audit-trail"', false);
    $this->get(route('atrium.roster.organizations.show', $acme))->assertOk()->assertDontSee('tab=activity', false);
});

it('shows everything scoped to an organization on its activity tab', function (): void {
    $filters = new ArrayObject;

    app()->instance(AuditTrail::class, new class($filters) implements AuditTrail
    {
        public function __construct(private ArrayObject $filters) {}

        public function available(): bool
        {
            return true;
        }

        public function entries(AuditFilter $filter): AuditPage
        {
            $this->filters[] = $filter;

            return new AuditPage;
        }
    });

    $this->actingAs($ada = user(['name' => 'Ada']));
    $acme = organization($ada);

    $this->get(route('atrium.roster.organizations.show', [$acme, 'tab' => 'activity']))->assertOk();

    expect($filters[0]->scopeId)->toBe((string) $acme->getKey())
        ->and($filters[0]->subjectId)->toBeNull();
});
