<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RefactorCircus\Roster\Support\Tables;
use RefactorCircus\Roster\Support\Users;

/*
 * Optional, for PostgreSQL apps: an expression index on lower(email) for
 * the users table, which serves Roster's case-insensitive email lookups
 * (sign-in by SSO, SCIM provisioning, invitations, imports) when an address
 * is stored with other casing. MySQL's case-insensitive collations already
 * use the plain email index, so this does nothing on other databases.
 * Published with the `roster-email-index-migration` tag; never loaded
 * automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection($this->getConnection())->getDriverName() !== 'pgsql') {
            return;
        }

        $table = Tables::name('users');
        $column = app(Users::class)->column('email') ?? 'email';
        $grammar = Schema::connection($this->getConnection())->getConnection()->getSchemaGrammar();

        DB::connection($this->getConnection())->statement(sprintf(
            'create index if not exists %s on %s (lower(%s))',
            $grammar->wrap($table.'_'.$column.'_lower_index'),
            $grammar->wrapTable($table),
            $grammar->wrap($column),
        ));
    }

    public function down(): void
    {
        if (DB::connection($this->getConnection())->getDriverName() !== 'pgsql') {
            return;
        }

        $table = Tables::name('users');
        $column = app(Users::class)->column('email') ?? 'email';

        DB::connection($this->getConnection())->statement(sprintf(
            'drop index if exists %s',
            Schema::connection($this->getConnection())->getConnection()->getSchemaGrammar()->wrap($table.'_'.$column.'_lower_index'),
        ));
    }
};
