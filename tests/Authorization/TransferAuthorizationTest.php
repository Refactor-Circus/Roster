<?php

declare(strict_types=1);

require_once __DIR__.'/fixtures.php';

use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Authorization\Contracts\Roles;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Transfer\Actions\ConfirmImportAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartExportAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Roster\Domains\Transfer\Data\StartExportData;
use RefactorCircus\Roster\Domains\Transfer\Data\StartImportData;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use Workbench\App\Models\User;

beforeEach(function (): void {
    $this->acme = organization(user(), ['name' => 'Acme', 'domains' => ['acme.test']]);
    $this->admin = user();
    app(AddMemberAction::class)->execute($this->acme, new AddMemberData(user: $this->admin->getRouteKey()));
    grant($this->admin, 'admin', $this->acme);
});

it('lets an organization admin import members there, but not elsewhere', function (): void {
    organization(user(), ['name' => 'Globex']);
    $this->actingAs($this->admin);

    $this->postJson(route('roster.imports.store'), ['type' => 'import_members', 'organization' => 'acme', 'content' => "email\nnew@acme.test"])
        ->assertStatus(202)
        ->assertJsonPath('data.status', 'awaiting_confirmation');

    $this->postJson(route('roster.imports.store'), ['type' => 'import_members', 'organization' => 'globex', 'content' => "email\nnew@acme.test"])
        ->assertForbidden();

    $this->postJson(route('roster.imports.store'), ['type' => 'import_users', 'content' => "email\nnew@acme.test"])
        ->assertForbidden();
});

it('turns rows outside the organization\'s domains into errors without invitation rights', function (): void {
    $manager = user();
    app(AddMemberAction::class)->execute($this->acme, new AddMemberData(user: $manager->getRouteKey()));
    grant($manager, roleGranting(['roster.members.manage'], 'organization'), $this->acme);

    $transfer = app(StartImportAction::class)->execute(new StartImportData(type: TransferType::ImportMembers, organization: 'acme', content: "email\nfriend@example.com"), $manager);

    expect($transfer->rows()[0]['action'])->toBe('error');
});

it('checks the confirmer\'s permission again at confirm', function (): void {
    $transfer = app(StartImportAction::class)->execute(new StartImportData(type: TransferType::ImportMembers, organization: 'acme', content: "email\nnew@acme.test"), $this->admin);

    app(Roles::class)->revoke($this->admin, $this->acme);

    expect(fn () => app(ConfirmImportAction::class)->execute($transfer, $this->admin))->toThrow(ValidationException::class);
    expect($transfer->refresh()->status)->toBe(TransferStatus::AwaitingConfirmation)
        ->and(User::query()->where('email', 'new@acme.test')->exists())->toBeFalse();
});

it('lets the requester follow and download their own export, and nobody else without permission', function (): void {
    $transfer = app(StartExportAction::class)->execute(new StartExportData(type: TransferType::ExportMembers, organization: 'acme'), $this->admin);

    $this->actingAs($this->admin)->getJson(route('roster.transfers.show', $transfer->id))
        ->assertOk()
        ->assertJsonPath('data.download_url', route('roster.transfers.download', $transfer->id));
    $this->actingAs($this->admin)->get(route('roster.transfers.download', $transfer->id))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $this->actingAs(user())->get(route('roster.transfers.download', $transfer->id))->assertForbidden();
});

it('hands MCP a short-lived signed download link', function (): void {
    $transfer = app(StartExportAction::class)->execute(new StartExportData(type: TransferType::ExportUsers), $this->admin);
    $this->actingAs($this->admin);

    $url = URL::temporarySignedRoute('roster.transfers.file', now()->addMinutes(15), ['transfer' => $transfer->id]);

    auth()->logout();
    $this->get($url)->assertOk();
    $this->get(route('roster.transfers.file', $transfer->id))->assertForbidden();

    $this->travel(16)->minutes();
    $this->get($url)->assertForbidden();
});

it('lists only your own transfers without a broader permission', function (): void {
    $mine = user();
    TransferModel::factory()->create(['requested_by' => $mine->getKey()]);
    TransferModel::factory()->create(['requested_by' => $this->admin->getKey()]);

    $this->actingAs($mine)->getJson(route('roster.transfers.index'))->assertOk()->assertJsonCount(1, 'data');
    $this->actingAs($mine)->getJson(route('roster.transfers.index', ['organization' => 'acme']))->assertForbidden();
});
