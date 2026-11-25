<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RefactorCircus\Keystone\Contracts\ReadActionEvent;
use RefactorCircus\Roster\Domains\User\Actions\SuspendUserAction;
use RefactorCircus\Roster\Domains\User\Data\SearchUsersData;
use RefactorCircus\Roster\Domains\User\Events\UserShowingActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UserShownActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UsersListedActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UsersListingActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UsersSearchedActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UsersSearchingActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UserStatsShowingActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UserStatsShownActionEvent;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;
use RefactorCircus\Roster\Support\Statuses;
use RefactorCircus\Roster\Support\Users;
use RefactorCircus\Roster\Tests\Fixtures\Statuses\MemberStatus;

// GET stats/users

it('counts every user per configured status, a user without a profile row as active', function (): void {
    $plain = user();
    ProfileModel::query()->where('user_id', $plain->getKey())->delete();
    user()->roster()->update(['status' => 'active']);
    user()->roster()->update(['status' => 'suspended']);
    user()->roster()->update(['status' => 'pending']);

    expect(ProfileModel::query()->where('user_id', $plain->getKey())->exists())->toBeFalse();

    $response = $this->getJson(route('roster.stats.users'))->assertOk()->assertExactJson([
        'data' => [
            'total' => 4,
            'by_status' => ['active' => 2, 'pending' => 1, 'suspended' => 1, 'deactivated' => 0],
        ],
    ]);

    expect(array_keys($response->json('data.by_status')))->toEqualCanonicalizing(Statuses::values(Statuses::users()));
});

it('counts the statuses of the app\'s own enum', function (): void {
    config(['roster.statuses.users' => MemberStatus::class]);

    user();
    user()->roster()->update(['status' => MemberStatus::Trial]);
    user()->roster()->update(['status' => MemberStatus::OnHold]);

    $this->getJson(route('roster.stats.users'))->assertOk()->assertExactJson([
        'data' => [
            'total' => 3,
            'by_status' => ['member' => 1, 'trial' => 1, 'applied' => 0, 'on_hold' => 1, 'closed' => 0],
        ],
    ]);
});

it('answers zeroes for an empty directory', function (): void {
    $this->getJson(route('roster.stats.users'))->assertOk()->assertExactJson([
        'data' => [
            'total' => 0,
            'by_status' => ['active' => 0, 'pending' => 0, 'suspended' => 0, 'deactivated' => 0],
        ],
    ]);
});

it('caches the counts for a minute under roster:user-status-counts', function (): void {
    user();

    expect(Users::STATUS_COUNTS_CACHE)->toBe('roster:user-status-counts');

    $this->getJson(route('roster.stats.users'))->assertJsonPath('data.total', 1);

    expect(Cache::has('roster:user-status-counts'))->toBeTrue();

    // Created outside Roster's actions: the cached counts still answer.
    user();
    $this->getJson(route('roster.stats.users'))->assertJsonPath('data.total', 1);

    $this->travel(61)->seconds();

    $this->getJson(route('roster.stats.users'))->assertJsonPath('data.total', 2);
});

it('forgets the cached counts when a User action finishes', function (): void {
    $ada = user();
    user();

    $this->getJson(route('roster.stats.users'))->assertJsonPath('data.by_status.suspended', 0);

    app(SuspendUserAction::class)->execute($ada);

    expect(Cache::has(Users::STATUS_COUNTS_CACHE))->toBeFalse();

    $this->getJson(route('roster.stats.users'))
        ->assertJsonPath('data.by_status.active', 1)
        ->assertJsonPath('data.by_status.suspended', 1);
});

it('keeps the cached counts while users are only read or searched', function (): void {
    $ada = user(['name' => 'Ada Lovelace']);

    $this->getJson(route('roster.stats.users'))->assertOk();
    $this->getJson(route('roster.users.index'))->assertOk();
    $this->getJson(route('roster.users.show', $ada))->assertOk();
    $this->getJson(route('roster.search.users', ['q' => 'Ada']))->assertOk();
    $this->getJson(route('roster.stats.users'))->assertOk();

    expect(Cache::has(Users::STATUS_COUNTS_CACHE))->toBeTrue();
});

it('marks every User read event as a read, so the cached counts survive it', function (string $event): void {
    expect(is_subclass_of($event, ReadActionEvent::class))->toBeTrue();
})->with([
    UsersListingActionEvent::class,
    UsersListedActionEvent::class,
    UserShowingActionEvent::class,
    UserShownActionEvent::class,
    UsersSearchingActionEvent::class,
    UsersSearchedActionEvent::class,
    UserStatsShowingActionEvent::class,
    UserStatsShownActionEvent::class,
]);

