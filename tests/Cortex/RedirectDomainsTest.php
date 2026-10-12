<?php

declare(strict_types=1);

use RefactorCircus\Cortex\Domains\RedirectDomain\Actions\CreateRedirectDomainAction;
use RefactorCircus\Cortex\Domains\RedirectDomain\Data\CreateRedirectDomainData;
use RefactorCircus\Cortex\Domains\RedirectDomain\Models\RedirectDomainModel;
use RefactorCircus\Cortex\Domains\RedirectDomain\Services\RedirectDomains;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use Workbench\App\Models\User;

/**
 * A user who may open Atrium, holding global permissions and, in an
 * organization, organization ones.
 *
 * @param  array<int, string>  $permissions
 * @param  array<int, string>  $inOrganization
 */
function domainManager(array $permissions = [], ?OrganizationModel $organization = null, array $inOrganization = []): User
{
    $user = user();

    RoleAssignmentModel::query()->create(['role_id' => roleWith(['atrium.view', ...$permissions])->id, 'user_id' => $user->getKey()]);

    if ($organization !== null) {
        RoleAssignmentModel::query()->create([
            'role_id' => roleWith($inOrganization, 'organization')->id,
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
        ]);
    }

    return $user;
}

it('manages an organization\'s redirect domains on its MCP tab', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $admin = domainManager([], $acme, ['roster.organizations.view', 'roster.organizations.update']);

    $this->actingAs($admin)
        ->get(route('atrium.roster.organizations.show', [$acme, 'tab' => 'mcp']))
        ->assertOk()
        ->assertSee('data-testid="tab-mcp"', false)
        ->assertSee(__('roster::roster.no_redirect_domains'));

    $this->actingAs($admin)
        ->post(route('atrium.roster.organizations.redirect-domains.store', $acme), ['domain' => 'https://claude.ai/api/mcp/auth_callback'])
        ->assertRedirect(route('atrium.roster.organizations.show', [$acme, 'tab' => 'mcp']));

    $domain = RedirectDomainModel::query()->sole();

    expect($domain->domain)->toBe('https://claude.ai')
        ->and($domain->owner?->is($acme))->toBeTrue()
        ->and(app(RedirectDomains::class)->allowed())->toContain('https://claude.ai');

    $this->actingAs($admin)
        ->get(route('atrium.roster.organizations.show', [$acme, 'tab' => 'mcp']))
        ->assertSee('https://claude.ai')
        ->assertSee('data-testid="remove-redirect-domain"', false);

    $this->actingAs($admin)
        ->delete(route('atrium.roster.organizations.redirect-domains.destroy', [$acme, $domain->id]))
        ->assertRedirect(route('atrium.roster.organizations.show', [$acme, 'tab' => 'mcp']));

    expect(RedirectDomainModel::query()->count())->toBe(0);
});

it('offers the MCP tab only to those who may edit the organization', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $viewer = domainManager([], $acme, ['roster.organizations.view']);

    $this->actingAs($viewer)
        ->get(route('atrium.roster.organizations.show', $acme))
        ->assertOk()
        ->assertDontSee('data-testid="tab-mcp"', false);

    $this->actingAs($viewer)->get(route('atrium.roster.organizations.show', [$acme, 'tab' => 'mcp']))->assertForbidden();
    $this->actingAs($viewer)->post(route('atrium.roster.organizations.redirect-domains.store', $acme), ['domain' => 'claude.ai'])->assertForbidden();

    expect(RedirectDomainModel::query()->count())->toBe(0);
});

it('removes only the organization\'s own domains', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);
    $admin = domainManager([], $acme, ['roster.organizations.view', 'roster.organizations.update']);
    $theirs = app(CreateRedirectDomainAction::class)->execute(new CreateRedirectDomainData(domain: 'chatgpt.com'), $globex);

    $this->actingAs($admin)
        ->delete(route('atrium.roster.organizations.redirect-domains.destroy', [$acme, $theirs->id]))
        ->assertNotFound();

    expect(RedirectDomainModel::query()->whereKey($theirs->id)->exists())->toBeTrue();
});

it('refuses a value that is not a domain', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $admin = domainManager([], $acme, ['roster.organizations.view', 'roster.organizations.update']);

    $this->actingAs($admin)
        ->post(route('atrium.roster.organizations.redirect-domains.store', $acme), ['domain' => '*'])
        ->assertSessionHasErrors('domain');

    expect(RedirectDomainModel::query()->count())->toBe(0);
});

it('manages a user\'s redirect domains on their page', function (): void {
    $target = user(['name' => 'Ada']);
    $admin = domainManager(['roster.users.view', 'roster.users.update']);

    $this->actingAs($admin)
        ->get(route('atrium.roster.users.show', $target->getRouteKey()))
        ->assertOk()
        ->assertSee('data-testid="redirect-domains-card"', false)
        ->assertSee('data-testid="add-redirect-domain"', false);

    $this->actingAs($admin)
        ->post(route('atrium.roster.users.redirect-domains.store', $target->getRouteKey()), ['domain' => 'cursor.com'])
        ->assertRedirect(route('atrium.roster.users.show', $target->getRouteKey()));

    $domain = RedirectDomainModel::query()->sole();

    expect($domain->domain)->toBe('https://cursor.com')
        ->and($domain->owner?->is($target))->toBeTrue();

    $this->actingAs($admin)
        ->delete(route('atrium.roster.users.redirect-domains.destroy', [$target->getRouteKey(), $domain->id]))
        ->assertRedirect(route('atrium.roster.users.show', $target->getRouteKey()));

    expect(RedirectDomainModel::query()->count())->toBe(0);
});

it('shows a user\'s domains read-only to those who may not edit users', function (): void {
    $target = user();
    app(CreateRedirectDomainAction::class)->execute(new CreateRedirectDomainData(domain: 'cursor.com'), $target);
    $viewer = domainManager(['roster.users.view']);

    $this->actingAs($viewer)
        ->get(route('atrium.roster.users.show', $target->getRouteKey()))
        ->assertOk()
        ->assertSee('https://cursor.com')
        ->assertDontSee('data-testid="add-redirect-domain"', false)
        ->assertDontSee('data-testid="remove-redirect-domain"', false);

    $this->actingAs($viewer)
        ->post(route('atrium.roster.users.redirect-domains.store', $target->getRouteKey()), ['domain' => 'claude.ai'])
        ->assertForbidden();
});

it('hides redirect domains while Cortex ignores stored ones', function (): void {
    config(['cortex.redirect_domains.enabled' => false]);
    $acme = organization(attributes: ['name' => 'Acme']);
    $admin = domainManager(['roster.users.view', 'roster.users.update'], $acme, ['roster.organizations.view', 'roster.organizations.update']);

    $this->actingAs($admin)
        ->get(route('atrium.roster.organizations.show', $acme))
        ->assertDontSee('data-testid="tab-mcp"', false);

    $this->actingAs($admin)
        ->get(route('atrium.roster.users.show', $admin->getRouteKey()))
        ->assertDontSee('data-testid="redirect-domains-card"', false);

    $this->actingAs($admin)
        ->post(route('atrium.roster.organizations.redirect-domains.store', $acme), ['domain' => 'claude.ai'])
        ->assertNotFound();
});
