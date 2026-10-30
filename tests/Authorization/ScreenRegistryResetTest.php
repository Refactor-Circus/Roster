<?php

declare(strict_types=1);

use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Atrium\Facades\Atrium;

beforeEach(function (): void {
    Atrium::screens()
        ->tab('roster.organization', TabDefinition::make('test.reset')->view('roster::ui.partials.deleted')->permission('roster.members.view'))
        ->section('roster.users', 'row.actions', SectionDefinition::make('test.reset')->view('roster::ui.partials.deleted')->permission('roster.users.update'));
});

it('forgets what a test registered once its reset hook runs', function (): void {
    expect(Atrium::screens()->tabs('roster.organization'))->toHaveCount(1)
        ->and(Atrium::screens()->sections('roster.users', 'row.actions'))->toHaveCount(1);

    // What runs after each Authorization test, called here so this test needs no other.
    $this->tearDownResetsScreenExtensions();

    expect(Atrium::screens()->tabs('roster.organization'))->toBe([])
        ->and(Atrium::screens()->sections('roster.users', 'row.actions'))->toBe([]);
});
