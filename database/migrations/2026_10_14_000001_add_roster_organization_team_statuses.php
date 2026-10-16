<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Roster\Support\Statuses;
use RefactorCircus\Roster\Support\Tables;

/**
 * A status on organizations and teams, from the enums `roster.statuses`
 * names. Existing rows take the enum's default status.
 */
return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'organizations' => Statuses::organizations()::default()->value,
            'teams' => Statuses::teams()::default()->value,
        ];

        foreach ($defaults as $key => $default) {
            Schema::table(Tables::name($key), function (Blueprint $table) use ($default): void {
                $table->string('status', 32)->default($default)->index();
                $table->string('status_reason', 1000)->nullable();
                $table->timestamp('status_changed_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['organizations', 'teams'] as $key) {
            Schema::table(Tables::name($key), function (Blueprint $table): void {
                $table->dropIndex(['status']);
                $table->dropColumn(['status', 'status_reason', 'status_changed_at']);
            });
        }
    }
};
