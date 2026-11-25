<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Roster\Domains\Organization\Http\Controllers\MemberController;
use RefactorCircus\Roster\Domains\Organization\Http\Controllers\OrganizationController;
use RefactorCircus\Roster\Domains\Organization\Http\Controllers\OrganizationDirectoryController;
use RefactorCircus\Roster\Domains\Organization\Http\Controllers\OrganizationSyncController;
use RefactorCircus\Roster\Domains\Organization\Http\Controllers\UserContextController;
use RefactorCircus\Roster\Http\Controllers\TrashController;

Route::put('users/{user}/context', [UserContextController::class, 'update'])->name('users.context.update');
Route::post('users/{user}/domain-join', [UserContextController::class, 'domainJoin'])->name('users.domain-join');

Route::get('organizations', [OrganizationController::class, 'index'])->name('organizations.index');
Route::post('organizations', [OrganizationController::class, 'store'])->name('organizations.store');
// Organizations from external systems (ERP, CRM, ...), by their id there.
Route::put('organizations/external/{source}/{externalId}', [OrganizationSyncController::class, 'sync'])->where('externalId', '.+')->name('organizations.sync');
Route::post('organizations/sync', [OrganizationSyncController::class, 'syncMany'])->name('organizations.sync-many');
Route::put('organizations/{organization}/links/{source}', [OrganizationSyncController::class, 'link'])->name('organizations.links.update');
Route::delete('organizations/{organization}/links/{source}', [OrganizationSyncController::class, 'unlink'])->name('organizations.links.destroy');

Route::get('organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
Route::patch('organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
Route::delete('organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');
Route::post('organizations/{organization}/restore', [TrashController::class, 'restoreOrganization'])->name('organizations.restore');
Route::delete('organizations/{organization}/purge', [TrashController::class, 'purgeOrganization'])->name('organizations.purge');
Route::post('organizations/{organization}/transfer', [OrganizationController::class, 'transfer'])->name('organizations.transfer');

Route::get('organizations/{organization}/members', [MemberController::class, 'index'])->name('organizations.members.index');
Route::post('organizations/{organization}/members', [MemberController::class, 'store'])->name('organizations.members.store');
Route::delete('organizations/{organization}/members/{user}', [MemberController::class, 'destroy'])->name('organizations.members.destroy');

// Outside `organizations/`, so neither segment can be mistaken for a slug.
Route::get('stats/organizations', [OrganizationDirectoryController::class, 'stats'])->name('stats.organizations');
Route::get('search/organizations', [OrganizationDirectoryController::class, 'search'])->name('search.organizations');
