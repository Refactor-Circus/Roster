<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\User\Data\ListUsersData;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Domains\User\Events\UsersListedActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UsersListingActionEvent;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;
use RefactorCircus\Roster\Roster;
use RefactorCircus\Roster\Support\Users;

final class ListUsersAction
{
    public function __construct(private readonly Users $users) {}

    /**
     * @return LengthAwarePaginator<int, Model>
     */
    public function execute(ListUsersData $filters = new ListUsersData): LengthAwarePaginator
    {
        UsersListingActionEvent::dispatch($filters);

        $query = match ($filters->trashed) {
            'only' => $this->users->onlyTrashed(),
            'with' => $this->users->withTrashed(),
            default => $this->users->query(),
        };
        $query->with('rosterProfile');
        $key = $this->users->newModel()->getQualifiedKeyName();

        $search = $filters->search;

        if ($search !== null && $search !== '') {
            $columns = array_filter([$this->users->column('name'), $this->users->column('email')]);

            $query->where(function (Builder $builder) use ($columns, $search): void {
                foreach ($columns as $column) {
                    $builder->orWhere($column, 'like', '%'.$search.'%');
                }

                $builder->orWhereIn(
                    $builder->getModel()->getQualifiedKeyName(),
                    ProfileModel::query()->select('user_id')->where('display_name', 'like', '%'.$search.'%'),
                );
            });
        }

        $status = $filters->status;

        // A user without a profile row is active, so "active" means "no
        // profile with another status" rather than "a profile marked active".
        if ($status === UserStatus::Active) {
            $query->whereNotIn($key, ProfileModel::query()->select('user_id')->where('status', '!=', UserStatus::Active));
        } elseif ($status !== null) {
            $query->whereIn($key, ProfileModel::query()->select('user_id')->where('status', $status));
        }

        $users = $query->orderBy($key)->paginate($filters->perPage, ['*'], 'page', $filters->page);

        // Each user's current organization and team, for the whole page at once.
        app(Roster::class)->preload($users->getCollection());

        UsersListedActionEvent::dispatch($filters);

        return $users;
    }
}
