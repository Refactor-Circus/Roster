<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\User\Data\SearchUsersData;
use RefactorCircus\Roster\Domains\User\Events\UsersSearchedActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UsersSearchingActionEvent;
use RefactorCircus\Roster\Support\PrefixSearch;
use RefactorCircus\Roster\Support\Users;

final class SearchUsersAction
{
    public function __construct(private readonly Users $users) {}

    /**
     * Up to `limit` users whose name or email column (`roster.users.columns`)
     * starts with `q`, oldest first, matched through `PrefixSearch` (`%`,
     * `_` and `\` in `q` match themselves).
     *
     * @return Collection<int, Model>
     */
    public function execute(SearchUsersData $data): Collection
    {
        UsersSearchingActionEvent::dispatch($data);

        $columns = array_values(array_filter([$this->users->column('name'), $this->users->column('email')]));

        $users = $columns === []
            ? $this->users->newModel()->newCollection()
            : PrefixSearch::apply($this->users->query(), $columns, $data->q)
                ->orderBy($this->users->newModel()->getQualifiedKeyName())
                ->limit($data->limit)
                ->get();

        UsersSearchedActionEvent::dispatch($data);

        return $users;
    }
}
