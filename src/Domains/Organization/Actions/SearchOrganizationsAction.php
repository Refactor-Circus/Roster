<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Contracts\Database\Query\ConditionExpression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Grammar;
use RefactorCircus\Roster\Domains\Organization\Data\SearchOrganizationsData;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationsSearchedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationsSearchingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class SearchOrganizationsAction
{
    /**
     * The LIKE escape character. Not the backslash: MySQL and PostgreSQL
     * read a backslash in an `ESCAPE` literal differently, while `!` means
     * the same on every database.
     */
    private const string ESCAPE = '!';

    /**
     * Up to `limit` organizations whose name or slug starts with `q`, by
     * name; deleted ones are left out. Matching from the start of a field
     * lets the columns' indexes serve every keystroke of a typeahead, and
     * `%`, `_` and `\` in `q` match themselves rather than acting as
     * wildcards.
     *
     * @param  array<int, int|string>|null  $within  Only these organizations; null for no limit.
     * @return Collection<int, OrganizationModel>
     */
    public function execute(SearchOrganizationsData $data, ?array $within = null): Collection
    {
        OrganizationsSearchingActionEvent::dispatch($data);

        $pattern = $this->escape($data->q).'%';

        $query = OrganizationModel::query()->with(['links', 'owner']);

        if ($within !== null) {
            $query->whereIn('id', $within);
        }

        $organizations = $query
            ->where(function (Builder $builder) use ($pattern): void {
                foreach (['name', 'slug'] as $column) {
                    // One placeholder per condition, so bind the pattern once each.
                    $builder->orWhere($this->startsWith($column))->addBinding($pattern, 'where');
                }
            })
            ->orderBy('name')
            ->orderBy('id')
            ->limit($data->limit)
            ->get();

        OrganizationsSearchedActionEvent::dispatch($data);

        return $organizations;
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
