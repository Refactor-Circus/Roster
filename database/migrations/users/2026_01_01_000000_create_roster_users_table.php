<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Roster\Support\Tables;

/*
 * Only for apps using RefactorCircus\Roster\Domains\User\Models\UserModel without a users table of their
 * own. Published with the `roster-users-migration` tag; never loaded
 * automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create(Tables::name('users'), function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            // Deleted users can be restored; see roster:purge-deleted.
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::name('users'));
    }
};
