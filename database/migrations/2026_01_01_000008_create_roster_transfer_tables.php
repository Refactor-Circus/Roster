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
        Schema::create(Tables::name('transfers'), function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('type', 32);
            $table->foreignUlid('organization_id')->nullable()->constrained(Tables::name('organizations'))->nullOnDelete();
            UserKey::column($table, 'requested_by')->index();
            $table->string('status', 32)->index();
            $table->string('impex_run_id')->nullable()->index();
            $table->string('input_path')->nullable();
            $table->string('output_path')->nullable();
            $table->json('filters')->nullable();
            // Summary counts and any error; per-row results live in
            // roster_transfer_rows.
            $table->json('report')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        // An import's rows: what the preview planned, then what applying did.
        Schema::create(Tables::name('transfer_rows'), function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('transfer_id')->constrained(Tables::name('transfers'))->cascadeOnDelete();
            $table->unsignedInteger('line');
            $table->json('values');
            $table->string('action', 16);
            $table->json('reasons');
            $table->string('result', 16)->nullable();
            $table->json('result_reasons')->nullable();

            $table->unique(['transfer_id', 'line']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Tables::name('transfer_rows'));
        Schema::dropIfExists(Tables::name('transfers'));
    }
};
