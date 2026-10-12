<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Roster\Support\Tables;
use RefactorCircus\Roster\Support\UserKey;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(Tables::name('profiles'), function (Blueprint $table): void {
            $table->ulid('id')->primary();

            // Typed by `roster.users.key_type`, set before migrating.
            UserKey::column($table, 'user_id');

            $table->string('display_name')->nullable();
            $table->string('avatar_url', 2048)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->string('locale', 16)->nullable();
            $table->text('bio')->nullable();
            $table->json('meta')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->string('status_reason', 1000)->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::name('profiles'));
    }
};
