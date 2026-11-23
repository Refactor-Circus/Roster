<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Roster\Domains\User\Http\Controllers\UserController;
use RefactorCircus\Roster\Domains\User\Http\Controllers\UserDirectoryController;
use RefactorCircus\Roster\Domains\User\Http\Controllers\UserProfileController;
use RefactorCircus\Roster\Domains\User\Http\Controllers\UserStatusController;
use RefactorCircus\Roster\Http\Controllers\TrashController;

Route::get('users', [UserController::class, 'index'])->name('users.index');
Route::post('users', [UserController::class, 'store'])->name('users.store');
Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
Route::post('users/{user}/restore', [TrashController::class, 'restoreUser'])->name('users.restore');
Route::delete('users/{user}/purge', [TrashController::class, 'purgeUser'])->name('users.purge');

Route::patch('users/{user}/profile', [UserProfileController::class, 'update'])->name('users.profile.update');

Route::post('users/{user}/suspend', [UserStatusController::class, 'suspend'])->name('users.suspend');
Route::post('users/{user}/deactivate', [UserStatusController::class, 'deactivate'])->name('users.deactivate');
Route::post('users/{user}/reactivate', [UserStatusController::class, 'reactivate'])->name('users.reactivate');
Route::post('users/{user}/approve', [UserStatusController::class, 'approve'])->name('users.approve');
Route::post('users/{user}/reject', [UserStatusController::class, 'reject'])->name('users.reject');

// Outside `users/`, so neither segment can be mistaken for a user's route key.
Route::get('stats/users', [UserDirectoryController::class, 'stats'])->name('stats.users');
Route::get('search/users', [UserDirectoryController::class, 'search'])->name('search.users');
