VERDICT: PASS

Compat review of ring-master/2026-10-29-2038-auth-extension-seams against main.

No findings at or above minor. Every public-API break is mandated by the spec and decisions D-004, D-005, D-006 and D-020 to D-022. Each one has a CHANGELOG entry under Breaking with an upgrade path:
- ssoRequiredFor() removed
- MembershipSource::Scim removed and memberships.source cast to string
- Authorizer::RESOURCES and BuiltInRoles::PERMISSIONS shrunk
- config policies entries for SSO and SCIM dropped (a published copy that still lists them keeps working)
- scim_token audit context moved onto AuditHooks

No database schema change. Stored membership values are unchanged. No new routes or events.

Nits, not blocking:
- Roster::__construct gained a required SignInRequirements parameter. Roster is container-resolved and scoped, so only code that calls new Roster(...) by hand breaks.
- BuiltInRoles::sync() now returns array instead of void. This is source-compatible.
