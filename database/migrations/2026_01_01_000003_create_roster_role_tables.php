<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Roster\Domains\Role\Support\BuiltInRoles;
use RefactorCircus\Roster\Support\Tables;
use RefactorCircus\Roster\Support\UserKey;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(Tables::name('permissions'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name')->unique();
            $table->string('description', 1000)->nullable();
            $table->boolean('system')->default(false);
            $table->timestamps();
        });

        Schema::create(Tables::name('roles'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug');
            $table->string('scope', 32);
            // Null for a role every organization can use; set for one
            // organization's own custom role.
            $table->foreignUlid('organization_id')->nullable()->constrained(Tables::name('organizations'))->cascadeOnDelete();
            $table->string('description', 1000)->nullable();
            $table->boolean('super')->default(false);
            $table->boolean('system')->default(false);
            $table->timestamps();

            $table->index(['scope', 'organization_id', 'slug']);
        });

        Schema::create(Tables::name('permission_role'), function (Blueprint $table): void {
            $table->foreignUlid('role_id')->constrained(Tables::name('roles'))->cascadeOnDelete();
            $table->foreignUlid('permission_id')->constrained(Tables::name('permissions'))->cascadeOnDelete();

            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create(Tables::name('role_assignments'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('role_id')->constrained(Tables::name('roles'))->cascadeOnDelete();
            UserKey::column($table, 'user_id');
            $table->foreignUlid('organization_id')->nullable()->index()->constrained(Tables::name('organizations'))->cascadeOnDelete();
            $table->foreignUlid('team_id')->nullable()->index()->constrained(Tables::name('teams'))->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'user_id', 'organization_id', 'team_id']);
            // A user's permissions in a scope: every Gate check reads this.
            $table->index(['user_id', 'organization_id', 'team_id']);
        });

        BuiltInRoles::sync();
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::name('role_assignments'));
        Schema::dropIfExists(Tables::name('permission_role'));
        Schema::dropIfExists(Tables::name('roles'));
        Schema::dropIfExists(Tables::name('permissions'));
    }
};
