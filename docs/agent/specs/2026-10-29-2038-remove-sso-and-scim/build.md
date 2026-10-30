# Build: Remove SSO and SCIM

## Group 1

Remove SSO and SCIM code, surfaces, config and migrations (criteria 1 to 13).

### Files

- Deleted: `src/Domains/Sso/`, `src/Domains/Scim/`, `src/Mcp/Concerns/DescribesSsoConnection.php`, `src/Atrium/Http/Controllers/SsoUiController.php`, `src/Atrium/Http/Controllers/ScimUiController.php`, `resources/views/ui/sso/`, `database/factories/SsoConnectionFactory.php`, `database/migrations/2026_01_01_000006_create_roster_sso_tables.php`, `database/migrations/2026_01_01_000007_create_roster_scim_tables.php`.
- Added: `src/Support/Rules/NoExternalSignIn.php` (message key `external_sign_in_required`, the old `sso_required` text unchanged).
- Edited: `src/Domains/DomainServiceProvider.php`, `src/Mcp/RosterServer.php` (ten tools), `src/Atrium/RosterPlugin.php` (seven routes), `src/Atrium/Http/Controllers/OrganizationUiController.php` (tabs, permission and icon maps, tab data), `src/Atrium/Http/Controllers/UserUiController.php`, `src/RosterServiceProvider.php` (`scim` surface, comments), `src/Support/Tables.php`, `src/Support/Audit.php`, `src/Support/Policies/Policy.php`, `src/Support/ProvisionedStatus.php`, `src/Roster.php`, `src/Data/ExternalSignIn.php`, `src/Domains/Permission/PermissionServiceProvider.php`, `src/Mcp/Concerns/DescribesOrganization.php`, `src/Domains/Organization/` (purge action and tool, `CreateOrganizationData`, `OrganizationRules` comments), `resources/views/ui/organizations/show.blade.php`, `resources/views/ui/users/partials/overview.blade.php`, `resources/views/ui/users/show.blade.php`, `resources/import-templates/import_users.csv`, `resources/boost/skills/roster-development/SKILL.md` (SSO/SCIM wording only, so the criterion 12 scan over `resources/` holds), `config/roster.php`, `lang/en/roster.php`, migrations 000002, 000010, `2026_10_12_000001` and the email-index migration, `composer.json` (`suggest`, `require-dev`), and `composer.local.json` (git-ignored, mirrored by hand).
- Tests added: `tests/Feature/RemovedSsoScimTest.php`, `tests/Feature/LegacySsoScimUpgradeTest.php`, `tests/Feature/NoExternalSignInTest.php`, `tests/Fixtures/legacy_sso_scim_schema.php` (000006/000007 verbatim).
- Tests deleted: `tests/Feature/Sso/`, `tests/Feature/Scim/`, `tests/Feature/Http/SsoHttpTest.php`, `tests/Feature/Http/ScimTokensHttpTest.php`, `tests/Feature/Ui/SsoPagesTest.php`, `tests/Feature/Ui/ScimPagesTest.php`, `tests/Browser/SsoBrowserTest.php`, `tests/Browser/ScimBrowserTest.php`, `tests/Modes/SsoUnavailable/`, `tests/SsoUnavailableTestCase.php`.
- Tests edited (SSO/SCIM cases only): `tests/Pest.php`, `tests/ArchTest.php`, `tests/Authorization/` (fixtures, HTTP, MCP, policies, screen controls), `tests/Feature/ComposerTest.php`, `DocsTest.php` (one line), `IndexesTest.php`, `Organization/MembershipSourceTest.php`, `PerformanceTest.php`, `Permission/PermissionSyncTest.php`, `Permission/RegisteredResourcesTest.php`, `ProvisionedStatusTest.php`, `SoftDeleteTest.php`, `StoredIdentifiersTest.php`, `Transfers/TemplateTest.php`, `Ui/OrganizationTabBarTest.php`, `Ui/OrganizationRegisteredTabsTest.php`, `Ui/ScreenDeclarationsTest.php`, `Ui/SharedTabBarTest.php` (built-in list), `Ui/UserRegisteredTabsTest.php`, `UserApprovalTest.php`, `tests/Modes/Keen/KeenAuditTest.php`, `tests/Modes/RenamedTables/RenamedTablesTest.php`, `tests/Unit/Organization/MembershipSourcesTest.php`, `tests/Unit/Permission/PermissionCatalogueTest.php`.

