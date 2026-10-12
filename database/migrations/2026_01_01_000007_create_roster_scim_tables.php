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
        Schema::create(Tables::name('scim_tokens'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained(Tables::name('organizations'))->cascadeOnDelete();
            $table->string('name');
            $table->string('token_hash', 64)->unique();
            $table->foreignUlid('sso_connection_id')->nullable()->constrained(Tables::name('sso_connections'))->nullOnDelete();
            UserKey::column($table, 'created_by')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create(Tables::name('scim_users'), function (Blueprint $table): void {
            // Its id is the SCIM resource id the identity provider sees.
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained(Tables::name('organizations'))->cascadeOnDelete();
            UserKey::column($table, 'user_id');
            $table->string('external_id')->nullable();
            $table->boolean('created_by_scim')->default(false);
            $table->boolean('deactivated_by_scim')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
            $table->index(['organization_id', 'external_id']);
        });

        Schema::create(Tables::name('scim_groups'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained(Tables::name('organizations'))->cascadeOnDelete();
            $table->foreignUlid('team_id')->unique()->constrained(Tables::name('teams'))->cascadeOnDelete();
            $table->string('external_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::name('scim_groups'));
        Schema::dropIfExists(Tables::name('scim_users'));
        Schema::dropIfExists(Tables::name('scim_tokens'));
    }
};
