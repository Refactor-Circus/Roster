# Standards for Auth extension seams

| Standard | File | Source | Why it applies |
| --- | --- | --- | --- |
| architecture/public-api | vendor/refactor-circus/standards/standards/architecture/public-api.md | refactor-circus/standards@dev-main | Five new public registration points; `ssoRequiredFor()` removed |
| architecture/dependencies | vendor/refactor-circus/standards/standards/architecture/dependencies.md | refactor-circus/standards@dev-main | Roster core stops referencing the SSO/SCIM domains |
| architecture/container-bindings | vendor/refactor-circus/standards/standards/architecture/container-bindings.md | refactor-circus/standards@dev-main | Registry singletons holding code-defined state |
| architecture/service-providers | vendor/refactor-circus/standards/standards/architecture/service-providers.md | refactor-circus/standards@dev-main | Core resources in `register()`, registrations in `boot()` |
| models/stored-identifiers | vendor/refactor-circus/standards/standards/models/stored-identifiers.md | refactor-circus/standards@dev-main | `memberships.source` values stay readable |
| http/authorization | vendor/refactor-circus/standards/standards/http/authorization.md | refactor-circus/standards@dev-main | Permission-to-policy resolution through registered resources |
| testing/test-layers | vendor/refactor-circus/standards/standards/testing/test-layers.md | refactor-circus/standards@dev-main | Existing suites unchanged; new unit tests for each seam |
| testing/arch-tests | vendor/refactor-circus/standards/standards/testing/arch-tests.md | refactor-circus/standards@dev-main | Arch test that core doesn't reference Sso/Scim |
