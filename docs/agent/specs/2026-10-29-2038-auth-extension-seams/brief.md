Risk: high

## What changed
- Permission: new `Resources` and `PermissionCatalogue` singleton registries. `Authorizer` resolves policies through `Resources`, and `BuiltInRoles::sync()` merges the catalogue. The SSO and SCIM resources, permissions and policies moved out of core into their own providers. `config/roster.php` `policies` lists only core models.
- Organization: new `MembershipSources` registry. `memberships.source` is now a string cast with `MembershipModel::sourceEnum()`. `MembershipSource::Scim` is removed. `join()` accepts `MembershipSource|string`.
- Sign-in: new `Contracts\SignInRequirement`, `Data\ExternalSignIn`, `Roster::requireSignIn()` and `externalSignInFor()`. `Roster::ssoRequiredFor()` is removed. SSO enforcement moved to `SsoSignInRequirement`.
- Provisioning: `Roster::provisionedStatus()` falls back to `active` and reports an invalid stored value. SSO login and SCIM use it.
- Audit: `scim_token` context moved out of `Support\Audit` into a self-filtering, self-catching `AuditHooks` closure in the SCIM provider. Roster adds no audit API.
- Docs: README "Extending Roster", Boost skill, CHANGELOG upgrade notes, and new arch, docs and example tests.

## Why
Guardian and other packages need to add authorization resources, permissions, membership sources and sign-in requirements without Roster naming SSO or SCIM in core. Decisions that shaped it:
- D-004: remove `ssoRequiredFor()`.
- D-005: string cast plus `sourceEnum()`.
- D-006, D-012: audit context lives on Keystone `AuditHooks`, with no Roster API.
- D-007: a throwing sign-in requirement fails closed.
- D-008, D-021: a missing policy throws for package resources only.
- D-010, D-017: `roster.*` is reserved, and Roster registers it through `@internal` methods.
- D-011: an invalid provisioned status falls back to `active` and reports.
- D-020, D-022: the policies config and the mechanical `->source` test rewrites.

## Look at
- src/Domains/Permission/Services/Authorizer.php:320 - `resolvePolicy()` now reads the registry. It throws for package resources with no policy, and the memo keys on both generations. This is the authorization path.
- src/Roster.php:123 - `externalSignInFor()` must fail closed, and super-admins must skip the requirements. `src/Support/SignInRequirements.php` holds the registry.
- src/Domains/Role/Support/BuiltInRoles.php:93 - `sync()` merges the catalogue. Grants apply only to newly created roles, and unregistered system permissions are reported but never deleted.
- src/Domains/Organization/Models/MembershipModel.php:79 - `sourceEnum()` and the string cast. Check the `->source` consumer audit.
- src/Domains/Scim/ScimServiceProvider.php - the audit closure's package check and try/catch, and the SCIM permission and policy registration.

## Tested
- 1, 2: `ResourcesTest`, `RegisteredResourcesTest`, `PoliciesTest`
- 3: `PermissionCatalogueTest`, `PermissionSyncTest`
- 4: `MembershipSourcesTest`, `MembershipSourceTest`
- 5: `ScimAuditContextTest`
- 6: `SignInRequirementsTest`, `SsoWiringTest`
- 7: `Unit/ProvisionedStatusTest`, `Feature/ProvisionedStatusTest`
- 8: `ArchTest`
- 9: `DocsTest`, `ExtendingRosterExamplesTest`
- 10: round 3 gate PASS (phpstan, pint, pest, ringside check). `composer test:browser` was not run by the gate.

Existing tests changed (each listed by file):
- `SsoWiringTest`: the `ssoRequiredFor` tests, rewritten against `externalSignInFor()`.
- `ContextAndDomainTest`, `InvitationActionsTest`, `OrganizationActionsTest`, `ScimUsersTest` (line 43): `->source` assertion rewrites.
- `ArchTest` and `PoliciesTest`: policy discovery changes (D-020).

## Risks
- Breaking public API for apps: `ssoRequiredFor()` is removed, `MembershipSource::Scim` is removed, and `=== MembershipSource::X` now silently evaluates false. The CHANGELOG notes cover these.
- The role grants and the `config/roster.php` `policies` change affect existing installs. Existing built-in roles never gain new permissions automatically, so apps grant them by hand or run `roster:sync-permissions`.
- A buggy sign-in requirement blocks every non-super-admin login. This is intended (D-007).
- `composer test:browser` was not verified by the gate.
- Open findings: none (nits: 0). Packages broken: none.
