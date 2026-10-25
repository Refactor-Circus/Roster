<?php

declare(strict_types=1);

use RefactorCircus\Roster\Atrium\Http\Controllers\OrganizationUiController;
use RefactorCircus\Roster\Atrium\Tabs;

it('keys the organization screen\'s permission map by Tabs::ORGANIZATION_TABS', function (): void {
    $permissions = new ReflectionClassConstant(OrganizationUiController::class, 'TAB_PERMISSIONS');

    expect($permissions->isPrivate())->toBeTrue()
        ->and(array_keys((array) $permissions->getValue()))->toBe(Tabs::ORGANIZATION_TABS);
});
