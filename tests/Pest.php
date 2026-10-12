<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Mcp\Server\Tool;
use RefactorCircus\Keystone\Testing\McpTools;
use RefactorCircus\Keystone\Testing\Parity;
use RefactorCircus\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Mcp\RosterServer;
use RefactorCircus\Roster\Tests\AtriumFeaturesTestCase;
use RefactorCircus\Roster\Tests\AuthorizationTestCase;
use RefactorCircus\Roster\Tests\BrowserTestCase;
use RefactorCircus\Roster\Tests\CortexTestCase;
use RefactorCircus\Roster\Tests\DefaultsTestCase;
use RefactorCircus\Roster\Tests\KeenTestCase;
use RefactorCircus\Roster\Tests\PennantPlusTestCase;
use RefactorCircus\Roster\Tests\RenamedTablesTestCase;
use RefactorCircus\Roster\Tests\SsoUnavailableTestCase;
use RefactorCircus\Roster\Tests\TestCase;
use RefactorCircus\Roster\Tests\TraitlessTestCase;
use RefactorCircus\Roster\Tests\TransfersUnavailableTestCase;
use RefactorCircus\Roster\Tests\UlidTestCase;
use Workbench\App\Models\User;
use Workbench\Database\Factories\UserFactory;

// pest-plugin-browser removes its output directories with @rmdir() when it
// boots. A missing directory raises a (suppressed) warning that failOnWarning
// turns into a failed run, even with every test passing, so make sure each
// one it removes exists; empty directories remove cleanly.
foreach (['Traces', 'Screenshots', 'Screenshots/Sliders', 'Screenshots/ImageDiffView'] as $directory) {
    if (! is_dir(__DIR__.'/Browser/'.$directory)) {
        @mkdir(__DIR__.'/Browser/'.$directory, recursive: true);
    }
}

uses(TestCase::class)->in('Feature');
uses(AuthorizationTestCase::class)->in('Authorization');
uses(CortexTestCase::class)->in('Cortex');
uses(BrowserTestCase::class)->in('Browser');
uses(DefaultsTestCase::class)->in('Defaults');
uses(TraitlessTestCase::class)->in('Modes/Traitless');
uses(UlidTestCase::class)->in('Modes/Ulid');
uses(TestCase::class)->in('Modes/Owned');
uses(AtriumFeaturesTestCase::class)->in('Modes/AtriumFeatures');
uses(KeenTestCase::class)->in('Modes/Keen');
uses(PennantPlusTestCase::class)->in('Modes/PennantPlus');
uses(SsoUnavailableTestCase::class)->in('Modes/SsoUnavailable');
uses(TransfersUnavailableTestCase::class)->in('Modes/TransfersUnavailable');
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
 * Actions missing from a surface, keyed by surface.
 *
 * Every use case must be reachable from the HTTP API, MCP and a UI. The UI
 * surface is the Atrium dashboard plus Roster's user-facing web pages
 * (accepting an invitation is self-service, not an admin screen). An Action
 * counts as reachable when its class name appears in that surface's request
 * or controller classes.
 *
 * @return array<string, array<int, string>>
 */
const PARITY_EXCEPTIONS = [
    // Using the one-time link swaps a browser session; it has no API or
    // MCP meaning. Start, list and stop are on every surface.
    'EnterImpersonationAction' => ['http', 'mcp'],
    // Signing in and linking happen in the browser, between Roster and the
    // identity provider; there is nothing for an API caller to send.
    'SsoLoginAction' => ['http', 'mcp'],
    'LinkSsoIdentityAction' => ['http', 'mcp'],
    // Machine-to-machine sync from an ERP or CRM. In Atrium the same results
    // come from the organization CSV import (bulk) and the link form.
    'SyncOrganizationAction' => ['ui'],
    'SyncOrganizationsAction' => ['ui'],
];

function parityGaps(): array
{
    $root = dirname(__DIR__).'/src';

    return Parity::gaps($root.'/Domains/*/Actions/*.php', [
        'http' => $root.'/Domains/*/Http/Requests/*.php',
        'mcp' => $root.'/Domains/*/Mcp/Requests/*.php',
        'ui' => [
            $root.'/Atrium/Http/Controllers/*.php',
            $root.'/Domains/*/Http/Controllers/*WebController.php',
            $root.'/Domains/*/Http/Controllers/TransferFileController.php',
        ],
    ], PARITY_EXCEPTIONS);
}
