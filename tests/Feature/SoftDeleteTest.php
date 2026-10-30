<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\DeleteOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\JoinOrganizationsByDomainAction;
use RefactorCircus\Roster\Domains\Organization\Actions\PurgeOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\RestoreOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\SwitchContextAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Data\SwitchContextData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\User\Actions\DeleteUserAction;
use RefactorCircus\Roster\Domains\User\Actions\PurgeUserAction;
use RefactorCircus\Roster\Roster;
use Workbench\App\Models\User;

it('stops a deleted user signing in, and keeps them out of member lists', function (): void {
    // As in an app, sign-in uses the same (soft-deleting) user model.
    config()->set('auth.providers.users.model', User::class);
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user(['email' => 'ada@example.com']);
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));

    app(DeleteUserAction::class)->execute($ada);

    expect(Auth::guard('web')->attempt(['email' => 'ada@example.com', 'password' => 'password']))->toBeFalse();
    $this->actingAs(user())->getJson(route('roster.organizations.members.index', 'acme'))->assertJsonCount(1, 'data');
});

it('turns a deleted organization off everywhere until it is restored', function (): void {
    $acme = organization(attributes: ['name' => 'Acme', 'domains' => ['acme.test']]);
    $acme->update(['auto_join' => true]);
    $member = user(['email' => 'member@example.com']);
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $member->getRouteKey()));
    app(SwitchContextAction::class)->execute($member, new SwitchContextData(organization: 'acme'));

    app(DeleteOrganizationAction::class)->execute($acme);
    app()->forgetScopedInstances();

    $this->actingAs(user())->getJson(route('roster.organizations.show', 'acme'))->assertNotFound();

    expect(app(Roster::class)->organization($member->fresh()))->toBeNull()
        ->and(app(JoinOrganizationsByDomainAction::class)->execute(user(['email' => 'new@acme.test', 'email_verified_at' => now()])))->toHaveCount(0);

    // Its slug and domains stay reserved.
    expect(fn () => validator(['name' => 'Acme', 'slug' => 'acme', 'owner' => user()->getRouteKey()], CreateOrganizationData::rules())->validate())->toThrow(ValidationException::class)
        ->and(fn () => validator(['name' => 'New', 'domains' => ['acme.test']], CreateOrganizationData::rules())->validate())->toThrow(ValidationException::class);

    app(RestoreOrganizationAction::class)->execute(OrganizationModel::withTrashed()->sole());
    app()->forgetScopedInstances();

    expect(app(Roster::class)->organization($member->fresh())?->slug)->toBe('acme');
});

it('only purges deleted records', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user();

    expect(fn () => app(PurgeOrganizationAction::class)->execute($acme))->toThrow(ValidationException::class, 'Delete it first')
        ->and(fn () => app(PurgeUserAction::class)->execute($ada))->toThrow(ValidationException::class, 'Delete it first');

    app(DeleteOrganizationAction::class)->execute($acme);
    app(PurgeOrganizationAction::class)->execute(OrganizationModel::withTrashed()->sole());

    expect(OrganizationModel::withTrashed()->count())->toBe(0);
});

it('purges records deleted longer ago than the retention period', function (): void {
    $old = organization(attributes: ['name' => 'Old']);
    $recent = organization(attributes: ['name' => 'Recent']);
    $gone = user();

    $this->travelTo(now()->subDays(40));
    app(DeleteOrganizationAction::class)->execute($old);
    app(DeleteUserAction::class)->execute($gone);
    $this->travelBack();
    app(DeleteOrganizationAction::class)->execute($recent);

    // Null retention keeps deleted records forever.
    $this->artisan('roster:purge-deleted')->expectsOutputToContain('forever')->assertSuccessful();
    expect(OrganizationModel::onlyTrashed()->count())->toBe(2);

    config()->set('roster.deletes.retention_days', 30);
    $this->artisan('roster:purge-deleted')->expectsOutputToContain('Purged 1 users')->assertSuccessful();

    expect(OrganizationModel::onlyTrashed()->pluck('name')->all())->toBe(['Recent'])
        ->and(User::withTrashed()->whereKey($gone->getKey())->exists())->toBeFalse();

    $this->artisan('roster:purge-deleted', ['--days' => 0])->assertSuccessful();
    expect(OrganizationModel::onlyTrashed()->count())->toBe(0);
});

it('restores and purges from atrium, and lists deleted records', function (): void {
    $this->actingAs($admin = user());
    $pat = user(['name' => 'Pat Gone', 'email' => 'pat@example.com']);
    $globex = organization(attributes: ['name' => 'Globex']);

    $this->delete(route('atrium.roster.users.destroy', $pat->getRouteKey()), ['confirm' => '1']);
    $this->delete(route('atrium.roster.organizations.destroy', 'globex'), ['confirm' => '1']);

    $this->get(route('atrium.roster.users.index', ['trashed' => 'only']))->assertOk()->assertSee('Pat Gone')->assertSee('data-testid="restore-user"', false);
    $this->get(route('atrium.roster.organizations.index', ['trashed' => 'only']))->assertOk()->assertSee('Globex')->assertSee('data-testid="restore-organization"', false);
    $this->get(route('atrium.roster.users.show', $pat->getRouteKey()))->assertOk()->assertSee('data-testid="deleted-banner"', false)->assertSee('Delete permanently');
    $this->get(route('atrium.roster.organizations.show', 'globex'))->assertOk()->assertSee('data-testid="deleted-banner"', false);

    $this->post(route('atrium.roster.users.restore', $pat->getRouteKey()))->assertRedirect();
    expect($pat->fresh()->trashed())->toBeFalse();

    $this->delete(route('atrium.roster.organizations.purge', 'globex'))->assertSessionHasErrors('confirm');
    $this->delete(route('atrium.roster.organizations.purge', 'globex'), ['confirm' => '1'])->assertRedirect();
    expect(OrganizationModel::withTrashed()->where('slug', 'globex')->exists())->toBeFalse();
});

it('purges every deleted organization past the first page of a thousand', function (): void {
    $deleted = now()->subDays(60);

    foreach (array_chunk(range(1, 1005), 500) as $chunk) {
        OrganizationModel::query()->insert(array_map(fn (int $i): array => [
            'id' => (string) Str::ulid(),
            'name' => 'Old '.$i,
            'slug' => 'old-'.$i,
            'personal' => false,
            'auto_join' => false,
            'provisioned_status' => 'active',
            'deleted_at' => $deleted,
            'created_at' => $deleted,
            'updated_at' => $deleted,
        ], $chunk));
    }

    $this->artisan('roster:purge-deleted', ['--days' => 30])->assertSuccessful();

    expect(OrganizationModel::onlyTrashed()->count())->toBe(0);
});
