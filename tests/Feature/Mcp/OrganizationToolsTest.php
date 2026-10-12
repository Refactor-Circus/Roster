<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use RefactorCircus\Roster\Domains\Invitation\Mcp\Tools\AcceptInvitationTool;
use RefactorCircus\Roster\Domains\Invitation\Mcp\Tools\CreateInvitationTool;
use RefactorCircus\Roster\Domains\Invitation\Mcp\Tools\DeclineInvitationTool;
use RefactorCircus\Roster\Domains\Invitation\Mcp\Tools\ListInvitationsTool;
use RefactorCircus\Roster\Domains\Invitation\Mcp\Tools\RevokeInvitationTool;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Invitation\Notifications\InvitationNotification;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\AddMemberTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\CreateOrganizationTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\DeleteOrganizationTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\JoinByDomainTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\ListMembersTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\ListOrganizationsTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\RemoveMemberTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\ShowOrganizationTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\SwitchContextTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\TransferOwnershipTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\UpdateOrganizationTool;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\Team\Mcp\Tools\AddTeamMemberTool;
use RefactorCircus\Roster\Domains\Team\Mcp\Tools\CreateTeamTool;
use RefactorCircus\Roster\Domains\Team\Mcp\Tools\DeleteTeamTool;
use RefactorCircus\Roster\Domains\Team\Mcp\Tools\ListTeamsTool;
use RefactorCircus\Roster\Domains\Team\Mcp\Tools\RemoveTeamMemberTool;
use RefactorCircus\Roster\Domains\Team\Mcp\Tools\ShowTeamTool;
use RefactorCircus\Roster\Domains\Team\Mcp\Tools\UpdateTeamTool;

function httpData(string $route, array $parameters = []): array
{
    return test()->getJson(route($route, $parameters))->json();
}

it('lists organizations, members, teams and invitations with parity, including empty lists', function (): void {
    mcpTool(ListOrganizationsTool::class)->assertOk()->assertStructuredContent([
        'data' => [],
        'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 0],
    ]);

    $organization = organization();
    app(CreateTeamAction::class)->execute($organization, new CreateTeamData(name: 'Ops'));

    mcpTool(ListOrganizationsTool::class)->assertOk()->assertStructuredContent(httpData('roster.organizations.index')['data'] === [] ? [] : [
        'data' => httpData('roster.organizations.index')['data'],
        'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 1],
    ]);

    foreach ([
        [ListMembersTool::class, 'roster.organizations.members.index'],
        [ListTeamsTool::class, 'roster.organizations.teams.index'],
        [ListInvitationsTool::class, 'roster.organizations.invitations.index'],
    ] as [$tool, $route]) {
        $http = httpData($route, [$organization->slug]);

        mcpTool($tool, ['organization' => $organization->slug])->assertOk()->assertStructuredContent([
            'data' => $http['data'],
            'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => $http['meta']['total']],
        ]);
    }
});

it('manages an organization with parity to the http payload', function (): void {
    $owner = user();
    $ada = user();

    mcpTool(CreateOrganizationTool::class, ['name' => 'Acme', 'owner' => $owner->getRouteKey()])
        ->assertOk()
        ->assertStructuredContent(httpData('roster.organizations.show', ['acme']));

    mcpTool(UpdateOrganizationTool::class, ['organization' => 'acme', 'domains' => ['acme.com']])
        ->assertOk()
        ->assertStructuredContent(httpData('roster.organizations.show', ['acme']));

    mcpTool(ShowOrganizationTool::class, ['organization' => 'acme'])
        ->assertOk()
        ->assertStructuredContent(httpData('roster.organizations.show', ['acme']));

    mcpTool(AddMemberTool::class, ['organization' => 'acme', 'user' => $ada->getRouteKey()])->assertOk();

    mcpTool(TransferOwnershipTool::class, ['organization' => 'acme', 'user' => $ada->getRouteKey()])
        ->assertOk()
        ->assertStructuredContent(httpData('roster.organizations.show', ['acme']));

    mcpTool(RemoveMemberTool::class, ['organization' => 'acme', 'user' => $owner->getRouteKey()])->assertOk()->assertSee('Member removed.');

    mcpTool(DeleteOrganizationTool::class, ['organization' => 'acme'])->assertOk();

    expect(OrganizationModel::query()->count())->toBe(0);

    mcpTool(ShowOrganizationTool::class, ['organization' => 'acme'])->assertHasErrors(['Not found.']);
});

