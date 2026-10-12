<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Support;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Grammar;

/**
 * Exact, case-insensitive string comparisons that stay index-friendly on
 * each database.
 *
 * PostgreSQL compares `lower(column) = ?`, which an expression index on
 * `lower(column)` serves (an ILIKE never uses a plain index). MySQL and
 * SQLite compare with a wildcard-free LIKE, which MySQL's case-insensitive
 * collations answer from the column's own index.
 */
final class CaseInsensitive
{
    /**
     * Constrain the builder to rows whose column equals the value, ignoring case.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    public static function where(Builder $builder, string $column, string $value): Builder
    {
        if ($builder->getModel()->getConnection()->getDriverName() === 'pgsql') {
            // `lower(column)`, wrapped by the query's own grammar.
            $lower = new readonly class($column) implements Expression
            {
                public function __construct(private string $column) {}

                public function getValue(Grammar $grammar): string
                {
                    return 'lower('.$grammar->wrap($this->column).')';
                }
            };

            return $builder->where($lower, mb_strtolower($value));
        }

        // Without LIKE wildcards in the value, a LIKE is an exact match.
        if (strpbrk($value, '%_') === false) {
            return $builder->whereLike($column, $value);
        }

        // `%` or `_` would widen the LIKE (`a_b@x` matching `axb@x`), so take
        // the LIKE's matches and keep only the exact ones.
        $model = $builder->getModel();

        $keys = $model->newQuery()
            ->whereLike($column, $value)
            ->get()
            ->filter(fn (Model $candidate): bool => strcasecmp((string) $candidate->getAttribute($column), $value) === 0)
            ->modelKeys();

        return $builder->whereIn($model->getQualifiedKeyName(), $keys);
    }
}
