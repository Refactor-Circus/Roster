VERDICT: CHANGES

- minor: Roster::provisionedStatus() holds the per-value status resolution and fallback/report logic (public-api: no per-value logic on the entry point). Move it to an instance (e.g. Support\ProvisionedStatus) and delegate from Roster.
- minor: docs/agent/standards/http/authorization.md and CLAUDE.md still say all policies come from roster.policies, Authorizer::RESOURCES includes sso/scim, and Support\Audit registers the SCIM token context. Update them to the new seams.
- minor: tests/Unit/ProvisionedStatusTest.php holds DB-backed SsoLoginAction and ScimUsers cases (test-layers: unit vs feature). Move them to tests/Feature.
