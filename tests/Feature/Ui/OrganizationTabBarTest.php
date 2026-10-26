<?php

declare(strict_types=1);

use Illuminate\Testing\TestResponse;
use RefactorCircus\Roster\Atrium\Tabs;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;

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

it('renders the built-in tabs in order, each linking to its ?tab=', function (): void {
    $this->actingAs(user());
    $organization = organization();

    $links = organizationTabBarLinks($this->get(route('atrium.roster.organizations.show', $organization))->assertOk());

    expect(array_map(strval(...), array_keys($links)))->toBe(['members', 'teams', 'invitations', 'roles', 'sso', 'scim', 'settings']);

    foreach ($links as $key => $href) {
        expect($href)->toBe(route('atrium.roster.organizations.show', [$organization, 'tab' => $key]));
    }
});

it('shows exactly the built-in tabs offered here', function (): void {
    $this->actingAs(user());
    $organization = organization();

    $links = organizationTabBarLinks($this->get(route('atrium.roster.organizations.show', $organization))->assertOk());

    // Without refactor-circus/keen or refactor-circus/cortex, activity and MCP are not offered.
    expect(array_map(strval(...), array_keys($links)))->toBe(array_values(array_diff(Tabs::ORGANIZATION_TABS, ['mcp', 'activity'])));
});

it('shows exactly the built-in tabs the viewer may see', function (): void {
    config()->set('roster.authorization', true);
    $organization = organization();
    $viewer = user();

    RoleAssignmentModel::query()->create([
        'role_id' => roleWith(['roster.organizations.view', 'roster.members.view', 'roster.sso.view'], 'organization')->id,
        'user_id' => $viewer->getKey(),
        'organization_id' => $organization->getKey(),
    ]);

    $links = organizationTabBarLinks($this->actingAs($viewer)->get(route('atrium.roster.organizations.show', $organization))->assertOk());

    expect(array_map(strval(...), array_keys($links)))
        ->toBe(array_values(array_intersect(Tabs::ORGANIZATION_TABS, ['members', 'sso', 'settings'])));
});

it('marks only the open tab as the current page', function (): void {
    $this->actingAs(user());
    $organization = organization();

    $content = (string) $this->get(route('atrium.roster.organizations.show', [$organization, 'tab' => 'teams']))->assertOk()->getContent();

    preg_match_all('/<a [^>]*data-testid="tab-[^"]+"[^>]*>/', $content, $tags);
    $current = array_values(array_filter($tags[0], fn (string $tag): bool => str_contains($tag, 'aria-current="page"')));

    expect($current)->toHaveCount(1)
        ->and($current[0])->toContain('data-testid="tab-teams"');
});
