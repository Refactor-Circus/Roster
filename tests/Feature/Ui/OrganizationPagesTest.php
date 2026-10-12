<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use RefactorCircus\Roster\Atrium\RosterPlugin;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Invitation\Notifications\InvitationNotification;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Roster;

beforeEach(function (): void {
    $this->admin = user(['name' => 'Admin']);
    $this->actingAs($this->admin);
});

it('lists, creates and shows organizations', function (): void {
    $this->get(route('atrium.roster.organizations.create'))->assertOk();

    $this->post(route('atrium.roster.organizations.store'), [
        'name' => 'Acme',
        'owner' => $this->admin->getRouteKey(),
        'domains' => "acme.com\nacme.io",
    ])->assertRedirect(route('atrium.roster.organizations.show', 'acme'));

    expect(OrganizationModel::query()->sole()->domains()->pluck('domain')->sort()->values()->all())->toBe(['acme.com', 'acme.io']);

    $this->get(route('atrium.roster.organizations.index'))->assertOk()->assertSee('Acme');

    foreach (['members', 'teams', 'invitations', 'settings'] as $tab) {
        $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => $tab]))->assertOk()->assertSee('Acme');
    }
});

it('edits settings and deletes', function (): void {
    $organization = organization($this->admin);

    $this->patch(route('atrium.roster.organizations.update', $organization), [
        'name' => 'Renamed',
        'slug' => $organization->slug,
        'domains' => 'renamed.com',
        'auto_join' => '1',
    ])->assertRedirect();

    $organization->refresh();

    expect($organization->name)->toBe('Renamed')->and($organization->auto_join)->toBeTrue();

    $this->get(route('atrium.roster.organizations.show', [$organization, 'tab' => 'settings']))
        ->assertSee('data-testid="danger-zone"', false)
        ->assertSee('Moves the organization to Deleted');

    // Refused until "I understand" is ticked.
    $this->delete(route('atrium.roster.organizations.destroy', $organization))->assertSessionHasErrors('confirm');
    expect(OrganizationModel::query()->count())->toBe(1);

    $this->delete(route('atrium.roster.organizations.destroy', $organization), ['confirm' => '1'])->assertRedirect(route('atrium.roster.organizations.index', ['trashed' => 'only']));

    expect(OrganizationModel::query()->count())->toBe(0);
});

it('manages members and ownership', function (): void {
    $organization = organization($this->admin);
    $ada = user(['name' => 'Ada']);

    $this->post(route('atrium.roster.organizations.members.store', $organization), ['user' => $ada->getRouteKey()])->assertRedirect();
    $this->get(route('atrium.roster.organizations.show', $organization))->assertSee('Ada');

    $this->post(route('atrium.roster.organizations.transfer', $organization), ['user' => $ada->getRouteKey()])->assertRedirect();
    expect($organization->refresh()->isOwnedBy($ada))->toBeTrue();

    $this->delete(route('atrium.roster.organizations.members.destroy', [$organization, $this->admin->getRouteKey()]))->assertRedirect();
    expect($organization->membershipFor($this->admin))->toBeNull();
});

it('manages teams', function (): void {
    $organization = organization($this->admin);
    $ada = user(['name' => 'Ada']);
    app(AddMemberAction::class)->execute($organization, new AddMemberData(user: $ada->getRouteKey()));

    $this->post(route('atrium.roster.teams.store', $organization), ['name' => 'Ops'])
        ->assertRedirect(route('atrium.roster.teams.show', [$organization, 'ops']));

    $this->post(route('atrium.roster.teams.members.store', [$organization, 'ops']), ['user' => $ada->getRouteKey()])->assertRedirect();
    $this->get(route('atrium.roster.teams.show', [$organization, 'ops']))->assertOk()->assertSee('Ada');

    $this->patch(route('atrium.roster.teams.update', [$organization, 'ops']), ['name' => 'Ops Team', 'slug' => 'ops'])->assertRedirect();
    $this->delete(route('atrium.roster.teams.members.destroy', [$organization, 'ops', $ada->getRouteKey()]))->assertRedirect();
    $this->delete(route('atrium.roster.teams.destroy', [$organization, 'ops']))->assertRedirect();

    expect($organization->teams()->count())->toBe(0);
});

it('invites and revokes', function (): void {
    Notification::fake();
    $organization = organization($this->admin);
    app(CreateTeamAction::class)->execute($organization, new CreateTeamData(name: 'Ops'));

    $this->post(route('atrium.roster.invitations.store', $organization), ['email' => 'ada@example.com', 'teams' => ['ops']])->assertRedirect();

    Notification::assertSentOnDemand(InvitationNotification::class);
    $invitation = InvitationModel::query()->sole();

    $this->get(route('atrium.roster.organizations.show', [$organization, 'tab' => 'invitations']))->assertSee('ada@example.com');

    $this->delete(route('atrium.roster.invitations.revoke', [$organization, $invitation->id]))->assertRedirect();

    expect($invitation->refresh()->revoked_at)->not->toBeNull();
});

it('switches a user context and runs domain join from the user page', function (): void {
    $ada = user(['email' => 'ada@acme.com']);
    organization(attributes: ['name' => 'Acme', 'domains' => ['acme.com'], 'auto_join' => true]);

    $this->post(route('atrium.roster.users.domain-join', $ada->getRouteKey()))->assertRedirect()->assertSessionHas('status');

    $this->get(route('atrium.roster.users.show', $ada->getRouteKey()))->assertOk()->assertSee('Acme');

    $this->put(route('atrium.roster.users.context', $ada->getRouteKey()), ['organization' => 'acme', 'team' => ''])->assertRedirect();

    expect(app(Roster::class)->organization($ada)?->slug)->toBe('acme');
});

it('renders the organizations widget', function (): void {
    organization($this->admin);

    $widget = collect(app(RosterPlugin::class)->widgets())
        ->firstOrFail(fn ($definition): bool => $definition->key === 'roster.organizations');

    expect(view('roster::ui.widgets.organizations', $widget->resolveData())->render())->toContain('Organizations');
});

it('shows each member\'s status on the members tab', function (): void {
    $this->actingAs(user());
    $acme = organization(attributes: ['name' => 'Acme']);
    $pending = app(CreateUserAction::class)->execute(new CreateUserData(name: 'Pat', email: 'pat@example.com', status: UserStatus::Pending));
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $pending->getRouteKey()));

    $this->get(route('atrium.roster.organizations.show', 'acme'))
        ->assertOk()
        ->assertSee('Awaiting approval')
        ->assertSee('data-testid="member-status"', false);

    $this->getJson(route('roster.organizations.members.index', 'acme'))->assertOk()->assertJsonFragment(['status' => 'pending']);
});
