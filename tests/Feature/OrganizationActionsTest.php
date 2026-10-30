<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\DeleteOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\ListOrganizationsAction;
use RefactorCircus\Roster\Domains\Organization\Actions\PurgeOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\RestoreOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\ShowOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\TransferOwnershipAction;
use RefactorCircus\Roster\Domains\Organization\Actions\UpdateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Data\ListOrganizationsData;
use RefactorCircus\Roster\Domains\Organization\Data\TransferOwnershipData;
use RefactorCircus\Roster\Domains\Organization\Data\UpdateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

it('creates an organization with its owner as first member', function (): void {
    $owner = user();

    $organization = app(CreateOrganizationAction::class)->execute(new CreateOrganizationData(
        name: 'Acme Inc',
        owner: $owner->getRouteKey(),
        domains: ['Acme.com'],
        autoJoin: true,
    ));

    expect($organization->slug)->toBe('acme-inc')
        ->and($organization->isOwnedBy($owner))->toBeTrue()
        ->and($organization->auto_join)->toBeTrue()
        ->and($organization->domains->pluck('domain')->all())->toBe(['acme.com'])
        ->and($organization->membershipFor($owner)?->sourceEnum())->toBe(MembershipSource::Direct);
});

it('generates unique slugs', function (): void {
    $first = organization(attributes: ['name' => 'Acme']);
    $second = organization(attributes: ['name' => 'Acme']);

    expect($first->slug)->toBe('acme')->and($second->slug)->toBe('acme-2');
});

it('validates organization input', function (array $data): void {
    organization(attributes: ['name' => 'Taken', 'domains' => ['taken.com']]);

    validator($data + ['name' => 'X', 'owner' => user()->getRouteKey()], CreateOrganizationData::rules())->validate();
})->with([
    'unknown owner' => [['owner' => 999]],
    'duplicate slug' => [['slug' => 'taken']],
    'domain owned elsewhere' => [['domains' => ['taken.com']]],
    'malformed domain' => [['domains' => ['not a domain']]],
])->throws(ValidationException::class);

it('updates settings and replaces domains', function (): void {
    $organization = organization(attributes: ['domains' => ['old.com']]);

    $updated = app(UpdateOrganizationAction::class)->execute($organization, new UpdateOrganizationData(name: 'Renamed', domains: ['new.com']));

    expect($updated->name)->toBe('Renamed')
        ->and($updated->domains->pluck('domain')->all())->toBe(['new.com']);

    // Its own slug and domains pass the unique checks.
    expect(validator(['slug' => $organization->slug, 'domains' => ['new.com']], UpdateOrganizationData::rules($organization))->passes())->toBeTrue();
});

it('lists organizations, filtered by member', function (): void {
    $ada = user();
    organization($ada, ['name' => 'Beta']);
    organization(attributes: ['name' => 'Alpha']);

    $names = fn (array $filters): array => collect(app(ListOrganizationsAction::class)->execute(ListOrganizationsData::from($filters))->items())->pluck('name')->all();

    expect($names([]))->toBe(['Alpha', 'Beta'])
        ->and($names(['user' => $ada->getRouteKey()]))->toBe(['Beta'])
        ->and($names(['search' => 'alp']))->toBe(['Alpha']);
});

it('shows an organization with counts', function (): void {
    $organization = app(ShowOrganizationAction::class)->execute(organization());

    expect($organization->memberships_count)->toBe(1)->and($organization->teams_count)->toBe(0);
});

it('transfers ownership to a member only', function (): void {
    $organization = organization();
    $member = user();

    expect(fn () => app(TransferOwnershipAction::class)->execute($organization, new TransferOwnershipData(user: $member->getRouteKey())))
        ->toThrow(ValidationException::class);

    app(AddMemberAction::class)->execute($organization, new AddMemberData(user: $member->getRouteKey()));

    expect(app(TransferOwnershipAction::class)->execute($organization, new TransferOwnershipData(user: $member->getRouteKey()))->isOwnedBy($member))->toBeTrue();
});

it('soft-deletes an organization, keeping everything until it is purged', function (): void {
    $organization = organization();

    app(DeleteOrganizationAction::class)->execute($organization);

    expect(OrganizationModel::query()->count())->toBe(0)->and(MembershipModel::query()->count())->toBe(1);

    app(RestoreOrganizationAction::class)->execute(OrganizationModel::withTrashed()->sole());
    expect(OrganizationModel::query()->count())->toBe(1);

    app(DeleteOrganizationAction::class)->execute(OrganizationModel::query()->sole());
    app(PurgeOrganizationAction::class)->execute(OrganizationModel::withTrashed()->sole());

    expect(OrganizationModel::withTrashed()->count())->toBe(0)->and(MembershipModel::query()->count())->toBe(0);
});

it('refuses to delete or transfer a personal organization', function (string $action): void {
    $organization = app(CreateOrganizationAction::class)->execute(new CreateOrganizationData(name: 'Mine'), user(), personal: true);

    $action === 'delete'
        ? app(DeleteOrganizationAction::class)->execute($organization)
        : app(TransferOwnershipAction::class)->execute($organization, new TransferOwnershipData(user: user()->getRouteKey()));
})->with(['delete', 'transfer'])->throws(ValidationException::class);
