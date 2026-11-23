# Standards for Roster v2: API and MCP only

| Standard | File | Source | Why it applies |
| --- | --- | --- | --- |
| actions/actions-events | vendor/refactor-circus/standards/standards/actions/actions-events.md | refactor-circus/standards@dev-main | New read actions (LookUpInvitationAction, ShowUserStatsAction, SearchUsersAction, ShowOrganizationStatsAction, SearchOrganizationsAction) with their starting/finished event pairs |
| actions/domain-guards | vendor/refactor-circus/standards/standards/actions/domain-guards.md | refactor-circus/standards@dev-main | The lookup refuses non-pending invitations inside the action |
| architecture/configuration | vendor/refactor-circus/standards/standards/architecture/configuration.md | refactor-circus/standards@dev-main | `roster.atrium` removed; new `roster.invitations.lookup_middleware`, `roster.rate_limit.invitation_lookups_per_minute`, `roster.users.approvals.review_url`; banners rewritten |
| architecture/container-bindings | vendor/refactor-circus/standards/standards/architecture/container-bindings.md | refactor-circus/standards@dev-main | `InvitationPage` stays a bindable contract with the plain default |
| architecture/dependencies | vendor/refactor-circus/standards/standards/architecture/dependencies.md | refactor-circus/standards@dev-main | No Atrium reference left; Keystone the only suite `require`; pennantplus dropped |
| architecture/domain-modules | vendor/refactor-circus/standards/standards/architecture/domain-modules.md | refactor-circus/standards@dev-main | New endpoints live in the Role, Invitation, User and Organization domains; `src/Atrium` goes |
| architecture/naming | vendor/refactor-circus/standards/standards/architecture/naming.md | refactor-circus/standards@dev-main | Names of the new requests, actions, data, events, resources, tools |
| architecture/public-api | vendor/refactor-circus/standards/standards/architecture/public-api.md | refactor-circus/standards@dev-main | Removed public classes (`RosterPlugin`, `Atrium\*`, `RosterServiceProvider::$withoutAtrium`) are a breaking change |
| architecture/service-providers | vendor/refactor-circus/standards/standards/architecture/service-providers.md | refactor-circus/standards@dev-main | Seam registration removed; the public lookup route registered behind `routes.enabled === true` |
| data/data-objects | vendor/refactor-circus/standards/standards/data/data-objects.md | refactor-circus/standards@dev-main | `LookUpInvitationData`, `SearchUsersData`, `SearchOrganizationsData` |
| errors/exceptions | vendor/refactor-circus/standards/standards/errors/exceptions.md | refactor-circus/standards@dev-main | Lookup reuses `InvalidInvitationException::forToken()` |
| http/authorization | docs/agent/standards/http/authorization.md | local (overrides refactor-circus/standards@dev-main) | Every new authenticated endpoint names `ability()`/`scope()`; the public lookup is the token-only exception; Atrium/`@rosterCan` text removed from the standard |
| http/request-persist | vendor/refactor-circus/standards/standards/http/request-persist.md | refactor-circus/standards@dev-main | Each new endpoint is a Request whose `persist()` calls one action |
| http/resources | vendor/refactor-circus/standards/standards/http/resources.md | refactor-circus/standards@dev-main | `InvitationLookupResource`, `UserStatsResource`, `OrganizationStatsResource` (final, with `schema()`) |
| http/routes | vendor/refactor-circus/standards/standards/http/routes.md | refactor-circus/standards@dev-main | New named `roster.*` routes in the domains' `routes.php` (the lookup in `Invitation/public.php`) |
| models/stored-identifiers | vendor/refactor-circus/standards/standards/models/stored-identifiers.md | refactor-circus/standards@dev-main | Payloads use slugs/route keys, the lookup none at all |
| roster/action-surfaces | docs/agent/standards/roster/action-surfaces.md | local | Surfaces become HTTP + MCP; `PARITY_EXCEPTIONS` for the lookup and search; standard rewritten |
| roster/atrium-views | docs/agent/standards/roster/atrium-views.md | local | Deleted with the Atrium views |
| roster/mcp-http-parity | docs/agent/standards/roster/mcp-http-parity.md | local | Stats tools answer what the stats routes answer |
| roster/mcp-responses | docs/agent/standards/roster/mcp-responses.md | local | Stats tools respond through the shared resources |
| roster/mcp-schemas | docs/agent/standards/roster/mcp-schemas.md | local | Stats tools' output schemas |
| roster/mcp-tools | docs/agent/standards/roster/mcp-tools.md | local | `ShowUserStatsTool`, `ShowOrganizationStatsTool` shells over MCP requests |
| roster/optional-surfaces | docs/agent/standards/roster/optional-surfaces.md | local | Dashboard UI row (`roster.ui.enabled`) removed |
| roster/testbench-environment | docs/agent/standards/roster/testbench-environment.md | local | Provider order loses `AtriumServiceProvider`; Atrium/PennantPlus modes removed |
| testing/arch-tests | vendor/refactor-circus/standards/standards/testing/arch-tests.md | refactor-circus/standards@dev-main | New no-Atrium guard in `tests/ArchTest.php`; existing action/request rules cover the new code |
| testing/test-layers | vendor/refactor-circus/standards/standards/testing/test-layers.md | refactor-circus/standards@dev-main | API wiring tests per endpoint, behavior at the actions, parity gaps, OpenAPI/client staleness test |
