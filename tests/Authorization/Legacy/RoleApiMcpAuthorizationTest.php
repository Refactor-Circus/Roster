<?php

declare(strict_types=1);

require_once __DIR__.'/fixtures.php';

use RefactorCircus\Roster\Mcp\RosterServer;

/**
 * Arguments that name existing records for every role and permission tool.
 *
 * @return array<class-string, Closure(array<string, mixed>): array<string, mixed>>
 */
function roleApiToolArguments(): array
{
    $user = fn (array $w): array => ['user' => $w['target']->getRouteKey()];

    return collect(RosterServer::TOOLS)
        ->filter(fn (string $tool): bool => isRoleApiTool($tool))
        ->mapWithKeys(fn (string $tool): array => [$tool => match (class_basename($tool)) {
            'ListRoleAssignmentsTool', 'AssignRoleTool', 'ListUserPermissionsTool' => $user,
            'RevokeRoleTool' => fn (array $w): array => $user($w) + ['assignment' => $w['assignment']->id],
            'ShowRoleTool', 'UpdateRoleTool', 'DeleteRoleTool' => fn (array $w): array => ['role' => $w['role']->id],
            'UpdatePermissionTool', 'DeletePermissionTool' => fn (): array => ['name' => 'invoices.edit'],
            default => fn (): array => [],
        }])
        ->all();
}

it('covers the role and permission tools', function (): void {
    expect(roleApiToolArguments())->not->toBeEmpty();
});

it('refuses every role and permission tool to guests and to users without the permission', function (string $tool): void {
    $world = roleApiWorld();
    $arguments = roleApiToolArguments()[$tool]($world);

    mcpTool($tool, $arguments)->assertHasErrors(['Unauthorized.']);

    $this->actingAs(user());

    mcpTool($tool, $arguments)->assertHasErrors(['Unauthorized.']);
})->with(fn (): array => array_keys(roleApiToolArguments()));

it('lets a super-admin past authorization on every role and permission tool', function (string $tool): void {
    $world = roleApiWorld();
    $admin = user();
    grant($admin, 'super-admin');
    $this->actingAs($admin);

    mcpTool($tool, roleApiToolArguments()[$tool]($world))->assertDontSee('Unauthorized.');
})->with(fn (): array => array_keys(roleApiToolArguments()));
