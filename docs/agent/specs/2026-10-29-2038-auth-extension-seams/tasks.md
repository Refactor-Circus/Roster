# Tasks: Auth extension seams

## Group 1: Authorization resources and permission catalogue
package: Roster
depends on: none
files: src/Domains/Permission/Services/Resources.php, src/Domains/Permission/Services/PermissionCatalogue.php, src/Domains/Permission/Services/Authorizer.php, src/Domains/Permission/PermissionServiceProvider.php, src/Domains/Permission/Console/Commands/SyncPermissionsCommand.php, src/Domains/Role/Support/BuiltInRoles.php, src/Domains/Sso/SsoServiceProvider.php, src/Domains/Scim/ScimServiceProvider.php, config/roster.php, tests/Unit/Permission/ResourcesTest.php, tests/Unit/Permission/PermissionCatalogueTest.php, tests/Feature/Permission/RegisteredResourcesTest.php, tests/Feature/Permission/PermissionSyncTest.php
criteria: 1, 2, 3

- [x] 1.1 Write tests for criteria 1 to 3: `Resources`: the prefix dataset, the `roster.*` reservation, the `@internal` path for Roster's own prefixes, duplicates, three-segment names; a fake `acme.widgets` policy reached through `Authorizer`, `ScreenAccess` and `@rosterCan`; a missing policy throws from `resolvePolicy()`, and the memo stores nothing for it; `parentOf()` with an `organization()` relation: pass, fail, other organization; late registration bumps `generation()`; the `PermissionCatalogue` registration rules; `sync()`, `roster:sync-permissions` (including the warning for an unregistered system row) and the fresh-migration cases; edited `admin` grants stay untouched
- [ ] 1.2 Implement: `Resources`: the core six registered in `PermissionServiceProvider::register()`; `PermissionCatalogue`; `Authorizer::resolvePolicy()` and memo keying on `Resources::generation()`; `BuiltInRoles::sync()` merging the catalogue; the warning in `SyncPermissionsCommand`; the SSO and SCIM providers registering `roster.sso` / `roster.scim` through the `@internal` methods, with their three permissions (admin grants) and their policies through Gate `policy()`; `config/roster.php` `policies` and `Authorizer::RESOURCES` reduced to core models
- [ ] 1.3 Run vendor/bin/pest; all green (the existing SSO/SCIM authorization tests unchanged)

## Group 2: Membership sources
package: Roster
depends on: 1
files: src/Domains/Organization/Services/MembershipSources.php, src/Domains/Organization/Enums/MembershipSource.php, src/Domains/Organization/Models/MembershipModel.php, src/Domains/Organization/Concerns/ManagesMemberships.php, src/Domains/Organization/Resources/MemberResource.php, src/Domains/Organization/OrganizationServiceProvider.php, src/Domains/Scim/ScimServiceProvider.php, src/Domains/Scim/Services/ScimUsers.php, tests/Unit/Organization/MembershipSourcesTest.php, tests/Feature/Organization/MembershipSourceTest.php, tests/Feature/ContextAndDomainTest.php, tests/Feature/InvitationActionsTest.php, tests/Feature/OrganizationActionsTest.php, CHANGELOG.md
criteria: 4

- [ ] 2.1 Write tests for criterion 4: `sourceEnum()` for a built-in value, `scim` and an unregistered value; registry duplicates and registering a built-in value; a stored `scim` reads back and shows "SCIM"; an unregistered stored value serializes and passes the `MemberResource` schema through `mcpTool()`; `join()` rejects an unknown string and accepts a registered one; the member list shows the label; a grep/arch test that nothing in `src/` compares `->source` to an enum case
- [ ] 2.2 Implement: `MembershipSources` (singleton, built-ins plus `register()`); the enum loses `Scim`; the string cast plus `sourceEnum()`; `MemberResource` uses the label, with a plain string schema; `join(MembershipSource|string)`; SCIM registers `scim`/"SCIM", and `ScimUsers` passes `'scim'`; existing `->source` assertions in the three listed tests change to `->value` or `sourceEnum()`, and nothing else; a CHANGELOG upgrade note
- [ ] 2.3 Run vendor/bin/pest; all green

