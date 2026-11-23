<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Mcp\Server\Tool;
use RefactorCircus\Keystone\Testing\McpTools;
use RefactorCircus\Keystone\Testing\Parity;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Invitation\Services\InvitationTokens;
use RefactorCircus\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Mcp\RosterServer;
use RefactorCircus\Roster\Tests\AtriumUnavailableTestCase;
use RefactorCircus\Roster\Tests\AuthorizationTestCase;
use RefactorCircus\Roster\Tests\CortexTestCase;
use RefactorCircus\Roster\Tests\DefaultsTestCase;
use RefactorCircus\Roster\Tests\KeenTestCase;
use RefactorCircus\Roster\Tests\RenamedTablesTestCase;
use RefactorCircus\Roster\Tests\TestCase;
use RefactorCircus\Roster\Tests\TraitlessTestCase;
use RefactorCircus\Roster\Tests\TransfersUnavailableTestCase;
use RefactorCircus\Roster\Tests\UlidTestCase;
use Workbench\App\Models\User;
use Workbench\Database\Factories\UserFactory;

uses(TestCase::class)->in('Feature');
uses(AuthorizationTestCase::class)->in('Authorization');
uses(CortexTestCase::class)->in('Cortex');
uses(DefaultsTestCase::class)->in('Defaults');
uses(TraitlessTestCase::class)->in('Modes/Traitless');
uses(UlidTestCase::class)->in('Modes/Ulid');
uses(TestCase::class)->in('Modes/Owned');
uses(KeenTestCase::class)->in('Modes/Keen');
uses(TransfersUnavailableTestCase::class)->in('Modes/TransfersUnavailable');
uses(AtriumUnavailableTestCase::class)->in('Modes/AtriumUnavailable');
uses(RenamedTablesTestCase::class)->in('Modes/RenamedTables');

/**
 * Call a Roster tool as an MCP client does, through tool search, with its
 * answer checked against the tool's output schema.
 *
 * @param  class-string<Tool>  $tool
 * @param  array<string, mixed>  $arguments
 */
function mcpTool(string $tool, array $arguments = []): TestResponse
{
    // Through tool search, as a client calls it, with its output schema checked.
    return McpTools::call(RosterServer::class, $tool, $arguments);
}

function user(array $attributes = []): User
{
    return UserFactory::new()->create($attributes);
}

/**
 * Give a user a role by slug: global, in an organization, or on a team.
 */
function grant(User $user, string $slug, ?OrganizationModel $organization = null, ?TeamModel $team = null): void
{
    $role = RoleModel::query()->where('slug', $slug)->firstOrFail();

    RoleAssignmentModel::query()->create([
        'role_id' => $role->getKey(),
        'user_id' => $user->getKey(),
        'organization_id' => $organization?->getKey() ?? $team?->organization_id,
        'team_id' => $team?->getKey(),
    ]);

    app(Permissions::class)->flush();
}

/**
 * A global role holding exactly the given permissions.
 *
 * @param  array<int, string>  $permissions
 */
function roleWith(array $permissions, string $scope = 'global'): RoleModel
{
    $role = RoleModel::factory()->create(['scope' => $scope]);
    $role->permissions()->sync(PermissionModel::query()->whereIn('name', $permissions)->pluck('id'));

    return $role;
}

function organization(?User $owner = null, array $attributes = []): OrganizationModel
{
    return app(CreateOrganizationAction::class)->execute(CreateOrganizationData::from(['name' => $attributes['name'] ?? 'Acme'] + $attributes), $owner ?? user());
}

/**
 * A pending invitation into the organization, onto the given teams, stored
 * as CreateInvitationAction stores one: by its token's hash only. Returns the
 * plain token, which only the invitation email carries.
 *
 * @param  array<int, TeamModel>  $teams
 * @param  array<string, mixed>  $attributes
 */
function invitationToken(OrganizationModel $organization, array $teams = [], array $attributes = []): string
{
    $token = InvitationTokens::generate();

    InvitationModel::factory()->create($attributes + [
        'organization_id' => $organization->getKey(),
        'token_hash' => InvitationTokens::hash($token),
        'teams' => array_map(fn (TeamModel $team): mixed => $team->getKey(), $teams),
    ]);

    return $token;
}

/**
 * Actions missing from a surface, keyed by surface.
 *
 * Every use case must be reachable from the HTTP API and MCP. An Action
 * counts as reachable when its class name appears in that surface's
 * request classes.
 *
 * @return array<string, array<int, string>>
 */
const PARITY_EXCEPTIONS = [
    // Using the one-time link swaps a browser session; it has no API or
    // MCP meaning. Start, list and stop are on both surfaces.
    'EnterImpersonationAction' => ['http', 'mcp'],
    // The public invitation lookup is for the page a guest opens from the
    // email link, served over HTTP by token alone. An MCP tool acts as a
    // signed-in user, who has no use for it; offering it there would only
    // add another way to probe tokens.
    'LookUpInvitationAction' => ['mcp'],
    // A typeahead for UIs: answers a handful of names as a user types.
    // Agents find users with list-users-tool, whose `search` matches
    // anywhere in the name, email or display name and pages through all of
    // them.
    'SearchUsersAction' => ['mcp'],
    // A typeahead for UIs, as SearchUsersAction is. Agents find
    // organizations with list-organizations-tool, whose `search` matches
    // anywhere in the name or slug and pages through all of them.
    'SearchOrganizationsAction' => ['mcp'],
];

function parityGaps(): array
{
    $root = dirname(__DIR__).'/src';

    return Parity::gaps($root.'/Domains/*/Actions/*.php', [
        'http' => $root.'/Domains/*/Http/Requests/*.php',
        'mcp' => $root.'/Domains/*/Mcp/Requests/*.php',
    ], PARITY_EXCEPTIONS);
}