it('manages teams with parity to the http payload', function (): void {
    $organization = organization();
    $ada = user();
    app(AddMemberAction::class)->execute($organization, new AddMemberData(user: $ada->getRouteKey()));
    $args = ['organization' => $organization->slug];

    mcpTool(CreateTeamTool::class, $args + ['name' => 'Ops'])
        ->assertOk()
        ->assertStructuredContent(httpData('roster.organizations.teams.show', [$organization->slug, 'ops']));

    mcpTool(AddTeamMemberTool::class, $args + ['team' => 'ops', 'user' => $ada->getRouteKey()])
        ->assertOk()
        ->assertStructuredContent(httpData('roster.organizations.teams.show', [$organization->slug, 'ops']));

    mcpTool(UpdateTeamTool::class, $args + ['team' => 'ops', 'slug' => 'operations'])
        ->assertOk()
        ->assertStructuredContent(httpData('roster.organizations.teams.show', [$organization->slug, 'operations']));

    mcpTool(ShowTeamTool::class, $args + ['team' => 'operations'])
        ->assertOk()
        ->assertStructuredContent(httpData('roster.organizations.teams.show', [$organization->slug, 'operations']));

    mcpTool(RemoveTeamMemberTool::class, $args + ['team' => 'operations', 'user' => $ada->getRouteKey()])->assertOk();

    mcpTool(DeleteTeamTool::class, $args + ['team' => 'operations'])->assertOk()->assertSee('Team deleted.');
});

it('runs the invitation lifecycle', function (): void {
    Notification::fake();
    $organization = organization();

    mcpTool(CreateInvitationTool::class, ['organization' => $organization->slug, 'email' => 'ada@example.com'])->assertOk();
    mcpTool(CreateInvitationTool::class, ['organization' => $organization->slug, 'email' => 'eve@example.com'])->assertOk();

    $tokens = [];
    Notification::assertSentOnDemand(InvitationNotification::class, function (InvitationNotification $notification) use (&$tokens): bool {
        $tokens[$notification->invitation->email] = $notification->token;

        return true;
    });

    // Answering acts as the authenticated user.
    mcpTool(AcceptInvitationTool::class, ['token' => $tokens['ada@example.com']])->assertHasErrors(['Unauthorized.']);

    $this->actingAs(user(['email' => 'ada@example.com']));
    mcpTool(AcceptInvitationTool::class, ['token' => $tokens['ada@example.com']])->assertOk();

    $this->actingAs(user(['email' => 'eve@example.com']));
    mcpTool(DeclineInvitationTool::class, ['token' => $tokens['eve@example.com']])->assertOk();

    $grace = InvitationModel::query()->create([
        'organization_id' => $organization->getKey(),
        'email' => 'grace@example.com',
        'token_hash' => hash('sha256', 'x'),
        'expires_at' => now()->addDay(),
    ]);

    mcpTool(RevokeInvitationTool::class, ['organization' => $organization->slug, 'invitation' => $grace->id])->assertOk();

    expect(InvitationModel::query()->get()->map(fn (InvitationModel $invitation): string => $invitation->status()->value)->sort()->values()->all())
        ->toBe(['accepted', 'declined', 'revoked']);
});

it('switches context and runs domain join', function (): void {
    $ada = user(['email' => 'ada@acme.com']);
    organization(attributes: ['name' => 'Acme', 'domains' => ['acme.com'], 'auto_join' => true]);

    mcpTool(JoinByDomainTool::class, ['user' => $ada->getRouteKey()])->assertOk()->assertSee('acme');

    mcpTool(SwitchContextTool::class, ['user' => $ada->getRouteKey(), 'organization' => 'acme'])
        ->assertOk()
        ->assertStructuredContent(httpData('roster.users.show', [$ada->getRouteKey()]));
});
