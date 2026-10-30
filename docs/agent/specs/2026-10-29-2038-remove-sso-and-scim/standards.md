# Standards for Remove SSO and SCIM

| Standard | File | Source | Why it applies |
| --- | --- | --- | --- |
| architecture/public-api | vendor/refactor-circus/standards/standards/architecture/public-api.md | refactor-circus/standards@dev-main | Public classes, routes, MCP tools, config and permissions are removed: breaking, needs CHANGELOG and README upgrade notes |
| architecture/dependencies | vendor/refactor-circus/standards/standards/architecture/dependencies.md | refactor-circus/standards@dev-main | SSO vendor suggestions leave; `refactor-circus/guardian` is suggested, never required or named in code |
| architecture/configuration | vendor/refactor-circus/standards/standards/architecture/configuration.md | refactor-circus/standards@dev-main | `sso` / `scim` config sections and `roster.tables` keys are removed |
| architecture/domain-modules | vendor/refactor-circus/standards/standards/architecture/domain-modules.md | refactor-circus/standards@dev-main | Two domain modules and their providers leave `Domains\DomainServiceProvider` |
| architecture/service-providers | vendor/refactor-circus/standards/standards/architecture/service-providers.md | refactor-circus/standards@dev-main | `RosterServiceProvider` stops naming the SCIM surface; the domain providers' registrations go |
| models/migrations | vendor/refactor-circus/standards/standards/models/migrations.md | refactor-circus/standards@dev-main | Migrations 000006/000007 removed; index migrations stop naming SSO/SCIM tables; nothing drops data |
| models/models | vendor/refactor-circus/standards/standards/models/models.md | refactor-circus/standards@dev-main | Table keys and policies of the removed models |
| models/stored-identifiers | vendor/refactor-circus/standards/standards/models/stored-identifiers.md | refactor-circus/standards@dev-main | Stored `memberships.source = 'scim'` values stay readable |
| http/authorization | docs/agent/standards/http/authorization.md | local | `roster.sso` / `roster.scim` resources, permissions and policies leave; the standard's SSO/SCIM wording is updated |
| http/routes | vendor/refactor-circus/standards/standards/http/routes.md | refactor-circus/standards@dev-main | SSO/SCIM API, web, SCIM-server and Atrium routes are removed |
| roster/action-surfaces | docs/agent/standards/roster/action-surfaces.md | local | `PARITY_EXCEPTIONS` loses the two SSO entries |
| roster/mcp-tools | docs/agent/standards/roster/mcp-tools.md | local | Ten MCP tools leave `RosterServer` |
| roster/atrium-views | docs/agent/standards/roster/atrium-views.md | local | Organization and user views lose SSO/SCIM markup |
| testing/test-layers | vendor/refactor-circus/standards/standards/testing/test-layers.md | refactor-circus/standards@dev-main | SSO/SCIM suites are deleted; the rest stays green |
| testing/arch-tests | vendor/refactor-circus/standards/standards/testing/arch-tests.md | refactor-circus/standards@dev-main | Arch test that nothing in Roster references the removed domains |
