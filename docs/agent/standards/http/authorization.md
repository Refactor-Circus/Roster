---
description: "Roster requests authorize through its Authorizer, which asks Keystone's Permissions contract: ability(), scope() and self() on AuthorizesWithRoster, never allows()/allowsEach(); {prefix}.{verb} goes to the Gate policy of the model registered for the prefix in Resources (core policies from roster.policies; package resources register their own policies), which answers with Authorizer::permits(); same check on HTTP and MCP; guest 401 vs 403"
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

Every Roster request, HTTP or MCP, authorizes in `authorize()` through Roster's `Authorizer` (`Support\Authorization\Authorizer`), never in the controller or tool. The request names the ability it needs; it never asks a policy itself. Roster owns no roles or permissions: the Authorizer asks Keystone's `Permissions` contract, whichever implementation is bound (a role store, or Keystone's Gate fallback).

## Policies

- Every model has a policy in its domain's `Policies/`, beside `Models/` ([[domain-modules]]), extending `RefactorCircus\Roster\Support\Policies\Policy` (on Keystone's `Policy`).
- Core models' policies are registered from the `roster.policies` config map (model class => policy class) by the package provider ([[service-providers]], [[configuration]]). A host application swaps one by pointing the model at another class there. Removing an entry checks that `roster.*` resource by Roster's permissions alone.
- Package resources register their own policies: a package that registers a resource through `Resources::resource()` registers its model's policy itself with Gate `policy()` in `boot()`. `roster.policies` lists core models only. An application entry in `roster.policies` for a package model still wins, because the package provider registers config policies after the domain providers boot.
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
  - `self()`: the user the request is about, when everyone may do it to themselves (switching their own context, listing their own transfers).

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

- A list or search asked without a scope of its own returns `true` from `acrossOrganizations()`. A user holding the permission only in some organizations then passes, and `persist()` hands `$this->organizations()` (null for no limit) to the action, so they see only what falls within those (`SearchOrganizationsRequest` does this for `GET search/organizations`).
- A request whose token is its only credential is the one kind that overrides `authorize()`: `InvitationResponseRequest` (accept and decline, as the signed-in invitee) and `LookUpInvitationRequest` (the public invitation lookup, logged in `docs/agent/standards-feedback.md` with what stops its tokens being guessed). A new one is a deviation to log the same way.
- Resolve route-bound or slug-bound models through the shared request helpers, so `scope()` and `persist()` use one resolution ([[request-persist]], [[mcp-model-binding]]).

## How the Authorizer answers

- Resources live in the `Support\Authorization\Resources` singleton (prefix => model). `Authorizer::RESOURCES` lists only the five core resources (`users`, `organizations`, `members`, `teams`, `invitations`), registered as `roster.{resource}` by `RosterServiceProvider::register()` through the `@internal` `rosterResource()`; other packages use `resource()` from their providers' `boot()`, which refuses `roster.*`. Abilities are declared in Keystone's `Abilities` registry (`define()`), Roster's by `RosterAbilities::define()` in `RosterServiceProvider::boot()`.
- `Authorizer::check($actor, $permission, $scope, $self)` refuses permissions blocked while impersonating, then sends `{prefix}.{verb}` to the Gate policy of the model registered for the prefix (everything before the last dot) as `[ModelClass, $scope, $self]`. An unregistered prefix has no policy and is denied. A `roster.*` resource with no policy answers with `permits()`; a package resource with no policy throws `InvalidArgumentException` naming it.
- `Authorizer::permits()` is the impersonation block, the self rule, then `Permissions::allows($actor, $ability, $scope)`; the organization owner rule is `OrganizationModel::grantsByItself()` (Keystone's `Scope`), outside `OrganizationModel::GLOBAL_ONLY_ABILITIES`. The Authorizer asks the bound contract every time; it remembers only the policy per ability (until `Resources::generation()` changes) and, per request, the organizations each actor owns and the parents of records.
- The same check guards both of Roster's surfaces: HTTP and MCP requests, through `AuthorizesWithRoster`. Roster renders no screens, so it has no Blade directive or view helper for permissions; a frontend learns what a user may do from the API (a 403) or from the role store, and the host's own Blade uses `@can`, which reaches the same check through the Gate.
- `roster.authorization` (default `true`) turns the check on. Set to `false`, every check passes and the route middleware is the only guard.

## Middleware and responses

- The route middleware runs first; the request's ability check is the per-operation decision on top of it.
- A refused guest gets 401, a refused signed-in user 403 (Keystone's request base).

## Enforcement

- `tests/ArchTest.php` asserts every model has a policy, in `roster.policies` or registered on the Gate by its domain provider (`Arch::modelsWithoutPolicies()`), and that `roster.policies` names only core models.
- Surface tests run with `roster.authorization` off; refusals are tested in `tests/Authorization` (`AuthorizationTestCase`, as shipped) ([[testbench-environment]]).
