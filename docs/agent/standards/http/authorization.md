---
description: "Roster requests authorize through its permission Authorizer: ability(), scope() and self() on AuthorizesWithRoster, never allows()/allowsEach(); roster.{resource}.{verb} goes to the roster.policies policy of the resource's model, which answers with Authorizer::permits(); same check on HTTP, MCP and Atrium; guest 401 vs 403"
tags:
  - http
  - mcp
  - authorization
  - permissions
  - policies
  - roster
kind: architectural
overrides: refactor-circus/standards
reviewed_hash: 051a33575df9
---
# Authorization

Every Roster request, HTTP or MCP, authorizes in `authorize()` through Roster's permission `Authorizer`, never in the controller or tool. The request names the permission it needs; it never asks a policy itself.

## Policies

- Every model has a policy in its domain's `Policies/`, beside `Models/` ([[domain-modules]]), extending `RefactorCircus\Roster\Support\Policies\Policy` (on Keystone's `Policy`).
- Policies are registered from the `roster.policies` config map (model class => policy class) by the provider ([[service-providers]], [[configuration]]). A host application swaps one by pointing the model at another class there. Removing an entry checks that resource by Roster's permissions alone.
- Policy methods are named after the permission's verb, camel-cased (`roster.users.manage-status` => `manageStatus()`), and take `(?Model $user, ?Model $subject = null, ?Model $self = null)`. The bundled policies answer with `$this->permits($user, $permission, $subject, $self)`: the raw permission check, in the organization or team the subject is or belongs to.
- A child model's policy checks its parent resource's permission (a team seat asks `roster.teams.view` / `roster.teams.manage`); `permits()` finds the parent through `Authorizer::parentOf()`, team before organization. Never repeat the parent's rules.

```php
class TeamMemberPolicy extends Policy
{
    public function manage(?Model $user, ?TeamMemberModel $seat = null): bool
    {
        return $this->permits($user, 'roster.teams.manage', $seat);
    }
}
```

## Requests

- `Http\Request` and `Mcp\Request` use `Support\Concerns\AuthorizesWithRoster`, which supplies `authorize()`. Concrete requests do not override `authorize()` and never call `allows()`, `allowsEach()` or the Gate. They declare:
  - `ability()`: the permission the action needs, `roster.{resource}.{verb}`. Required.
  - `scope()`: the `OrganizationModel` or `TeamModel` the permission is checked in; `null` (the default) for global. Organization request bases already return `organization()`.
  - `self()`: the user the request is about, when everyone may do it to themselves (switching their own context, listing their own role assignments or transfers).

```php
final class UpdateTeamRequest extends TeamRequest
{
    protected function ability(): string
    {
        return 'roster.teams.manage';
    }

    protected function scope(): TeamModel
    {
        return $this->team();
    }
}
```

- A list asked without a scope of its own returns `true` from `acrossOrganizations()`. A user holding the permission only in some organizations then passes, and `persist()` hands `$this->organizations()` (null for no limit) to the action, so they see only what falls within those.
- Resolve route-bound or slug-bound models through the shared request helpers, so `scope()` and `persist()` use one resolution ([[request-persist]], [[mcp-model-binding]]).

## How the Authorizer answers

- `Authorizer::check($actor, $permission, $scope, $self)` refuses permissions blocked while impersonating, then sends `roster.{resource}.{verb}` to the policy registered for the resource's model (`Authorizer::RESOURCES`) as `[ModelClass, $scope, $self]`. With no policy registered, it answers with `permits()`.
- Answers are remembered for the request and dropped whenever `Permissions` forgets anything.
- The same check guards every surface: HTTP and MCP requests through `AuthorizesWithRoster`, Atrium screens through `ScreenAccess` and `@rosterCan('roster.…', $scope, $self)`. Hide a control the user may not use with `@rosterCan`.
- `roster.authorization` (default `true`) turns the check on. Set to `false`, every check passes and the route middleware is the only guard.

## Middleware and responses

- The route middleware runs first; the request's permission check is the per-operation decision on top of it.
- A refused guest gets 401, a refused signed-in user 403 (Keystone's request base).

## Enforcement

- `tests/ArchTest.php` asserts every model has a policy in `roster.policies` (`Arch::modelsWithoutPolicies()`).
- Surface tests run with `roster.authorization` off; refusals are tested in `tests/Authorization` (`AuthorizationTestCase`, as shipped) ([[testbench-environment]]).
