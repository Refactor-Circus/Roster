<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Services;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RefactorCircus\Roster\Domains\Impersonation\Services\ImpersonationContext;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\User\Models\UserModel;

/**
 * How Roster's own surfaces - the JSON API, MCP tools and Atrium screens -
 * decide whether the caller may act.
 *
 * With `roster.authorization` on (the default), every call needs an
 * authenticated user allowed the Action's permission in the right scope.
 * Turning it off leaves the route middleware as the only check.
 *
 * A permission `{prefix}.{verb}` is answered by the Gate policy of the model
 * registered for its prefix in Resources (everything before the last dot), as
 * the camel-cased verb: `roster.users.manage-status` asks the UserModel
 * policy's `manageStatus()`, and a package's `acme.widgets.approve` asks its
 * WidgetModel policy's `approve()`. The bundled policies check the permission
 * itself, through `permits()`. Impersonation's blocked permissions are
 * refused before any policy is asked.
 */
final class Authorizer
{
    /**
     * Roster's core resources: the model whose policy answers each
     * `roster.{resource}.*` permission. PermissionServiceProvider registers
     * them with Resources; Roster's other domains register their own there.
     *
     * @var array<string, class-string<Model>>
     */
    public const array RESOURCES = [
        'users' => UserModel::class,
        'organizations' => OrganizationModel::class,
        'members' => MembershipModel::class,
        'teams' => TeamModel::class,
        'invitations' => InvitationModel::class,
        'roles' => RoleModel::class,
    ];

    /**
     * The policy answering each permission, worked out once per request and
     * again whenever a resource is registered.
     *
     * @var array<string, array{0: class-string<Model>, 1: string}|null>
     */
    private array $policies = [];

    /**
     * Answers already given this request, by actor, permission, scope and
     * subject; started over whenever Permissions forgets anything or a
     * resource is registered. A check that throws remembers nothing.
     *
     * @var array<string, bool>
     */
    private array $answers = [];

    private int $generation = 0;

    private int $resourcesGeneration = 0;

    /**
     * Organizations and teams found for records checked by parent, by class
     * and key, so records sharing one are looked up once per request.
     *
     * @var array<string, OrganizationModel|TeamModel|null>
     */
    private array $parents = [];

    public function __construct(
        private readonly Repository $config,
        private readonly Permissions $permissions,
        private readonly Gate $gate,
        private readonly Resources $resources,
    ) {}

    public function enabled(): bool
    {
        return $this->config->get('roster.authorization', true) !== false;
    }

    /**
     * Whether `$actor` may use a permission in a scope, as the policy for the
     * permission's resource decides. `$self` names the user the call is about,
     * for abilities everyone has over themselves.
     *
     * @throws InvalidArgumentException when a package's registered resource has no policy
     */
    public function check(?Model $actor, string $permission, OrganizationModel|TeamModel|null $scope = null, ?Model $self = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (app(ImpersonationContext::class)->isBlocked($permission)) {
            return false;
        }

        $this->startOverIfForgotten();

        // Unsaved records have no identity to remember an answer by.
        foreach ([$actor, $scope, $self] as $model) {
            if ($model !== null && $model->getKey() === null) {
                return $this->answer($actor, $permission, $scope, $self);
            }
        }

        $policy = $this->policyFor($permission);

        $key = implode('|', [
            // The policy class too, so registering another policy is seen at once.
            $policy === null ? '' : ($this->gate->policies()[$policy[0]] ?? ''),
            $actor === null ? '' : $actor->getMorphClass().':'.$actor->getKey(),
            $permission,
            $scope === null ? '' : $scope::class.':'.$scope->getKey(),
            $self === null ? '' : $self->getMorphClass().':'.$self->getKey(),
        ]);

        return $this->answers[$key] ??= $this->answer($actor, $permission, $scope, $self);
    }

    private function answer(?Model $actor, string $permission, OrganizationModel|TeamModel|null $scope, ?Model $self): bool
    {
        $policy = $this->policyFor($permission);

        if ($policy === null) {
            return $this->permits($actor, $permission, $scope, $self);
        }

        return $this->gate->forUser($actor)->allows($policy[1], [$policy[0], $scope, $self]);
    }

    /**
     * Whether `$actor` holds a permission in a scope, by Roster's roles alone:
     * what the bundled policies answer with.
     */
    public function permits(?Model $actor, string $permission, OrganizationModel|TeamModel|null $scope = null, ?Model $self = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if ($actor === null) {
            return false;
        }

        if (app(ImpersonationContext::class)->isBlocked($permission)) {
            return false;
        }

        if ($self !== null && $actor->is($self)) {
            return true;
        }

        return $this->permissions->allows($actor, $permission, $scope);
    }

