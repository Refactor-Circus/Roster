<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Keystone\Contracts\UnauditedActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Actions\CancelTransferAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\ConfirmImportAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartExportAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Roster\Domains\Transfer\Data\StartExportData;
use RefactorCircus\Roster\Domains\Transfer\Data\StartImportData;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Domains\Transfer\Events\ImportRowAppliedActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Events\ImportRowApplyingActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Events\TransferFinishedActionEvent;

/**
 * The Transfer domain's action events seen while running the callback, by
 * class name, in order.
 *
 * @return array<int, string>
 */
function transferEvents(Closure $callback): array
{
    $seen = [];

    Event::listen([ActionStartingEvent::class, ActionFinishedEvent::class], function (object $event) use (&$seen): void {
        if (str_contains($event::class, '\\Transfer\\Events\\')) {
            $seen[] = class_basename($event);
        }
    });

    $callback();

    return $seen;
}

beforeEach(function (): void {
    $this->owner = user(['email' => 'owner@acme.test']);
    $this->acme = organization($this->owner, ['name' => 'Acme', 'domains' => ['acme.test']]);
});

it('announces each step of an import', function (): void {
    $seen = transferEvents(function (): void {
        $transfer = app(StartImportAction::class)->execute(new StartImportData(type: TransferType::ImportMembers, organization: 'acme', content: "email,name\na@acme.test,A\nb@acme.test,B"), $this->owner);
        app(ConfirmImportAction::class)->execute($transfer, $this->owner);
    });

    expect($seen)->toBe([
        'TransferStartingActionEvent', 'ImportValidatingActionEvent', 'ImportValidatedActionEvent', 'TransferStartedActionEvent',
        'TransferConfirmingActionEvent',
        'ImportRowApplyingActionEvent', 'ImportRowAppliedActionEvent',
        'ImportRowApplyingActionEvent', 'ImportRowAppliedActionEvent',
        'TransferFinishingActionEvent', 'TransferFinishedActionEvent',
        'TransferConfirmedActionEvent',
    ]);
});

it('applies each row\'s events as the person who confirmed the import', function (): void {
    $actors = [];
    Event::listen([ImportRowApplyingActionEvent::class, ImportRowAppliedActionEvent::class], function (object $event) use (&$actors): void {
        $actors[] = Auth::id();
    });

    $transfer = app(StartImportAction::class)->execute(new StartImportData(type: TransferType::ImportMembers, organization: 'acme', content: "email,name\na@acme.test,A"), $this->owner);
    app(ConfirmImportAction::class)->execute($transfer, $this->owner);

    expect($actors)->toBe([$this->owner->getKey(), $this->owner->getKey()]);
});

it('keeps per-row steps out of the audit log', function (): void {
    expect(is_subclass_of(ImportRowApplyingActionEvent::class, UnauditedActionEvent::class))->toBeTrue()
        ->and(is_subclass_of(ImportRowAppliedActionEvent::class, UnauditedActionEvent::class))->toBeTrue()
        ->and(is_subclass_of(TransferFinishedActionEvent::class, UnauditedActionEvent::class))->toBeFalse();
});

it('announces each step of an export', function (): void {
    $seen = transferEvents(fn () => app(StartExportAction::class)->execute(new StartExportData(type: TransferType::ExportMembers, organization: 'acme'), $this->owner));

    expect($seen)->toBe([
        'TransferStartingActionEvent', 'ExportBuildingActionEvent', 'ExportBuiltActionEvent',
        'TransferFinishingActionEvent', 'TransferFinishedActionEvent', 'TransferStartedActionEvent',
    ]);
});

it('announces closing a cancelled import', function (): void {
    $transfer = app(StartImportAction::class)->execute(new StartImportData(type: TransferType::ImportMembers, organization: 'acme', content: "email,name\na@acme.test,A"), $this->owner);

    $seen = transferEvents(fn () => app(CancelTransferAction::class)->execute($transfer));

    expect($seen)->toContain('TransferFinishingActionEvent', 'TransferFinishedActionEvent');
});