## Group 3: SCIM audit context on Keystone AuditHooks
package: Roster
depends on: 2
files: src/Support/Audit.php, src/Domains/Scim/ScimServiceProvider.php, tests/Feature/Scim/ScimAuditContextTest.php
criteria: 5

- [ ] 3.1 Write tests (Keen installed, auditing on): the existing `scim_token` assertions still hold, or move here unchanged; a non-Roster event during a SCIM request carries no `scim_token`; a throwing `ScimContext` leaves the entry written without the key, reported (`Exceptions::fake()`); with `roster.audit.enabled` off, nothing is registered
- [ ] 3.2 Remove `scim_token` from `Support\Audit::context()`. The SCIM provider registers a self-filtering closure (`PackageRegistry::for($event)?->key === 'roster'`) that catches its own errors (try/catch, `report()`, return `[]`) on `AuditHooks::context()`, at the moment `Support\Audit::register()` does, and only while `Roster::auditing()`. No Keystone change.
- [ ] 3.3 Run vendor/bin/pest; all green

## Group 4: Sign-in requirements and provisioned status
package: Roster
depends on: 2
files: src/Contracts/SignInRequirement.php, src/Data/ExternalSignIn.php, src/Roster.php, src/Domains/Sso/Services/SsoSignInRequirement.php, src/Domains/Sso/SsoServiceProvider.php, src/Domains/Sso/Support/NotSsoEnforced.php, src/Domains/Sso/Actions/SsoLoginAction.php, src/Domains/Scim/Services/ScimUsers.php, tests/Feature/Sso/SsoWiringTest.php, tests/Unit/SignInRequirementsTest.php, tests/Unit/ProvisionedStatusTest.php
criteria: 6, 7

- [ ] 4.1 Write tests: the `SsoWiringTest` enforcement cases, rewritten against `externalSignInFor()`; a throwing requirement propagates and fails `NotSsoEnforced`; the super-admin short-circuit, without calling requirements; order, with the first answer winning; lazy class-string resolution, and duplicate registration asked once; `ssoRequiredFor` no longer exists; `provisionedStatus()`: pending, null, remapped, and invalid (reported); SSO and SCIM provisioning as pending stays unchanged
- [ ] 4.2 Implement: `SignInRequirement` and `ExternalSignIn`; `Roster::requireSignIn()` / `externalSignInFor()`, failing closed; `SsoSignInRequirement`, registered from `SsoServiceProvider`; `NotSsoEnforced` using `externalSignInFor()`; remove `ssoRequiredFor()`; `Roster::provisionedStatus()` with its reported fallback, used by `SsoLoginAction` and `ScimUsers`; list the `externalSignInFor()` call sites for the PR
- [ ] 4.3 Run vendor/bin/pest; all green

## Group 5: Proof, documentation and gate
package: Roster
depends on: 1, 2, 3, 4
files: tests/ArchTest.php, tests/Feature/DocsTest.php, tests/Feature/ExtendingRosterExamplesTest.php, README.md, resources/boost/skills/roster-development/SKILL.md, CHANGELOG.md
criteria: 8, 9, 10

- [ ] 5.1 Write tests: an arch test: the listed core classes use no `Domains\Sso` / `Domains\Scim` class, and nothing uses `MembershipSource::Scim`; a grep test for `roster\.sso`, `roster\.scim`, `'scim'` and `"scim"` in the listed files, and `sso`/`scim` keys in `config/roster.php` `policies`; a docs test for the "Extending Roster" section strings and the role-grant sentence, plus `class_exists` for every `Roster\...` name in its snippets; an examples test running the resource and catalogue registration snippets
- [ ] 5.2 Write the README and Boost "Extending Roster" section: every seam with an example; `AuditHooks` usage with the package check and try/catch; the `roster.*` reservation; the impersonation-blocked list staying core-only; `roster:sync-permissions` after late registration Finish the CHANGELOG notes (`ssoRequiredFor()` removal, `memberships.source` cast).
- [ ] 5.3 Run vendor/bin/pest, composer test and composer test:browser; all green. List every changed existing test, by file and test name, for the PR.
