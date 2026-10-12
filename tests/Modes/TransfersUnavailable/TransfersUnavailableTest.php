<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Roster\Domains\Transfer\Data\StartImportData;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Domains\Transfer\Exceptions\TransfersUnavailableException;

it('explains which package to install', function (): void {
    app(StartImportAction::class)->execute(new StartImportData(type: TransferType::ImportUsers, content: "email\na@b.test"), user());
})->throws(TransfersUnavailableException::class, 'composer require refactor-circus/impex');

it('shows the setup note in atrium instead of the forms', function (): void {
    $this->actingAs(user())
        ->get(route('atrium.roster.transfers.index'))
        ->assertOk()
        ->assertSee('composer require refactor-circus/impex')
        ->assertDontSee('Upload and preview');
});
