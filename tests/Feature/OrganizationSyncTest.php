<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\LinkOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\ListOrganizationsAction;
use RefactorCircus\Roster\Domains\Organization\Actions\RemoveMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\SyncOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\SyncOrganizationsAction;
use RefactorCircus\Roster\Domains\Organization\Actions\TransferOwnershipAction;
use RefactorCircus\Roster\Domains\Organization\Actions\UnlinkOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Data\LinkOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Data\ListOrganizationsData;
use RefactorCircus\Roster\Domains\Organization\Data\OrganizationSyncResult;
use RefactorCircus\Roster\Domains\Organization\Data\SyncOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Data\SyncOrganizationsData;
use RefactorCircus\Roster\Domains\Organization\Data\TransferOwnershipData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationLinkModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

function sync(array $record): OrganizationSyncResult
{
    return app(SyncOrganizationAction::class)->execute(SyncOrganizationData::from($record));
}

it('creates an ownerless organization from an external record, then updates it', function (): void {
    $created = sync(['source' => 'erp', 'external_id' => 'C-100', 'name' => 'Initech', 'account_number' => 'A-42', 'domains' => ['initech.test']]);

    expect($created->outcome)->toBe('created')
        ->and($created->organization->owner_id)->toBeNull()
        ->and($created->organization->memberships()->count())->toBe(0)
        ->and($created->link->account_number)->toBe('A-42')
        ->and($created->link->synced_at)->not->toBeNull();

    $updated = sync(['source' => 'erp', 'external_id' => 'C-100', 'name' => 'Initech Corp']);

    expect($updated->outcome)->toBe('updated')
        ->and($updated->organization->id)->toBe($created->organization->id)
        ->and($updated->organization->name)->toBe('Initech Corp')
        // Fields left out are kept.
        ->and($updated->organization->domains->pluck('domain')->all())->toBe(['initech.test'])
        ->and($updated->link->account_number)->toBe('A-42');

    expect(sync(['source' => 'erp', 'external_id' => 'C-100', 'name' => 'Initech Corp', 'account_number' => 'A-42'])->outcome)->toBe('unchanged')
        ->and(sync(['source' => 'erp', 'external_id' => 'C-100', 'account_number' => 'A-43'])->outcome)->toBe('updated')
        ->and(OrganizationModel::query()->count())->toBe(1);
});

it('needs a name to create', function (): void {
    sync(['source' => 'erp', 'external_id' => 'C-1']);
})->throws(ValidationException::class, 'name');

it('rejects a domain another organization owns', function (): void {
    organization(attributes: ['name' => 'Acme', 'domains' => ['acme.test']]);

    sync(['source' => 'erp', 'external_id' => 'C-1', 'name' => 'Copycat', 'domains' => ['acme.test']]);
})->throws(ValidationException::class);

it('links an existing organization on its first sync, and one organization to several sources', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);

    expect(sync(['source' => 'erp', 'external_id' => 'C-7', 'organization' => 'acme'])->organization->id)->toBe($acme->id)
        ->and(sync(['source' => 'crm', 'external_id' => '0015g00000', 'organization' => 'acme'])->organization->id)->toBe($acme->id)
        ->and($acme->links()->pluck('source')->sort()->values()->all())->toBe(['crm', 'erp']);

    expect(fn () => sync(['source' => 'erp', 'external_id' => 'C-8', 'organization' => 'acme']))
        ->toThrow(ValidationException::class, 'already linked to a erp record');
});

it('applies the owner only when there is none', function (): void {
    $first = user();
    $second = user();

    $organization = sync(['source' => 'erp', 'external_id' => 'C-1', 'name' => 'Initech'])->organization;
    sync(['source' => 'erp', 'external_id' => 'C-1', 'owner' => $first->getRouteKey()]);

    expect($organization->refresh()->isOwnedBy($first))->toBeTrue()
        ->and($organization->membershipFor($first))->not->toBeNull();

    expect(sync(['source' => 'erp', 'external_id' => 'C-1', 'owner' => $second->getRouteKey()])->outcome)->toBe('unchanged')
        ->and($organization->refresh()->isOwnedBy($first))->toBeTrue();
});

