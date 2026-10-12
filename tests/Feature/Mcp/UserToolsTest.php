<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\User\Mcp\Tools\CreateUserTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\DeactivateUserTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\DeleteUserTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\ListUsersTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\ReactivateUserTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\ShowUserTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\SuspendUserTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\UpdateProfileTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\UpdateUserTool;
use Workbench\App\Models\User;

/**
 * @return array<string, mixed>
 */
function httpUser(User $user): array
{
    return test()->getJson(route('roster.users.show', $user->getRouteKey()))->json();
}

it('lists an empty directory without erroring', function (): void {
    mcpTool(ListUsersTool::class)->assertOk()->assertStructuredContent([
        'data' => [],
        'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 0],
    ]);
});

it('lists users with parity to the http payload', function (): void {
    user();

    $http = test()->getJson(route('roster.users.index'))->json('data');

    mcpTool(ListUsersTool::class)->assertOk()->assertStructuredContent([
        'data' => $http,
        'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 1],
    ]);
});

it('shows a user with parity to the http payload', function (): void {
    $user = user();

    mcpTool(ShowUserTool::class, ['user' => $user->getRouteKey()])->assertOk()->assertStructuredContent(httpUser($user));
});

it('creates a user with parity to the http payload', function (): void {
    $response = mcpTool(CreateUserTool::class, ['name' => 'Ada', 'email' => 'ada@example.com', 'bio' => 'Hi']);

    $response->assertOk()->assertStructuredContent(httpUser(User::query()->sole()));
});

it('updates a user and profile with parity to the http payload', function (): void {
    $user = user();

    mcpTool(UpdateUserTool::class, ['user' => $user->getRouteKey(), 'name' => 'Renamed'])
        ->assertOk()
        ->assertStructuredContent(httpUser($user));

    mcpTool(UpdateProfileTool::class, ['user' => $user->getRouteKey(), 'timezone' => 'UTC'])
        ->assertOk()
        ->assertStructuredContent(httpUser($user));
});

it('changes status with parity to the http payload', function (string $tool): void {
    $user = user();

    if ($tool === ReactivateUserTool::class) {
        $user->roster()->update(['status' => 'suspended']);
    }

    mcpTool($tool, ['user' => $user->getRouteKey(), 'reason' => 'Because'])
        ->assertOk()
        ->assertStructuredContent(httpUser($user));
})->with([SuspendUserTool::class, DeactivateUserTool::class, ReactivateUserTool::class]);

it('deletes a user', function (): void {
    $user = user();

    mcpTool(DeleteUserTool::class, ['user' => $user->getRouteKey()])->assertOk()->assertSee('User deleted.');

    expect(User::query()->count())->toBe(0);
});

it('reports an unknown user as not found', function (): void {
    mcpTool(ShowUserTool::class, ['user' => 999])->assertHasErrors(['Not found.']);
});

it('reports validation failures as tool errors', function (): void {
    mcpTool(CreateUserTool::class, ['name' => 'Ada'])->assertHasErrors();
});

it('reports every validation failure in one error, identifiers and input together', function (): void {
    mcpTool(SuspendUserTool::class, ['reason' => str_repeat('x', 1001)])
        ->assertHasErrors(['The user field is required.', 'The reason field must not be greater than 1000 characters.']);
});
