<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Support\Concerns;

use Closure;
use RefactorCircus\Roster\Contracts\Status;

/**
 * For Data whose status fields are typed by a contract: Keystone casts a
 * value to a concrete enum only, so the configured enum is resolved here,
 * before the fields are filled.
 */
trait PreparesStatuses
{
    /**
     * @param  array<array-key, mixed>  $validated
     * @param  array<string, Closure(string): Status>  $statuses  input key => resolver
     * @return array<array-key, mixed>
     */
    private static function prepareStatuses(array $validated, array $statuses): array
    {
        foreach ($statuses as $key => $resolve) {
            $value = $validated[$key] ?? null;

            if (is_string($value) && $value !== '') {
                $validated[$key] = $resolve($value);
            }
        }

        return $validated;
    }
}
