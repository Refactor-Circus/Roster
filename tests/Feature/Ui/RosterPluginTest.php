<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavItem;
use RefactorCircus\Atrium\Domains\Plugins\Services\PluginRegistry;
use RefactorCircus\Atrium\Domains\Widgets\Data\WidgetDefinition;
use RefactorCircus\Atrium\Domains\Widgets\Services\WidgetRegistry;
use RefactorCircus\Roster\Atrium\Badges;
use RefactorCircus\Roster\Atrium\RosterPlugin;
use RefactorCircus\Roster\Domains\Invitation\Enums\InvitationStatus;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;

it('registers itself with atrium', function (): void {
    expect(app(PluginRegistry::class)->has('roster'))->toBeTrue();
});

it('contributes users and organizations navigation', function (): void {
    $labels = array_map(fn (NavItem $item): string => $item->label, app(RosterPlugin::class)->navigation());

    expect($labels)->toBe(['Users', 'Organizations', 'Roles', 'Permissions', 'Impersonations', 'Imports & exports', 'Audit log']);
});

it('registers its routes inside the atrium group', function (): void {
    expect(Route::has('atrium.roster.users.index'))->toBeTrue()
        ->and(route('atrium.roster.users.index'))->toContain('/atrium/roster/users');
});

it('offers its widgets without placing them', function (): void {
    $keys = array_map(fn (WidgetDefinition $definition): string => $definition->key, app(RosterPlugin::class)->widgets());

    expect($keys)->toBe(['roster.user-status', 'roster.organizations'])
        ->and(app(WidgetRegistry::class)->all())->toHaveKeys($keys);
});

it('maps every status to a colour, giving pending one of its own', function (): void {
    $statuses = [...UserStatus::cases(), ...InvitationStatus::cases(), ...TransferStatus::cases()];
    $pending = [UserStatus::Pending, InvitationStatus::Pending, TransferStatus::AwaitingConfirmation];

    foreach ($statuses as $status) {
        expect(Badges::forStatus($status))->toBeIn(['success', 'warning', 'danger', 'primary', 'info', 'neutral']);

        // Only pending is info.
        expect(Badges::forStatus($status) === 'info')->toBe(in_array($status, $pending, true));
    }
});

it('searches users and organizations as separate atrium sources', function (): void {
    user(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
    // A fixed owner, so a random name or email can't also match "ada".
    organization(user(['name' => 'Owen Owner', 'email' => 'owner@example.com']), ['name' => 'Adams & Co']);

    [$users, $organizations] = app(RosterPlugin::class)->search();

    expect($users->key)->toBe('roster-users')
        ->and($users->description)->not->toBeNull()
        ->and(array_map(fn ($result) => $result->title, $users->results('ada')))->toBe(['Ada Lovelace'])
        ->and(array_map(fn ($result) => $result->title, $organizations->results('ada')))->toBe(['Adams & Co']);
});

it('gives organizations their own share of the results', function (): void {
    config()->set('atrium.search.concurrency', 'sync');

    foreach (range(1, 8) as $i) {
        user(['name' => "Ada {$i}", 'email' => "ada{$i}@example.com"]);
    }
    organization(attributes: ['name' => 'Ada Industries']);

    $titles = collect($this->actingAs(user())->getJson(route('atrium.search', ['q' => 'ada']))->assertOk()->json('data'))->pluck('title');

    // Users fill their own five; the organization still shows.
    expect($titles->filter(fn (string $title): bool => str_starts_with($title, 'Ada ') && $title !== 'Ada Industries'))->toHaveCount(5)
        ->and($titles)->toContain('Ada Industries');
});

it('keeps its search sources working after crossing into another process', function (): void {
    user(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);

    // Atrium's process driver serializes sources into a child process.
    $sources = unserialize(serialize(app(RosterPlugin::class)->search()));

    expect(array_map(fn ($result) => $result->title, $sources[0]->results('ada')))->toBe(['Ada Lovelace']);
});
