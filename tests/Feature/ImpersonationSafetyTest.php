<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StartImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Data\StartImpersonationData;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\User\Actions\UpdateUserAction;
use RefactorCircus\Roster\Domains\User\Data\UpdateUserData;
use RefactorCircus\Roster\Tests\Fixtures\Policies\OpenPolicy;

beforeEach(function (): void {
    config()->set('roster.authorization', true);
    config()->set('roster.impersonation.blocked', ['roster.roles.*', 'billing.*', 'roster.users.delete']);
    Gate::define('billing.refund', fn (): bool => true);

    $this->admin = user(['name' => 'Admin']);
    grant($this->admin, 'super-admin');
    $this->ada = user(['name' => 'Ada', 'email' => 'ada@example.com']);
    grant($this->ada, 'super-admin');
    $this->actingAs($this->admin);

    $started = app(StartImpersonationAction::class)->execute($this->ada, new StartImpersonationData(reason: 'Ticket 42'), $this->admin);
    $this->get($started->url);
    $this->impersonation = $started->impersonation->refresh();
});

it('blocks listed permissions and app abilities, even for super-admins', function (): void {
    expect(Auth::id())->toBe($this->ada->getKey())
        ->and(app(Authorizer::class)->check($this->ada, 'roster.roles.assign'))->toBeFalse()
        ->and(app(Authorizer::class)->check($this->ada, 'roster.users.view'))->toBeTrue()
        ->and(Gate::allows('billing.refund'))->toBeFalse()
        ->and(Gate::allows('roster.users.delete'))->toBeFalse()
        ->and(Gate::allows('roster.users.view'))->toBeTrue();
});

it('locks the impersonated account email and password', function (): void {
    expect(fn () => app(UpdateUserAction::class)->execute($this->ada, new UpdateUserData(email: 'mine@evil.test')))
        ->toThrow(ValidationException::class)
        ->and(fn () => app(UpdateUserAction::class)->execute($this->ada, new UpdateUserData(password: 'new-password')))
        ->toThrow(ValidationException::class);

    // Other fields, and other accounts, are fine.
    expect(app(UpdateUserAction::class)->execute($this->ada, new UpdateUserData(name: 'Ada L'))->getAttribute('name'))->toBe('Ada L');
});

it('blocks listed permissions whatever the configured policy says', function (): void {
    Gate::policy(RoleModel::class, OpenPolicy::class);

    expect(app(Authorizer::class)->check($this->ada, 'roster.roles.assign'))->toBeFalse()
        ->and(app(Authorizer::class)->check($this->ada, 'roster.roles.view'))->toBeFalse()
        ->and(app(Authorizer::class)->check($this->ada, 'roster.users.view'))->toBeTrue();
});
