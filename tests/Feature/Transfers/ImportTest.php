<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use RefactorCircus\Impex\Domains\Run\Events\RunFailed;
use RefactorCircus\Impex\Domains\Run\Models\RunModel;
use RefactorCircus\Impex\Testing\Flows;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Organization\Actions\SyncOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\SyncOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Transfer\Actions\CancelTransferAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\ConfirmImportAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Roster\Domains\Transfer\Data\StartImportData;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use Workbench\App\Models\User;

function startImport(string $type, string $csv, ?User $actor = null, ?string $organization = null): TransferModel
{
    return app(StartImportAction::class)->execute(new StartImportData(
        type: TransferType::from($type),
        organization: $organization,
        content: $csv,
    ), $actor ?? user());
}

beforeEach(function (): void {
    $this->owner = user(['email' => 'owner@acme.test']);
    $this->acme = organization($this->owner, ['name' => 'Acme', 'domains' => ['acme.test']]);
    TeamModel::factory()->create(['organization_id' => $this->acme->id, 'name' => 'Sales', 'slug' => 'sales']);
});

it('previews a members import without changing anything', function (): void {
    user(['email' => 'linked@acme.test']);

    $transfer = startImport('import_members', implode("\n", [
        'email,name,teams,role',
        'linked@acme.test,Linked,sales,',
        'new@acme.test,New Person,,',
        'friend@example.com,Friend,sales,',
        'not-an-email,Bad,,',
        'owner@acme.test,Owner,,',
    ]), $this->owner, 'acme');

    expect($transfer->status)->toBe(TransferStatus::AwaitingConfirmation)
        ->and($transfer->row_count)->toBe(5)
        ->and(collect($transfer->rows())->pluck('action')->all())->toBe(['link', 'create', 'invite', 'error', 'skip'])
        ->and($transfer->summary())->toMatchArray(['link' => 1, 'create' => 1, 'invite' => 1, 'error' => 1, 'skip' => 1])
        ->and(User::query()->where('email', 'new@acme.test')->exists())->toBeFalse()
        ->and(MembershipModel::query()->count())->toBe(1)
        ->and(InvitationModel::query()->count())->toBe(0);
});

it('applies a confirmed members import row by row, as the confirmer', function (): void {
    user(['email' => 'linked@acme.test']);

    $transfer = startImport('import_members', implode("\n", [
        'email,name,teams',
        'linked@acme.test,Linked,sales',
        'new@acme.test,New Person,',
        'friend@example.com,Friend,sales',
        'not-an-email,Bad,',
    ]), $this->owner, 'acme');

    $transfer = app(ConfirmImportAction::class)->execute($transfer, $this->owner)->refresh();

    $new = User::query()->where('email', 'new@acme.test')->firstOrFail();

    expect($transfer->status)->toBe(TransferStatus::Completed)
        ->and(collect($transfer->rows())->pluck('result')->all())->toBe(['link', 'create', 'invite', 'error'])
        ->and(MembershipModel::query()->where('organization_id', $this->acme->id)->where('user_id', $new->id)->exists())->toBeTrue()
        ->and(InvitationModel::query()->where('email', 'friend@example.com')->exists())->toBeTrue()
        ->and(TeamModel::query()->where('slug', 'sales')->firstOrFail()->seats()->count())->toBe(1);
});

it('rejects a file with a password column', function (): void {
    $transfer = startImport('import_users', "email,password\na@b.test,secret", $this->owner);

    expect($transfer->status)->toBe(TransferStatus::Failed)
        ->and($transfer->report['error'])->toContain('Passwords cannot be imported');
});

it('creates users and skips existing accounts', function (): void {
    $transfer = startImport('import_users', "email,name,display_name\nada@example.com,Ada Lovelace,Ada\nowner@acme.test,Owner,", $this->owner);
    app(ConfirmImportAction::class)->execute($transfer, $this->owner);

    expect(collect($transfer->refresh()->rows())->pluck('result')->all())->toBe(['create', 'skip'])
        ->and(User::query()->where('email', 'ada@example.com')->firstOrFail()->rosterProfile?->display_name)->toBe('Ada');
});

