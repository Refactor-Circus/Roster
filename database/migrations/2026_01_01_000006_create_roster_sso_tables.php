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
};
