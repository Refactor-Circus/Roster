<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Roster\Support\Tables;

/**
 * Indexes for the columns Roster filters, joins and sorts on that no index
 * leads with. MySQL indexes foreign keys by itself; PostgreSQL and SQLite do
 * not, so each index is only added where none exists yet.
 */
return new class extends Migration
{
    /**
     * @var array<string, array<int, string>>
     */
    private const array COLUMNS = [
        'organizations' => ['name'],
        'organization_domains' => ['organization_id'],
        'roles' => ['organization_id'],
        'profiles' => ['current_organization_id', 'current_team_id'],
        'impersonations' => ['organization_id', 'created_at'],
        'sso_connections' => ['organization_id'],
        'scim_tokens' => ['organization_id', 'sso_connection_id'],
        'scim_groups' => ['organization_id'],
        'transfers' => ['organization_id'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $key => $columns) {
            $name = Tables::name($key);

            foreach ($columns as $column) {
                if (! Schema::hasIndex($name, [$column])) {
                    Schema::table($name, fn (Blueprint $table) => $table->index($column, $name.'_'.$column.'_lookup_index'));
                }
            }
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $key => $columns) {
            $name = Tables::name($key);

            foreach ($columns as $column) {
                if (Schema::hasIndex($name, $name.'_'.$column.'_lookup_index')) {
                    Schema::table($name, fn (Blueprint $table) => $table->dropIndex($name.'_'.$column.'_lookup_index'));
                }
            }
        }
    }
};
