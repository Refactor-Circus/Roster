# Remove SSO and SCIM — Shaping Notes

## Scope

Delete SSO and SCIM from Roster now that Guardian (root spec `2026-10-29-2038-guardian-package`) owns them.

Remove:

- `src/Domains/Sso/` and `src/Domains/Scim/`, about 168 PHP files, and their entries in `Domains\DomainServiceProvider`
- `src/Atrium/Http/Controllers/SsoUiController.php`, `ScimUiController.php`, `resources/views/ui/sso/`, and the SSO/SCIM routes in `RosterPlugin.php:200-207`
- the `sso` and `scim` organization tabs, so `Tabs::ORGANIZATION_TABS` / the Atrium built-in list drops them. Guardian registers them instead.
- the SSO identities card on the user overview (`UserUiController:326`). Guardian registers it as a `roster.user` section.
- the SSO/SCIM MCP tools from `RosterServer`
- `Concerns/DescribesSsoConnection`
- the `sso` / `scim` config sections, `roster.tables.sso_*` / `scim_*`, and the SSO/SCIM policies
- the `socialite`, `saml2`, `php-jwt` and `microsoft-azure` suggestions from `composer.json`
- the SSO/SCIM tests
- the migrations `2026_01_01_000006_create_roster_sso_tables.php` and `000007_create_roster_scim_tables.php`. Guardian's upgrade migration renames the existing tables.

Also add:

- a CHANGELOG and README upgrade note: install `refactor-circus/guardian`, run its migrations (which rename `roster_sso_*` / `roster_scim_*` to `guardian_*`), and move `roster.sso.*` / `roster.scim.*` config to `guardian.*`.
- `refactor-circus/guardian` in `suggest`.

## Decisions

- D-001: the table data moves through Guardian's rename-upgrade migration, never dropped by Roster. Roster's removed migrations must not leave a down-migration that drops data. (User, 2026-10-29: "rename, ship upgrade".)
- D-002: Roster keeps `organizations.provisioned_status` and the seams from `auth-extension-seams`. Only SSO/SCIM-specific code leaves.
- D-003: open for spec review: whether `MembershipSource::Scim` values already stored stay readable, by keeping the value registered by Guardian through the seam.
- D-004: the `roster.sso.*` / `roster.scim.*` permissions become `guardian.sso.*` / `guardian.scim.*`, and Guardian's upgrade migration renames existing role grants. Roster's catalogue drops the old names. (User, 2026-10-29.)
- D-005: the organization tabs become `guardian.sso` / `guardian.scim`, registered by Guardian. Old `?tab=sso` and `?tab=scim` links fall back to the default tab.

## Context

- **Depends on:**
  - Roster `2026-10-29-2038-auth-extension-seams` (merged)
  - root `2026-10-29-2038-guardian-package` (merged)
  - Roster `2026-10-29-2038-adopt-atrium-screen-registry` (merged)
- **References:** see `references.md`.

## Standards

- architecture/public-api: removing public classes, config and routes is breaking and needs an upgrade note.
- architecture/dependencies: no SSO/SCIM vendors are suggested by Roster any more.
- architecture/configuration: config sections are removed.
- models/migrations: migration removal and safe upgrade ordering with Guardian's rename.
- testing/test-layers: the remaining suite plus `ArchTest` stay green.
