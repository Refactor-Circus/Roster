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
        Schema::create(Tables::name('impersonations'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            UserKey::column($table, 'impersonator_id')->index();
            UserKey::column($table, 'user_id')->index();
            $table->foreignUlid('organization_id')->nullable()->constrained(Tables::name('organizations'))->nullOnDelete();
            $table->string('reason', 500);
            // The one-time link's token, hashed; cleared once the link is used.
            $table->string('token_hash', 64)->nullable()->unique();
            $table->timestamp('link_expires_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('end_reason', 16)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::name('impersonations'));
    }
};
