<?php

declare(strict_types=1);

use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\JoinOrganizationsByDomainAction;
use RefactorCircus\Roster\Domains\Organization\Actions\ListMembersAction;
use RefactorCircus\Roster\Domains\Organization\Actions\SwitchContextAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Data\SwitchContextData;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Actions\DeleteUserAction;
use RefactorCircus\Roster\Domains\User\Actions\PurgeUserAction;
use RefactorCircus\Roster\Domains\User\Actions\RestoreUserAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Roster;
use Workbench\App\Models\User;

it('falls back to the first organization joined', function (): void {
    $ada = user();
    $first = organization($ada, ['name' => 'First']);
    organization($ada, ['name' => 'Second']);

    expect(app(Roster::class)->organization($ada)?->is($first))->toBeTrue()
        ->and($ada->currentOrganization()?->is($first))->toBeTrue()
        ->and($ada->currentTeam())->toBeNull();
});

it('switches organization and team', function (): void {
    $ada = user();
    organization($ada, ['name' => 'First']);
    $second = organization($ada, ['name' => 'Second']);
    $team = app(CreateTeamAction::class)->execute($second, new CreateTeamData(name: 'Ops'));
    app(AddTeamMemberAction::class)->execute($team, new AddTeamMemberData(user: $ada->getRouteKey()));

    app(SwitchContextAction::class)->execute($ada, new SwitchContextData(organization: 'second', team: 'ops'));

    expect(app(Roster::class)->organization($ada)?->is($second))->toBeTrue()
        ->and(app(Roster::class)->team($ada)?->is($team))->toBeTrue();
});

it('refuses to switch into an organization or team the user is not in', function (array $data): void {
    $ada = user();
    $mine = organization($ada, ['name' => 'Mine']);
    organization(attributes: ['name' => 'Theirs']);
    app(CreateTeamAction::class)->execute($mine, new CreateTeamData(name: 'Ops'));

    app(SwitchContextAction::class)->execute($ada, SwitchContextData::from($data));
})->with([
    'foreign organization' => [['organization' => 'theirs']],
    'team without a seat' => [['organization' => 'mine', 'team' => 'ops']],
])->throws(ValidationException::class);

it('joins auto-join organizations on email verification', function (): void {
    organization(attributes: ['name' => 'Acme', 'domains' => ['acme.com'], 'auto_join' => true]);
    organization(attributes: ['name' => 'Closed', 'domains' => ['closed.com'], 'auto_join' => false]);

    $ada = user(['email' => 'ada@acme.com', 'email_verified_at' => null]);
    $joined = app(JoinOrganizationsByDomainAction::class)->execute($ada);

    expect($joined)->toBeEmpty();

    $ada->markEmailAsVerified();
    event(new Verified($ada));

    $acme = OrganizationModel::query()->where('slug', 'acme')->sole();

    expect($acme->membershipFor($ada)?->source)->toBe(MembershipSource::Domain);

    $grace = user(['email' => 'grace@closed.com']);

    expect(app(JoinOrganizationsByDomainAction::class)->execute($grace))->toBeEmpty();
});

it('creates a personal organization when configured', function (): void {
    config()->set('roster.organizations.personal', true);

    $ada = app(CreateUserAction::class)->execute(new CreateUserData(name: 'Ada', email: 'ada@example.com'));

    $personal = OrganizationModel::query()->sole();

    expect($personal->personal)->toBeTrue()
        ->and($personal->isOwnedBy($ada))->toBeTrue()
        ->and($personal->membershipFor($ada)?->source)->toBe(MembershipSource::Personal);

    // Deleting the user moves their personal organization to Deleted too;
    // restoring brings both back.
    app(DeleteUserAction::class)->execute($ada);

    expect(OrganizationModel::query()->count())->toBe(0)->and(OrganizationModel::onlyTrashed()->count())->toBe(1);

    app(RestoreUserAction::class)->execute(User::withTrashed()->sole());

    expect(OrganizationModel::query()->count())->toBe(1);
});

it('refuses to delete a user who owns a shared organization', function (): void {
    $ada = user();
    organization($ada);

    app(DeleteUserAction::class)->execute($ada);
})->throws(ValidationException::class);

it('keeps a deleted user\'s memberships hidden, and removes them when purged', function (): void {
    $organization = organization();
    $ada = user();
    app(AddMemberAction::class)->execute($organization, new AddMemberData(user: $ada->getRouteKey()));

    app(DeleteUserAction::class)->execute($ada);

    expect($organization->memberships()->count())->toBe(2)
        ->and(app(ListMembersAction::class)->execute($organization)->total())->toBe(1);

    app(PurgeUserAction::class)->execute(User::withTrashed()->whereKey($ada->getKey())->sole());

    expect($organization->memberships()->count())->toBe(1);
});

it('blocks users without an organization', function (): void {
    Route::middleware(['web', 'roster.organization'])->get('roster-test/tenant', fn (): string => 'ok');

    $this->actingAs(user())->get('roster-test/tenant')->assertForbidden();

    $ada = user();
    organization($ada);

    $this->actingAs($ada)->get('roster-test/tenant')->assertOk();
});