it('lets an ownerless organization get an owner by transfer, and has no owner to protect', function (): void {
    $organization = sync(['source' => 'erp', 'external_id' => 'C-1', 'name' => 'Initech'])->organization;
    $member = user();
    app(AddMemberAction::class)->execute($organization, new AddMemberData(user: $member->getRouteKey()));

    app(RemoveMemberAction::class)->execute($organization, $member);
    app(AddMemberAction::class)->execute($organization, new AddMemberData(user: $member->getRouteKey()));
    app(TransferOwnershipAction::class)->execute($organization, new TransferOwnershipData(user: $member->getRouteKey()));

    expect($organization->refresh()->isOwnedBy($member))->toBeTrue();
});

it('still requires an owner for personal organizations', function (): void {
    app(CreateOrganizationAction::class)->execute(new CreateOrganizationData(name: 'Mine'), personal: true);
})->throws(ValidationException::class, 'personal organization needs an owner');

it('syncs a batch record by record', function (): void {
    organization(attributes: ['name' => 'Acme', 'domains' => ['acme.test']]);

    $results = app(SyncOrganizationsAction::class)->execute(new SyncOrganizationsData(
        records: [
            ['source' => 'erp', 'external_id' => 'C-1', 'name' => 'One'],
            ['source' => 'erp', 'external_id' => 'C-2', 'name' => 'Two', 'domains' => ['acme.test']],
            ['source' => 'erp', 'external_id' => 'C-1', 'name' => 'One'],
            ['external_id' => 'C-3'],
        ],
    ));

    expect(array_column($results, 'outcome'))->toBe(['created', 'error', 'unchanged', 'error'])
        ->and($results[1]['errors'])->toHaveKey('domains.0')
        ->and($results[3]['errors'])->toHaveKey('source')
        ->and(OrganizationLinkModel::query()->count())->toBe(1);
});

it('links, relinks and unlinks by hand', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);

    app(LinkOrganizationAction::class)->execute($acme, new LinkOrganizationData(source: 'erp', externalId: 'C-1', accountNumber: 'A-1'));
    app(LinkOrganizationAction::class)->execute($acme, new LinkOrganizationData(source: 'erp', externalId: 'C-2'));

    expect($acme->links()->sole()->only(['external_id', 'account_number']))->toBe(['external_id' => 'C-2', 'account_number' => 'A-1']);

    expect(fn () => app(LinkOrganizationAction::class)->execute($globex, new LinkOrganizationData(source: 'erp', externalId: 'C-2')))
        ->toThrow(ValidationException::class, 'Another organization');

    app(UnlinkOrganizationAction::class)->execute($acme, 'erp');

    expect($acme->links()->count())->toBe(0);
});

it('finds organizations by their external records', function (): void {
    sync(['source' => 'erp', 'external_id' => 'C-1', 'name' => 'One', 'account_number' => 'A-1']);
    sync(['source' => 'erp', 'external_id' => 'C-2', 'name' => 'Two', 'account_number' => 'A-2']);
    sync(['source' => 'crm', 'external_id' => 'C-1', 'organization' => 'two']);

    $find = fn (array $filters): array => app(ListOrganizationsAction::class)->execute(ListOrganizationsData::from($filters))->pluck('name')->all();

    expect($find(['account_number' => 'A-2']))->toBe(['Two'])
        ->and($find(['source' => 'erp', 'external_id' => 'C-1']))->toBe(['One'])
        ->and($find(['external_id' => 'C-1']))->toBe(['One', 'Two'])
        ->and($find(['source' => 'crm']))->toBe(['Two']);
});
