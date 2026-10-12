<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Actions;

use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Domains\Transfer\Events\ImportTemplateShowingActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Events\ImportTemplateShownActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;

final class ShowImportTemplateAction
{
    public function __construct(private readonly Transfers $transfers) {}

    /**
     * The CSV template for an import type, as text. Works without refactor-circus/impex,
     * so files can be prepared before imports are set up.
     */
    public function execute(TransferType $type): string
    {
        ImportTemplateShowingActionEvent::dispatch($type->value);

        $template = $this->transfers->template($type);

        ImportTemplateShownActionEvent::dispatch($type->value);

        return $template;
    }
}