it('creates and fills teams from existing members', function (): void {
    $transfer = startImport('import_teams', "name,members\nSupport,owner@acme.test\nSales,stranger@example.com", $this->owner, 'acme');

    expect(collect($transfer->rows())->pluck('action')->all())->toBe(['create', 'error']);

    app(ConfirmImportAction::class)->execute($transfer, $this->owner);

    expect(TeamModel::query()->where('slug', 'support')->firstOrFail()->seats()->count())->toBe(1);
});

it('cancels an import awaiting confirmation with nothing applied', function (): void {
    $transfer = startImport('import_users', "email\nada@example.com", $this->owner);

    $transfer = app(CancelTransferAction::class)->execute($transfer);

    expect($transfer->status)->toBe(TransferStatus::Cancelled)
        ->and(User::query()->where('email', 'ada@example.com')->exists())->toBeFalse();
});

it('expires an import nobody confirms', function (): void {
    $transfer = startImport('import_users', "email\nada@example.com", $this->owner);

    $this->travel(25)->hours();
    $this->artisan('impex:tick')->assertSuccessful();

    expect($transfer->refresh()->status)->toBe(TransferStatus::Expired)
        ->and(User::query()->where('email', 'ada@example.com')->exists())->toBeFalse();
});

it('needs an organization for a members import', function (): void {
    startImport('import_members', "email\nada@example.com", $this->owner);
})->throws(ValidationException::class);

it('never applies a row twice when the engine redelivers steps', function (): void {
    $transfer = startImport('import_members', "email,teams\nnew@acme.test,sales\nfriend@example.com,", $this->owner, 'acme');
    app(ConfirmImportAction::class)->execute($transfer, $this->owner);

    Flows::redeliverSteps(RunModel::query()->findOrFail($transfer->impex_run_id));

    expect(User::query()->where('email', 'new@acme.test')->count())->toBe(1)
        ->and(InvitationModel::query()->count())->toBe(1);
});

it('marks the transfer failed when its run fails', function (): void {
    $transfer = startImport('import_users', "email\nada@example.com", $this->owner);

    event(new RunFailed((string) $transfer->impex_run_id));

    expect($transfer->refresh()->status)->toBe(TransferStatus::Failed);
});

it('imports organizations from an external system', function (): void {
    app(SyncOrganizationAction::class)->execute(new SyncOrganizationData(source: 'erp', externalId: 'C-1', name: 'Initech', accountNumber: 'A-1'));

    $transfer = startImport('import_organizations', implode("\n", [
        'source,external_id,name,account_number,domains,owner',
        'erp,C-1,Initech,A-1,,',
        'erp,C-1b,Initrode,A-2,initrode.test;initrode.example,owner@acme.test',
        'erp,C-1,Initech Corp,,,',
        'erp,C-3,,,,',
        'erp,C-4,Ghost,,,ghost@nowhere.test',
    ]), $this->owner);

    expect(collect($transfer->rows())->pluck('action')->all())->toBe(['skip', 'create', 'update', 'error', 'error'])
        ->and($transfer->rows()[4]['reasons'][0])->toBe('No user has the owner email ghost@nowhere.test.')
        ->and(OrganizationModel::query()->where('slug', 'initrode')->exists())->toBeFalse();

    app(ConfirmImportAction::class)->execute($transfer, $this->owner);

    $initrode = OrganizationModel::query()->where('slug', 'initrode')->firstOrFail();

    expect($initrode->isOwnedBy($this->owner))->toBeTrue()
        ->and($initrode->domains()->pluck('domain')->sort()->values()->all())->toBe(['initrode.example', 'initrode.test'])
        ->and(OrganizationModel::query()->where('slug', 'initech')->value('name'))->toBe('Initech Corp');
});
