<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Support\Tables;

it('migrates every table under its configured name', function (): void {
    foreach (array_keys(Tables::DEFAULTS) as $key) {
        if ($key === 'users') {
            continue;
        }

        expect(Schema::hasTable('crm_'.$key))->toBeTrue()
            ->and(Schema::hasTable('roster_'.$key))->toBeFalse();
    }
});

it('keeps organizations and teams working on renamed tables', function (): void {
    $acme = organization(attributes: ['name' => 'Acme', 'domains' => ['acme.test']]);
    $ada = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));
    $ops = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));
    app(AddTeamMemberAction::class)->execute($ops, new AddTeamMemberData(user: $ada->getRouteKey()));

    expect($acme->members()->count())->toBe(2)
        ->and($ops->hasMember($ada))->toBeTrue()
        ->and($ada->rosterOrganizations()->pluck('slug')->all())->toBe(['acme']);

    // Validation rules look in the renamed tables too.
    expect(validator(['name' => 'Other', 'slug' => 'acme'], CreateOrganizationData::rules())->errors()->keys())->toBe(['slug'])
        ->and(validator(['name' => 'Ops', 'slug' => 'ops'], CreateTeamData::rules($acme))->fails())->toBeTrue();
});
