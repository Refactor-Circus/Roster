<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Contracts\Database\Query\ConditionExpression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Grammar;
use RefactorCircus\Roster\Domains\User\Data\SearchUsersData;
use RefactorCircus\Roster\Domains\User\Events\UsersSearchedActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UsersSearchingActionEvent;
use RefactorCircus\Roster\Support\Users;

final class SearchUsersAction
{
    /**
     * The LIKE escape character. Not the backslash: MySQL and PostgreSQL
     * read a backslash in an `ESCAPE` literal differently, while `!` means
     * the same on every database.
     */
    private const string ESCAPE = '!';

    public function __construct(private readonly Users $users) {}

    /**
     * Up to `limit` users whose name or email column (`roster.users.columns`)
     * starts with `q`, oldest first. Matching from the start of a field lets
     * the columns' indexes serve every keystroke of a typeahead, and `%`,
     * `_` and `\` in `q` match themselves rather than acting as wildcards.
     *
     * @return Collection<int, Model>
     */
    public function execute(SearchUsersData $data): Collection
    {
        UsersSearchingActionEvent::dispatch($data);

        $columns = array_values(array_filter([$this->users->column('name'), $this->users->column('email')]));

        if ($columns === []) {
            $users = $this->users->newModel()->newCollection();
        } else {
            $pattern = $this->escape($data->q).'%';

            $users = $this->users->query()
                ->where(function (Builder $builder) use ($columns, $pattern): void {
                    foreach ($columns as $column) {
                        // One placeholder per condition, so bind the pattern once each.
                        $builder->orWhere($this->startsWith($column))->addBinding($pattern, 'where');
                    }
                })
                ->orderBy($this->users->newModel()->getQualifiedKeyName())
                ->limit($data->limit)
                ->get();
        }

        UsersSearchedActionEvent::dispatch($data);

        return $users;
    }

    /**
     * `column like ? escape '!'`, the column wrapped by the query's own
     * grammar. Laravel's `whereLike()` has no `escape` clause, and SQLite has
     * no default LIKE escape character.
     */
    private function startsWith(string $column): ConditionExpression
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

    /**
     * `q` with the LIKE wildcards and the escape character itself escaped,
     * so each matches literally.
     */
    private function escape(string $value): string
    {
        return str_replace([self::ESCAPE, '%', '_'], [self::ESCAPE.self::ESCAPE, self::ESCAPE.'%', self::ESCAPE.'_'], $value);
    }
}
