<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Roster\Support\Tables;
use RefactorCircus\Roster\Support\UserKey;

/*
 * The SSO and SCIM schema an upgraded install still holds: the two removed
 * migrations, `2026_01_01_000006_create_roster_sso_tables.php` (`sso`) and
 * `2026_01_01_000007_create_roster_scim_tables.php` (`scim`), copied verbatim
 * from the last Roster release that shipped them. Their table keys are no
 * longer in `Tables::DEFAULTS`, so the test names them in `roster.tables`, as
 * an upgraded install's published config does.
 */
return [
    'sso' => new class extends Migration
    {
        public function up(): void
        {
            Schema::create(Tables::name('sso_connections'), function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('organization_id')->constrained(Tables::name('organizations'))->cascadeOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('protocol', 8);
                // Encrypted JSON: issuer, client id and secret, or SAML IdP details.
                $table->text('config');
                $table->boolean('jit')->default(true);
                $table->boolean('enforced')->default(false);
                $table->boolean('enabled')->default(true);
                $table->timestamps();
            });

            Schema::create(Tables::name('sso_identities'), function (Blueprint $table): void {
                $table->ulid('id')->primary();
                $table->foreignUlid('connection_id')->constrained(Tables::name('sso_connections'))->cascadeOnDelete();
                UserKey::column($table, 'user_id')->index();
                $table->string('subject');
                $table->string('email')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->timestamps();

                $table->unique(['connection_id', 'subject']);
            });
        }

        public function down(): void
        {
            Schema::dropIfExists(Tables::name('sso_identities'));
            Schema::dropIfExists(Tables::name('sso_connections'));
        }
    },

    'scim' => new class extends Migration
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
    },
];