    /**
     * The organizations `$actor` may use a permission in, for lists that show
     * what falls within them. Null means no organization limits them: they
     * hold it globally, or authorization is off.
     *
     * @return array<int, int|string>|null
     */
    public function organizationsWith(?Model $actor, string $permission): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        if ($actor === null || app(ImpersonationContext::class)->isBlocked($permission)) {
            return [];
        }

        return $this->permissions->organizationsWith($actor, $permission);
    }

    /**
     * The organization or team a record belongs to through its `$relation`
     * (`organization`, `team`): the loaded relation when there is one, else
     * found by the record's foreign key, once per request for every record
     * sharing it. A record without that relation (another package's model,
     * say) is looked up by its `organization_id` / `team_id` attribute the
     * same way. Eager-load the relation on lists checked record by record to
     * skip even that.
     */
    public function parentOf(Model $subject, string $relation): OrganizationModel|TeamModel|null
    {
        if ($subject->relationLoaded($relation)) {
            $parent = $subject->getRelation($relation);

            return $parent instanceof OrganizationModel || $parent instanceof TeamModel ? $parent : null;
        }

        if (! $subject->isRelation($relation)) {
            return $this->parentByAttribute($subject, $relation);
        }

        $related = $subject->{$relation}();

        if (! $related instanceof BelongsTo) {
            $parent = $subject->getRelationValue($relation);

            return $parent instanceof OrganizationModel || $parent instanceof TeamModel ? $parent : null;
        }

        $foreign = $subject->getAttribute($related->getForeignKeyName());

        if (! is_int($foreign) && ! is_string($foreign)) {
            return null;
        }

        $this->startOverIfForgotten();

        $key = $related->getRelated()::class.':'.$foreign;

        if (! array_key_exists($key, $this->parents)) {
            $found = $related->first();
            $this->parents[$key] = $found instanceof OrganizationModel || $found instanceof TeamModel ? $found : null;
        }

        $parent = $this->parents[$key];

        if ($parent !== null) {
            $subject->setRelation($relation, $parent);
        }

        return $parent;
    }

    /**
     * The organization or team named by a record's `{$relation}_id`
     * attribute, for records without the relation itself.
     */
    private function parentByAttribute(Model $subject, string $relation): OrganizationModel|TeamModel|null
    {
        $class = match ($relation) {
            'organization' => OrganizationModel::class,
            'team' => TeamModel::class,
            default => null,
        };

        $foreign = $class === null ? null : $subject->getAttribute($relation.'_id');

        if ($class === null || (! is_int($foreign) && ! is_string($foreign))) {
            return null;
        }

        $this->startOverIfForgotten();

        $key = $class.':'.$foreign;

        if (! array_key_exists($key, $this->parents)) {
            $found = $class::query()->find($foreign);
            $this->parents[$key] = $found instanceof OrganizationModel || $found instanceof TeamModel ? $found : null;
        }

        return $this->parents[$key];
    }

    /**
     * Drop remembered answers once Permissions has forgotten anything, and
     * the resolved policies too once a resource has been registered.
     */
    private function startOverIfForgotten(): void
    {
        if ($this->resourcesGeneration !== $this->resources->generation()) {
            $this->policies = [];
            $this->answers = [];
            $this->resourcesGeneration = $this->resources->generation();
        }

        if ($this->generation !== $this->permissions->generation()) {
            $this->answers = [];
            $this->parents = [];
            $this->generation = $this->permissions->generation();
        }
    }

    /**
     * The model and policy method that answer a permission, or null when no
     * policy is registered for its resource.
     *
     * @return array{0: class-string<Model>, 1: string}|null
     */
    private function policyFor(string $permission): ?array
    {
        if (array_key_exists($permission, $this->policies)) {
            return $this->policies[$permission];
        }

        return $this->policies[$permission] = $this->resolvePolicy($permission);
    }

    /**
     * The registered model for the permission's prefix (everything before
     * its last dot) and the policy method for its verb. Null when the prefix
     * is not registered, so no policy is asked; null too for one of Roster's
     * own resources whose policy the application removed, which is then
     * checked by Roster's permissions alone.
     *
     * @return array{0: class-string<Model>, 1: string}|null
     *
     * @throws InvalidArgumentException when a package's registered resource has no policy
     */
    private function resolvePolicy(string $permission): ?array
    {
        $dot = strrpos($permission, '.');

        if ($dot === false) {
            return null;
        }

        $prefix = substr($permission, 0, $dot);
        $verb = substr($permission, $dot + 1);
        $model = $this->resources->model($prefix);

        if ($model === null || $verb === '') {
            return null;
        }

        if ($this->gate->getPolicyFor($model) === null) {
            if ($this->resources->reserved($prefix)) {
                return null;
            }

            throw new InvalidArgumentException("The resource [{$prefix}] is registered for [{$model}], which has no Gate policy: register one with Gate::policy() to answer [{$permission}].");
        }

        return [$model, Str::camel($verb)];
    }
}
