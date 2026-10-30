<?php

declare(strict_types=1);

use RefactorCircus\Roster\Facades\Roster;

/*
 * The organization's activity tab came from the audit log; Keen now adds the
 * history to screens itself, so Roster offers no activity tab even while it
 * audits.
 */

it('offers no activity tab while Roster audits', function (): void {
    $admin = user(['name' => 'Admin']);
    grant($admin, 'super-admin');
    $acme = organization($admin, ['name' => 'Acme']);

    expect(Roster::auditing())->toBeTrue();

    $this->actingAs($admin)
        ->get(route('atrium.roster.organizations.show', $acme))
        ->assertOk()
        ->assertSee('data-testid="tab-members"', false)
        ->assertDontSee('data-testid="tab-activity"', false)
        ->assertDontSee('tab=activity', false);
});

it('opens the default tab for ?tab=activity', function (): void {
    $admin = user(['name' => 'Admin']);
    grant($admin, 'super-admin');
    $acme = organization($admin, ['name' => 'Acme']);

    $this->actingAs($admin)
        ->get(route('atrium.roster.organizations.show', [$acme, 'tab' => 'activity']))
        ->assertOk()
        // The members tab is the default for a viewer who may open every tab.
        ->assertSee('data-testid="add-member"', false)
        ->assertDontSee('data-testid="tab-activity"', false)
        ->assertDontSee('data-testid="audit-trail"', false);
});
