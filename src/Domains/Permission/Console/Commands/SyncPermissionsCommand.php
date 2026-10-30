<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Console\Commands;

use Illuminate\Console\Command;
use RefactorCircus\Roster\Domains\Role\Support\BuiltInRoles;

final class SyncPermissionsCommand extends Command
{
    protected $signature = 'roster:sync-permissions';

    protected $description = "Add Roster's built-in and registered permissions and default roles that are missing";

    public function handle(): int
    {
        foreach (BuiltInRoles::sync() as $name) {
            $this->components->warn("System permission [{$name}] is no longer registered by Roster or any package. It was left in place; delete it once nothing uses it.");
        }

        $this->components->info('Roster permissions and default roles are up to date.');

        return self::SUCCESS;
    }
}
