<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Support;

use Illuminate\Contracts\Database\Query\ConditionExpression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Grammar;

/**
 * Typeahead matching: rows where any of the given columns starts with a
 * value, with `%`, `_` and `\` in the value matching themselves rather than
 * acting as wildcards. Matching from the start of a field lets the columns'
 * indexes serve every keystroke.
 */
final class PrefixSearch
{
    /**
     * The LIKE escape character. Not the backslash: MySQL and PostgreSQL
     * read a backslash in an `ESCAPE` literal differently, while `!` means
     * the same on every database.
     */
    private const string ESCAPE = '!';

    /**
     * Limits the query to rows where any of `columns` starts with `value`.
     *
     * @template TBuilder of Builder<covariant Model>
     *
     * @param  TBuilder  $query
     * @param  list<string>  $columns
     * @return TBuilder
     */
    public static function apply(Builder $query, array $columns, string $value): Builder
    {
        $pattern = self::pattern($value);

        return $query->where(function (Builder $builder) use ($columns, $pattern): void {
            foreach ($columns as $column) {
                // One placeholder per condition, so bind the pattern once each.
                $builder->orWhere(self::startsWith($column))->addBinding($pattern, 'where');
            }
        });
    }

    /**
     * `value` with the LIKE wildcards and the escape character itself
     * escaped, so each matches literally, followed by `%`.
     */
    public static function pattern(string $value): string
    {
        return str_replace(
            [self::ESCAPE, '%', '_'],
            [self::ESCAPE.self::ESCAPE, self::ESCAPE.'%', self::ESCAPE.'_'],
            $value,
        ).'%';
    }

    /**
     * `column like ? escape '!'`, the column wrapped by the query's own
     * grammar. Laravel's `whereLike()` has no `escape` clause, and SQLite has
     * no default LIKE escape character.
     */
    private static function startsWith(string $column): ConditionExpression
    {
        return new readonly class($column, self::ESCAPE) implements ConditionExpression
        {
            public function __construct(private string $column, private string $escape) {}

            public function getValue(Grammar $grammar): string
            {
                return $grammar->wrap($this->column)." like ? escape '".$this->escape."'";
            }
        };
    }
}
