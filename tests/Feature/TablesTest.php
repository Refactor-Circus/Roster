<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Tables;

it('ships the default table names in its config', function (): void {
    $config = require dirname(__DIR__, 2).'/config/roster.php';

    expect($config['tables'])->toBe(Tables::DEFAULTS);
});

it('keeps the default for tables a published config leaves out', function (): void {
    config()->set('roster.tables', ['organizations' => 'crm_organizations']);

    expect(Tables::name('organizations'))->toBe('crm_organizations')
        ->and(Tables::name('teams'))->toBe('roster_teams')
        ->and((new OrganizationModel)->getTable())->toBe('crm_organizations');
});
