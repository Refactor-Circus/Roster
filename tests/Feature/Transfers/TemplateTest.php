<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use RefactorCircus\Roster\Domains\Transfer\Actions\ShowImportTemplateAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Roster\Domains\Transfer\Data\StartImportData;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Tools\ShowImportTemplateTool;
use RefactorCircus\Roster\RosterServiceProvider;
use Workbench\App\Models\User;

$imports = array_values(array_filter(TransferType::cases(), fn (TransferType $type): bool => $type->isImport()));

it('ships a template for every import type, with exactly its columns', function (TransferType $type): void {
    $header = str_getcsv(strtok(app(ShowImportTemplateAction::class)->execute($type), "\n"), escape: '');

    expect($header)->toBe([...$type->columns()['required'], ...$type->columns()['optional']]);
})->with($imports);

it('refuses an untouched template, which has no rows', function (TransferType $type): void {
    $owner = user();
    organization($owner, ['name' => 'Acme']);

    $transfer = app(StartImportAction::class)->execute(new StartImportData(
        type: $type,
        organization: $type->needsOrganization() ? 'acme' : null,
        content: app(ShowImportTemplateAction::class)->execute($type),
    ), $owner);

    expect($transfer->status)->toBe(TransferStatus::Failed)
        ->and($transfer->report['error'])->toContain('no rows to import');
})->with($imports);

it('skips commented rows in a real import', function (): void {
    $owner = user();

    $transfer = app(StartImportAction::class)->execute(new StartImportData(type: TransferType::ImportUsers, content: "email\n#skip@example.com\nada@example.com\n  # also skipped"), $owner);

    expect(collect($transfer->rows())->pluck('values.email')->all())->toBe(['ada@example.com'])
        ->and(User::query()->where('email', 'skip@example.com')->exists())->toBeFalse();
});

it('serves a published template instead of the default, and publishes them', function (): void {
    $path = resource_path('roster/import-templates/import_users.csv');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, "email,name\n");

    try {
        expect(app(ShowImportTemplateAction::class)->execute(TransferType::ImportUsers))->toBe("email,name\n");
    } finally {
        File::delete($path);
    }

    expect(RosterServiceProvider::pathsToPublish(RosterServiceProvider::class, 'roster-import-templates'))
        ->toContain(resource_path('roster/import-templates'));
});

it('downloads templates over http and mcp', function (): void {
    $this->actingAs(user());

    $this->get(route('roster.imports.templates.show', 'import_organizations'))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename="roster-import_organizations-template.csv"');
    $this->getJson(route('roster.imports.templates.show', 'export_users'))->assertJsonValidationErrors('type');

    mcpTool(ShowImportTemplateTool::class, ['type' => 'import_teams'])->assertOk()->assertSee('name,slug,members');
});
