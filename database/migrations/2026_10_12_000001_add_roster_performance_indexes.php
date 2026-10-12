<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Roster\Support\Tables;

/**
 * Indexes for the organization-scoped lists Roster pages through, which
 * filter on the organization and sort by creation time then key: one index
 * answers the filter and the order without a sort.
 */
return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private const array TABLES = ['memberships', 'role_assignments', 'invitations', 'scim_users', 'scim_groups'];

    private const array COLUMNS = ['organization_id', 'created_at', 'id'];

    public function up(): void
    {
        foreach (self::TABLES as $key) {
            $name = Tables::name($key);

            if (! Schema::hasIndex($name, self::COLUMNS)) {
                Schema::table($name, fn (Blueprint $table) => $table->index(self::COLUMNS, $name.'_organization_created_index'));
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $key) {
            $name = Tables::name($key);

            if (Schema::hasIndex($name, $name.'_organization_created_index')) {
                Schema::table($name, fn (Blueprint $table) => $table->dropIndex($name.'_organization_created_index'));
            }
        }
    }
};
