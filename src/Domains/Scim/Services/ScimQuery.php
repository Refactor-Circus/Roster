<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Support\CaseInsensitive;

/**
 * SCIM filter comparisons, case-insensitive as SCIM strings are by default.
 */
final class ScimQuery
{
    /**
     * `eq` is exact and index-friendly (see CaseInsensitive), so `_` or `%`
     * in a value can never match a different account.
     * `co` and `sw` are searches; wildcards in them only widen the results.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    public static function compare(Builder $builder, string $column, string $operator, string $value): Builder
    {
        if ($operator === 'co') {
            return $builder->whereLike($column, '%'.$value.'%');
        }

        if ($operator === 'sw') {
            return $builder->whereLike($column, $value.'%');
        }

        return CaseInsensitive::where($builder, $column, $value);
    }
}
