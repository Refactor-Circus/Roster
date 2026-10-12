<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Services;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use RefactorCircus\Roster\Domains\Impersonation\Services\ImpersonationContext;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;
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
 * A permission `roster.{resource}.{verb}` is answered by the policy
 * registered for the resource's model (`roster.policies`), as the camel-cased
 * verb: `roster.users.manage-status` asks the UserModel policy's
 * `manageStatus()`. The bundled policies check the permission itself, through
 * `permits()`. Impersonation's blocked permissions are refused before any
 * policy is asked.
 */
final class Authorizer
{
    /**
     * The model whose policy answers each resource's permissions.
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
        'sso' => SsoConnectionModel::class,
        'scim' => ScimTokenModel::class,
    ];

    /**
     * The policy answering each permission, worked out once per request.
     *
     * @var array<string, array{0: class-string<Model>, 1: string}|null>
     */
    private array $policies = [];

    /**
     * Answers already given this request, by actor, permission, scope and
     * subject; started over whenever Permissions forgets anything.
     *
     * @var array<string, bool>
     */
    private array $answers = [];

    private int $generation = 0;

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
    ) {}

    public function enabled(): bool
    {
        return $this->config->get('roster.authorization', true) !== false;
    }

    /**
     * Whether `$actor` may use a permission in a scope, as the policy for the
     * permission's resource decides. `$self` names the user the call is about,
     * for abilities everyone has over themselves.
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
     * sharing it. Eager-load the relation on lists checked record by record
     * to skip even that.
     */
    public function parentOf(Model $subject, string $relation): OrganizationModel|TeamModel|null
    {
        if ($subject->relationLoaded($relation)) {
            $parent = $subject->getRelation($relation);

            return $parent instanceof OrganizationModel || $parent instanceof TeamModel ? $parent : null;
        }

        if (! $subject->isRelation($relation)) {
            return null;
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
     * Drop remembered answers once Permissions has forgotten anything.
     */
    private function startOverIfForgotten(): void
    {
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
     * @return array{0: class-string<Model>, 1: string}|null
     */
    private function resolvePolicy(string $permission): ?array
    {
        $parts = explode('.', $permission);

        if (count($parts) !== 3 || $parts[0] !== 'roster' || ! isset(self::RESOURCES[$parts[1]])) {
            return null;
        }

        $model = self::RESOURCES[$parts[1]];

        return $this->gate->getPolicyFor($model) === null ? null : [$model, Str::camel($parts[2])];
    }
}
