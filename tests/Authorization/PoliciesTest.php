<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\UpdateOrganizationTool;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationDomainModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\User\Models\UserModel;
use RefactorCircus\Roster\Tests\Fixtures\Policies\FrozenOrganizationPolicy;
use RefactorCircus\Roster\Tests\Fixtures\Policies\OpenPolicy;

/**
 * Every model class under src/Domains/{Domain}/Models.
 *
 * @return array<int, class-string>
 */
function rosterModels(): array
{
    return array_map(
        fn (string $path): string => 'RefactorCircus\\Roster\\Domains\\'.basename(dirname($path, 2)).'\\Models\\'.basename($path, '.php'),
        (array) glob(dirname(__DIR__, 2).'/src/Domains/*/Models/*Model.php'),
    );
}

it('registers a configurable policy for every model', function (): void {
    // Core models from `roster.policies`; the SSO and SCIM domains register
    // their own models' policies with the Gate when they boot.
    $policies = config('roster.policies') + array_filter(
        Gate::policies(),
        fn (string $model): bool => str_starts_with($model, 'RefactorCircus\\Roster\\'),
        ARRAY_FILTER_USE_KEY,
    );

    expect(array_keys($policies))->toEqualCanonicalizing(rosterModels());

    foreach ($policies as $model => $policy) {
        expect(Gate::getPolicyFor($model))->toBeInstanceOf($policy);
    }
});

it('asks the configured policy on the API and MCP', function (): void {
    $owner = user();
    organization($owner, ['name' => 'Acme']);
    $this->actingAs($owner);

    $this->patchJson(route('roster.organizations.update', 'acme'), ['name' => 'Acme Inc'])->assertOk();

    Gate::policy(OrganizationModel::class, FrozenOrganizationPolicy::class);

    $this->patchJson(route('roster.organizations.update', 'acme'), ['name' => 'Acme Corp'])->assertForbidden();
    mcpTool(UpdateOrganizationTool::class, ['organization' => 'acme', 'name' => 'Acme Corp'])->assertHasErrors(['Unauthorized.']);

    // Abilities the policy leaves alone still come from Roster's permissions.
    $this->getJson(route('roster.organizations.show', 'acme'))->assertOk();
});

it('lets a policy allow what no role grants', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $this->actingAs(user());

    $this->postJson(route('roster.organizations.teams.store', 'acme'), ['name' => 'Ops'])->assertForbidden();

    Gate::policy(TeamModel::class, OpenPolicy::class);

    $this->postJson(route('roster.organizations.teams.store', 'acme'), ['name' => 'Ops'])->assertCreated();

    expect($acme->teams()->count())->toBe(1);
});

it('passes the scope and the user the call is about to the policy', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user();
    $seen = [];

    Gate::policy(UserModel::class, OpenPolicy::class);
    Gate::after(function (mixed $user, string $ability, ?bool $result, array $arguments) use (&$seen): void {
        $seen[] = [$ability, ...$arguments];
    });

    app(Authorizer::class)->check($ada, 'roster.users.manage-status', $acme, $ada);

    expect($seen)->toBe([['manageStatus', UserModel::class, $acme, $ada]]);
});

it('answers the application\'s own checks on records', function (): void {
    $owner = user();
    $acme = organization($owner, ['name' => 'Acme', 'domains' => ['acme.test']]);
    $member = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $member->getRouteKey()));
    $ops = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));
    $domain = OrganizationDomainModel::query()->sole();

    expect($owner->can('update', $acme))->toBeTrue()
        ->and($member->can('view', $acme))->toBeTrue()
        ->and($member->can('update', $acme))->toBeFalse()
        ->and($member->can('view', $ops))->toBeTrue()
        ->and($member->can('manage', $ops))->toBeFalse()
        ->and($owner->can('manage', $domain))->toBeTrue()
        ->and($member->can('manage', $domain))->toBeFalse()
        ->and(user()->can('view', $acme))->toBeFalse();
});
