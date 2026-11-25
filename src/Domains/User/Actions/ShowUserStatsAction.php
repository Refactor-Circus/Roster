<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Support\Facades\Cache;
use RefactorCircus\Roster\Domains\User\Events\UserStatsShowingActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UserStatsShownActionEvent;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;
use RefactorCircus\Roster\Support\Statuses;
use RefactorCircus\Roster\Support\Users;

final class ShowUserStatsAction
{
    public function __construct(private readonly Users $users) {}

    /**
     * How many users there are, and how many hold each configured status. A
     * user without a profile row counts as active. Counted at most once a
     * minute (Users::STATUS_COUNTS_CACHE): counting every user on every call
     * gets slow on large tables. Roster forgets the counts whenever one of
     * its User actions changes a user.
     *
     * @return array{total: int, by_status: array<string, int>}
     */
    public function execute(): array
    {
        UserStatsShowingActionEvent::dispatch();

        /** @var array{total: int, by_status: array<string, int>} $stats */
        $stats = Cache::remember(Users::STATUS_COUNTS_CACHE, 60, fn (): array => $this->count());

        UserStatsShownActionEvent::dispatch($stats);

        return $stats;
    }

    /**
     * @return array{total: int, by_status: array<string, int>}
     */
    private function count(): array
    {
        $total = $this->users->query()->count();

        $counts = ProfileModel::query()
            ->where('status', '!=', Statuses::users()::active())
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (mixed $count): int => is_numeric($count) ? (int) $count : 0)
            ->all();

        $others = array_sum($counts);

        $active = (string) Statuses::users()::active()->value;
        $byStatus = [$active => max(0, $total - $others)];

        foreach (Statuses::values(Statuses::users()) as $status) {
            if ($status !== $active) {
                $byStatus[$status] = $counts[$status] ?? 0;
            }
        }

        return ['total' => $total, 'by_status' => $byStatus];
    }
}
