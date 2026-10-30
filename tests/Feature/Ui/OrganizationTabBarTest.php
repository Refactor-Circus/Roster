<?php

declare(strict_types=1);

use Illuminate\Testing\TestResponse;
use RefactorCircus\Atrium\Domains\Screens\Data\ResolvedScreen;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;

/**
 * The organization screen's built-in tabs, in order.
 *
 * @return list<string>
 */
function organizationBuiltInTabs(): array
{
    return ['members', 'teams', 'invitations', 'roles', 'settings'];
}

/**
 * The organization screen's tab bar, as `data-testid` key => `href`, in the
 * order it renders.
 *
 * @return array<string, string>
 */
function organizationTabBarLinks(TestResponse $response): array
{
    preg_match_all('/<a href="([^"]*)"[^>]*?data-testid="tab-([^"]+)"/', (string) $response->getContent(), $matches, PREG_SET_ORDER);

    $links = [];

    foreach ($matches as $match) {
        $links[$match[2]] = html_entity_decode($match[1]);
    }

    return $links;
}

/**
 * Turn authorization on and sign in a user holding these permissions in the
 * organization.
 *
 * @param  array<int, string>  $permissions
 */
function organizationTabBarViewer(array $permissions): void
{
    config()->set('roster.authorization', true);
    $viewer = user();

    RoleAssignmentModel::query()->create([
        'role_id' => roleWith($permissions, 'organization')->id,
        'user_id' => $viewer->getKey(),
        'organization_id' => test()->organization->getKey(),
    ]);

    test()->actingAs($viewer);
}

beforeEach(function (): void {
    $this->organization = organization();
});

it('renders the built-in tabs in order, each linking to its ?tab=', function (): void {
    $this->actingAs(user());

    $links = organizationTabBarLinks($this->get(route('atrium.roster.organizations.show', $this->organization))->assertOk());

    expect(array_map(strval(...), array_keys($links)))->toBe(organizationBuiltInTabs());

    foreach ($links as $key => $href) {
        expect($href)->toBe(route('atrium.roster.organizations.show', [$this->organization, 'tab' => $key]));
    }
});

it('keeps the rest of the query string in each tab\'s link, except the page', function (): void {
    $this->actingAs(user());

    $links = organizationTabBarLinks($this->get(route('atrium.roster.organizations.show', [$this->organization, 'tab' => 'members', 'page' => 2, 'sort' => 'name']))->assertOk());

    expect($links['teams'])->toBe(route('atrium.roster.organizations.show', [$this->organization, 'tab' => 'teams', 'sort' => 'name']));
});

it('shows exactly the built-in tabs offered here', function (): void {
    $this->actingAs(user());

    $links = organizationTabBarLinks($this->get(route('atrium.roster.organizations.show', $this->organization))->assertOk());

    // Keen's history and Cortex's MCP domains are theirs to register.
    expect(array_keys($links))->not->toContain('activity')
        ->not->toContain('mcp');
});

it('shows exactly the built-in tabs the viewer may see', function (): void {
    organizationTabBarViewer(['roster.organizations.view', 'roster.members.view', 'roster.roles.view']);

    $links = organizationTabBarLinks($this->get(route('atrium.roster.organizations.show', $this->organization))->assertOk());

    expect(array_map(strval(...), array_keys($links)))->toBe(['members', 'roles', 'settings']);
});

it('shows no tab bar while the viewer may open only one built-in tab', function (): void {
    organizationTabBarViewer(['roster.organizations.view']);

    $this->get(route('atrium.roster.organizations.show', $this->organization))
        ->assertOk()
        ->assertViewHas('resolved', fn (ResolvedScreen $resolved): bool => $resolved->openTab === 'settings')
        ->assertDontSee('data-testid="screen-tabs"', false)
        ->assertDontSee('data-testid="tab-', false);
});

it('marks only the open tab as the current page', function (): void {
    $this->actingAs(user());

    $content = (string) $this->get(route('atrium.roster.organizations.show', [$this->organization, 'tab' => 'teams']))->assertOk()->getContent();

    preg_match_all('/<a [^>]*data-testid="tab-[^"]+"[^>]*>/', $content, $tags);
    $current = array_values(array_filter($tags[0], fn (string $tag): bool => str_contains($tag, 'aria-current="page"')));

    expect($current)->toHaveCount(1)
        ->and($current[0])->toContain('data-testid="tab-teams"');
});
