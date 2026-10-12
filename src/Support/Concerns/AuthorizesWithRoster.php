<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Support\Concerns;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * How every Roster request - HTTP or MCP - is authorized: it names the
 * permission its Action needs (`ability()`), the organization or team it
 * applies to (`scope()`) and the user it is about when everyone may do it to
 * themselves (`self()`), and Roster's Authorizer checks that against the
 * signed-in user. Lists asked without a scope may answer for just the
 * organizations the user holds the permission in.
 */
trait AuthorizesWithRoster
{
    public function authorize(): bool
    {
        if (app(Authorizer::class)->check($this->actor(), $this->ability(), $this->scope(), $this->self())) {
            return true;
        }

        return ($this->organizations() ?? []) !== [];
    }

    /**
     * Lists return true: asked without an organization of their own, a user
     * holding the permission only in some organizations sees what falls
     * within those, rather than being refused.
     */
    protected function acrossOrganizations(): bool
    {
        return false;
    }

    /**
     * The organizations a list is limited to, or null for no limit: the user
     * holds the permission globally, or the request names its own scope.
     *
     * @return array<int, int|string>|null
     */
    protected function organizations(): ?array
    {
        if (! $this->acrossOrganizations() || $this->scope() !== null) {
            return null;
        }

        $authorizer = app(Authorizer::class);

        if ($authorizer->check($this->actor(), $this->ability(), null, $this->self())) {
            return null;
        }

        return $authorizer->organizationsWith($this->actor(), $this->ability());
    }

    /**
     * The permission the request's Action needs.
     */
    abstract protected function ability(): string;

    /**
     * The organization or team the permission is checked in; null for global.
     */
    protected function scope(): OrganizationModel|TeamModel|null
    {
        return null;
    }

    /**
     * The user the request is about, when everyone may do it to themselves.
     */
    protected function self(): ?Model
    {
        return null;
    }

    /**
     * The authenticated user, passed to Actions that guard against acting on
     * yourself - also with `roster.authorization` off, unlike the shared base.
     */
    protected function actor(): ?Model
    {
        $user = $this->user();

        return $user instanceof Model ? $user : null;
    }
}