### Tests

- Criterion 1: `RemovedSsoScimTest` (folders, provider list, `class_exists`/`trait_exists`, the `SsoConnectionModel` handover string, and an `arch()` rule that nothing under `RefactorCircus\Roster` uses either domain).
- Criterion 2: `RemovedSsoScimTest` (`Resources`, `PermissionCatalogue`, `Authorizer` for an organization admin, `MembershipSources`); `KeenAuditTest` (no `scim_token` with the impersonator context as positive control; an old entry with `scim_token` still served by the history route and tool).
- Criterion 3: `PermissionSyncTest` (unregistered report, rows and admin grants kept for every option the command defines).
- Criterion 4: `MembershipSourceTest` (raw `scim` on read, resource, API, MCP and members tab; filter, team write and import; `SCIM` once registered).
- Criteria 5 to 9 and 12: `RemovedSsoScimTest` (route-name dataset, route-table regex scan over names and URIs, `Surface` registry content, MCP tool names, the five organization tabs with members default, `?tab=sso`/`?tab=scim` fallbacks, controllers, overview view data, config and `Tables::DEFAULTS`, the criterion 12 regex scan and a regex self-check).
- Criterion 10: `LegacySsoScimUpgradeTest` (fresh migrate creates none of the five; upgraded install through migrate, status, rollback, re-migrate and refresh keeps the five tables and rows; `migrate:fresh` errors nowhere and creates no second set; stray `migrations` rows).
- Criterion 11: `ComposerTest` (`suggest`, `require-dev`, unchanged Atrium `conflict` `<0.1.0`) and the vendor-namespace scan in `RemovedSsoScimTest`.
- Criterion 13: `NoExternalSignInTest` through `Roster::requireSignIn()`.
- Run here: the four new files plus `MembershipSourceTest`, `PermissionSyncTest`, `KeenAuditTest` and `SharedTabBarTest` (122 tests, green); `vendor/bin/pint --test` on the new files and `phpstan analyse` (green). The full suite is left to the gate.

### Deviations and notes

