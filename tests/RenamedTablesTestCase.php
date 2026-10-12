<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Tests;

use RefactorCircus\Roster\Support\Tables;

/**
 * Every Roster table renamed through `roster.tables` before migrating.
 */
abstract class RenamedTablesTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        foreach (array_keys(Tables::DEFAULTS) as $key) {
            if ($key !== 'users') {
                $app['config']->set('roster.tables.'.$key, 'crm_'.$key);
            }
        }
    }
}