it('announces showing the stats with a starting and a finished event', function (): void {
    Event::fake([UserStatsShowingActionEvent::class, UserStatsShownActionEvent::class]);
    user();

    $this->getJson(route('roster.stats.users'))->assertOk();

    Event::assertDispatched(UserStatsShowingActionEvent::class);
    Event::assertDispatched(UserStatsShownActionEvent::class, fn (UserStatsShownActionEvent $event): bool => $event->stats['total'] === 1);
});

// GET search/users

it('finds users whose name or email starts with q, as user summaries', function (): void {
    $ada = user(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
    $grace = user(['name' => 'Grace Hopper', 'email' => 'gh@navy.test']);
    // "ada" inside a field, never at its start.
    user(['name' => 'Bob Adams', 'email' => 'bob@adams.test']);

    $this->getJson(route('roster.search.users', ['q' => 'ada']))->assertOk()->assertExactJson([
        'data' => [['id' => $ada->getRouteKey(), 'name' => 'Ada Lovelace', 'email' => 'ada@example.com']],
    ]);

    $this->getJson(route('roster.search.users', ['q' => 'gh@']))->assertOk()->assertExactJson([
        'data' => [['id' => $grace->getRouteKey(), 'name' => 'Grace Hopper', 'email' => 'gh@navy.test']],
    ]);

    $this->getJson(route('roster.search.users', ['q' => 'Lovelace']))->assertOk()->assertExactJson(['data' => []]);
});

it('searches the configured name and email columns only', function (): void {
    user(['name' => 'Grace Hopper', 'email' => 'gh@navy.test']);

    $this->getJson(route('roster.search.users', ['q' => 'Grace']))->assertJsonCount(1, 'data');

    config(['roster.users.columns.name' => '']);

    $this->getJson(route('roster.search.users', ['q' => 'Grace']))->assertExactJson(['data' => []]);
    $this->getJson(route('roster.search.users', ['q' => 'gh@']))->assertJsonCount(1, 'data');
});

it('leaves deleted users out', function (): void {
    user(['name' => 'Ada Lovelace'])->delete();

    $this->getJson(route('roster.search.users', ['q' => 'Ada']))->assertExactJson(['data' => []]);
});

it('answers five users unless asked for up to 25', function (): void {
    foreach (range(1, 7) as $i) {
        user(['name' => 'Sam '.$i]);
    }

    $this->getJson(route('roster.search.users', ['q' => 'Sam']))->assertJsonCount(5, 'data');
    $this->getJson(route('roster.search.users', ['q' => 'Sam', 'limit' => 2]))->assertJsonCount(2, 'data');
    $this->getJson(route('roster.search.users', ['q' => 'Sam', 'limit' => 25]))->assertJsonCount(7, 'data');
});

it('validates q and limit', function (array $query, string $field): void {
    $this->getJson(route('roster.search.users', $query))->assertUnprocessable()->assertJsonValidationErrors([$field]);
})->with([
    'q missing' => [[], 'q'],
    'q empty' => [['q' => ''], 'q'],
    'q over 100 characters' => [['q' => str_repeat('a', 101)], 'q'],
    'limit zero' => [['q' => 'a', 'limit' => 0], 'limit'],
    'limit over 25' => [['q' => 'a', 'limit' => 26], 'limit'],
    'limit not a number' => [['q' => 'a', 'limit' => 'many'], 'limit'],
]);

it('takes q of up to 100 characters', function (): void {
    $this->getJson(route('roster.search.users', ['q' => str_repeat('a', 100)]))->assertOk()->assertExactJson(['data' => []]);
});

it('matches %, _ and \\ in q literally', function (string $q, string $match, string $other): void {
    user(['name' => $match]);
    user(['name' => $other]);

    $this->getJson(route('roster.search.users', ['q' => $q]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', $match);
})->with([
    'percent' => ['100%', '100% Pure', '100 Proof'],
    'underscore' => ['a_', 'a_b', 'axb'],
    'backslash' => ['c\\', 'c\\d', 'cd'],
    'exclamation mark' => ['hey!', 'hey!you', 'heyyou'],
]);

it('announces a search with the input on its starting and finished events', function (): void {
    Event::fake([UsersSearchingActionEvent::class, UsersSearchedActionEvent::class]);

    $this->getJson(route('roster.search.users', ['q' => 'Ada', 'limit' => 3]))->assertOk();

    $carries = fn (SearchUsersData $data): bool => $data->q === 'Ada' && $data->limit === 3;

    Event::assertDispatched(UsersSearchingActionEvent::class, fn (UsersSearchingActionEvent $event): bool => $carries($event->data));
    Event::assertDispatched(UsersSearchedActionEvent::class, fn (UsersSearchedActionEvent $event): bool => $carries($event->data));
});

it('builds search input with a default limit of five', function (): void {
    $data = SearchUsersData::from(['q' => 'Ada']);

    expect($data->q)->toBe('Ada')
        ->and($data->limit)->toBe(5);
});