- `LegacySsoScimUpgradeTest` runs on its own in-memory SQLite connection with foreign keys off. With them on, SQLite turns dropping `roster_organizations` during rollback into an implicit delete that cascades into the legacy rows (MySQL and PostgreSQL refuse the drop instead). The test checks what Roster's migrations do to the five tables. The fixture's `Tables::name()` calls resolve because the test names the five keys in `roster.tables`, as an upgraded install's published config does.
- `Authorizer::check()` sends an unregistered prefix to the raw permission check (`permits()`), not a flat deny. On a fresh install the admin holds no `roster.sso.view`, so the check denies, which is what the test pins. On an upgraded install without Guardian, the kept legacy grant would still pass a raw `roster.sso.view` check. Nothing in Roster asks for it any more.
- `tests/Unit` is not a PHPUnit test suite in `phpunit.xml.dist`, and `tests/Pest.php` binds no test case to it, so its files (including the edited `MembershipSourcesTest` and `PermissionCatalogueTest`, and the seams' `SignInRequirementsTest`) do not run in `composer test`. This predates this spec and is left unchanged.
- For group 2: criterion 12 scans `resources/`, including the Boost skill, so `SKILL.md` must keep its Guardian section free of the criterion 12 tokens (the current text says "Single sign-on and provisioning").

### Retry

- Re-checked the committed work against tasks 1.1 to 1.3; nothing was missing, so no code or test changed. The criterion 12 regex over `src/`, `config/`, `database/`, `lang/`, `resources/` and `workbench/` has no hits. The vendor-namespace scan hits only `RemovedSsoScimTest`'s own needles, which the test skips. The SSO/SCIM names left under `tests/` are negative assertions, the kept-permission fixture in `PermissionSyncTest` and the ArchTest pattern self-checks.
- `composer.local.json` is git-ignored and was mirrored by hand (Guardian suggested, no Socialite/JWT packages); it was not regenerated and `composer update` was not run. It still predates `adopt-atrium-screen-registry` (Atrium in `require`, no `conflict`), which is outside this spec's change; `ComposerTest` reads `composer.json`, where the `conflict` is exactly `<0.1.0`.

## Group 2

Upgrade docs and gate (criteria 14, 15, 16).

### Files

- `CHANGELOG.md`: new first `[Unreleased]` → `### Breaking` entry "SSO and SCIM moved to refactor-circus/guardian", opening with the bold fail-open warning, listing what was removed, the six numbered upgrade steps (step 2 bold; step 3 tells apps that installed Guardian early to run `php artisan guardian:upgrade-from-roster`), and the behaviour after upgrading. The seams-era entries are left as written; the new entry says it supersedes them.
- `README.md`: "Single sign-on" (with "Requiring SSO") and "SCIM provisioning" replaced by one "SSO and SCIM (refactor-circus/guardian)" section (bold warning first, short upgrade steps, Guardian link). "Extending Roster" no longer cites Roster's SSO/SCIM; it shows `NoExternalSignIn` and "a registered sign-in requirement". Also dropped: the SSO/SCIM feature bullets, API route rows, MCP tool rows, Atrium dashboard tab bullets, `sso`/`scim` from the built-in tab list (`?tab=sso`/`?tab=scim` listed with the old keys that open the default tab), the `scim_token` context and `scim` surface, and SSO/SCIM wording in policies, CSV import, deletes and approval.
- `resources/boost/skills/roster-development/SKILL.md` (per `package-generate-skill`): section 8 points at Guardian with the fail-open warning, the short upgrade path and `guardian:upgrade-from-roster`; "Extending Roster" says "a registered sign-in requirement"; one anti-pattern added. It stays free of the criterion 12 tokens, since `resources/` is scanned.
- `AGENTS.md` (`CLAUDE.md` is a symlink to it): domain list, `scim.php`, `connectionRules()`, the SSO/SCIM resource and audit-context wording, the `scim` surface and the `sso`/`scim` tabs removed; `Support\Rules\NoExternalSignIn` added to the cross-domain list.
- `docs/agent/product/roadmap.md`: one-line note that SSO/SCIM moved to Guardian.
- `docs/agent/standards/http/authorization.md`: "SSO/SCIM policies from their domain providers" became "package resources register their own policies", in the description and body. `vendor/bin/ringside sync` rerun (it wrote only git-ignored files).
- `tests/Feature/DocsTest.php`: tests below.

### Tests

- Criterion 14: "writes the Guardian upgrade entry in the Unreleased Breaking section of the CHANGELOG" (slice by bold title; bold warning as first line; the listed strings, removals and behaviour claims) and "gives the six upgrade steps in order, with step 2 bold" (numbers 1 to 6 in order, each step's key content, step 3's bold early-install clause with `php artisan guardian:upgrade-from-roster`).
- Criterion 15: "replaces the README single sign-on and SCIM sections with one Guardian section", "points the Boost skill at Guardian for single sign-on and provisioning", "names no removed SSO class outside the Guardian section" (README, Boost skill), "documents no removed SSO or SCIM surface outside the Guardian section of the README", "lists no sso or scim tab" (README dashboard and screen extensions, Boost skill screen extensions), "no longer cites Roster SSO or SCIM as users of the extension seams" (README, Boost skill; criterion 12 regex), "keeps the agent guides free of SSO and SCIM" (`CLAUDE.md`, `AGENTS.md`; criterion 12 regex and `scim.php`), plus a helper self-check. The seams-era "Extending Roster" tests are unchanged.
- Criterion 16: test counts from the build base `c8d55a3` to this branch, as `it()`/`test()`/`arch()` declarations: 692 before, 81 removed (all SSO/SCIM cases, three of them replaced by generic versions), 611 kept, 56 added, 667 after. The per-file, per-test list of deleted and changed tests is drafted for the PR description. The suite itself (`vendor/bin/pest`, `composer test`, `composer test:browser`) is left to the gate, as instructed; here only `pint --test` and `php -l` ran on `DocsTest.php` (green), and the new assertions were checked by grep against the edited docs.

### Deviations and notes

- `CLAUDE.md` is a symlink to `AGENTS.md`, so only `AGENTS.md` changed on disk; both read the same.
- The Boost skill cannot carry a heading named "SSO and SCIM (refactor-circus/guardian)", because criterion 12 scans `resources/`. Its Guardian section stays "Single sign-on and provisioning", and the DocsTest checks it under that heading.
