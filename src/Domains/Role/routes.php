<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Roster\Domains\Role\Http\Controllers\OrganizationRoleAssignmentController;
use RefactorCircus\Roster\Domains\Role\Http\Controllers\RoleController;
use RefactorCircus\Roster\Domains\Role\Http\Controllers\UserRoleController;

Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
Route::patch('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

Route::get('users/{user}/roles', [UserRoleController::class, 'index'])->name('users.roles.index');
Route::post('users/{user}/roles', [UserRoleController::class, 'store'])->name('users.roles.store');
Route::delete('users/{user}/roles/{assignment}', [UserRoleController::class, 'destroy'])->name('users.roles.destroy');
Route::get('users/{user}/permissions', [UserRoleController::class, 'permissions'])->name('users.permissions');

Route::get('organizations/{organization}/role-assignments', [OrganizationRoleAssignmentController::class, 'index'])->name('organizations.role-assignments.index');
