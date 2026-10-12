<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Event;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\Team\Events\TeamCreatingEvent;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\User\Models\UserModel;

const ELOQUENT_HOOKS = ['retrieved', 'creating', 'created', 'updating', 'updated', 'saving', 'saved', 'deleting', 'deleted', 'replicating'];

const SOFT_DELETE_HOOKS = ['restoring', 'restored', 'trashed', 'forceDeleting', 'forceDeleted'];

/**
 * Every lifecycle event seen while running the callback, as `Model.hook`.
 *
 * @return array<int, string>
 */
function lifecycle(Closure $callback): array
{
    $seen = [];

    Event::listen(ModelLifecycleEvent::class, function (ModelLifecycleEvent $event) use (&$seen): void {
        $seen[] = class_basename($event->model()).'.'.$event->hook();
    });

    $callback();

    return array_values(array_unique($seen));
}

it('has an event class for every Eloquent hook of every model', function (): void {
    foreach ((array) glob(dirname(__DIR__, 2).'/src/Domains/*/Models/*Model.php') as $path) {
        $domain = basename(dirname($path, 2));
        $model = 'RefactorCircus\\Roster\\Domains\\'.$domain.'\\Models\\'.basename($path, '.php');
        $hooks = in_array(SoftDeletes::class, class_uses_recursive($model), true) ? [...ELOQUENT_HOOKS, ...SOFT_DELETE_HOOKS] : ELOQUENT_HOOKS;

        foreach ($hooks as $hook) {
            $event = 'RefactorCircus\\Roster\\Domains\\'.$domain.'\\Events\\'.str_replace('Model', '', class_basename($model)).ucfirst($hook).'Event';

            expect(class_exists($event))->toBeTrue($event.' is missing')
                ->and(is_subclass_of($event, ModelLifecycleEvent::class))->toBeTrue($event.' is not a ModelLifecycleEvent');
        }
    }
});

it('fires every lifecycle event of an organization', function (): void {
    $seen = lifecycle(function (): void {
        $acme = organization(attributes: ['name' => 'Acme']);
        $acme->update(['name' => 'Acme Inc']);
        OrganizationModel::query()->find($acme->id);
        $acme->replicate();
        $acme->delete();
        $acme->restore();
        $acme->forceDelete();
    });

    $hooks = array_values(array_map(fn (string $entry): string => substr($entry, strlen('OrganizationModel.')), array_filter($seen, fn (string $entry): bool => str_starts_with($entry, 'OrganizationModel.'))));

    expect($hooks)->toEqualCanonicalizing([...ELOQUENT_HOOKS, ...SOFT_DELETE_HOOKS]);
});

it('fires the lifecycle events of the records an action writes', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user();

    $seen = lifecycle(function () use ($acme, $ada): void {
        app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));
        $ops = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));
        app(AddTeamMemberAction::class)->execute($ops, new AddTeamMemberData(user: $ada->getRouteKey()));
    });

    expect($seen)->toContain('MembershipModel.creating', 'MembershipModel.created', 'TeamModel.saving', 'TeamModel.saved', 'TeamMemberModel.created');
});

it('fires the user events for the ready-made user model', function (): void {
    $seen = lifecycle(fn (): UserModel => UserModel::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret']));

    expect($seen)->toContain('UserModel.creating', 'UserModel.created');
});

it('lets a creating listener stop a record being written', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    Event::listen(TeamCreatingEvent::class, fn (): bool => false);

    $team = TeamModel::query()->make(['organization_id' => $acme->id, 'name' => 'Ops', 'slug' => 'ops']);

    expect($team->save())->toBeFalse()
        ->and(TeamModel::query()->count())->toBe(0);
});
