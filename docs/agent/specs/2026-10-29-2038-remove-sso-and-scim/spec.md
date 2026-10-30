# Remove SSO and SCIM

## Goal

Roster stops shipping SSO and SCIM, now that `refactor-circus/guardian` owns them. Roster is left with the seams from `auth-extension-seams` and `organizations.provisioned_status` (D-002). It no longer names an SSO or SCIM class, route, permission, table, config key or vendor library. Existing installs lose no data: Roster drops nothing, and Guardian's upgrade migration renames the tables and role grants (D-001, D-004). Apps get a clear upgrade path in the CHANGELOG and README, including `guardian:upgrade-from-roster` for apps that installed Guardian early (D-010). Without Guardian, SSO-enforced organizations accept password sign-in; this fail-open is accepted and documented prominently (D-017).

## Acceptance criteria

### Code and registrations

1. **The domains are gone.**
   - `src/Domains/Sso/` and `src/Domains/Scim/` do not exist. `Domains\DomainServiceProvider::$providers` lists neither `SsoServiceProvider` nor `ScimServiceProvider`.
   - `database/factories/SsoConnectionFactory.php` and `src/Mcp/Concerns/DescribesSsoConnection.php` are deleted.
   - Proof: a new arch test asserts that no class under `RefactorCircus\Roster` uses `RefactorCircus\Roster\Domains\Sso` or `RefactorCircus\Roster\Domains\Scim`. A feature test asserts that `is_dir()` is false for both folders, and that `class_exists()` is false for `Domains\Sso\SsoServiceProvider`, `Domains\Scim\ScimServiceProvider` and `Mcp\Concerns\DescribesSsoConnection` (`trait_exists` for the trait).
   - **Guardian handover contract (D-010).** Guardian's runtime handover gate (`Upgrade\Handover::complete()`) is true once `class_exists('RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel')` is false. A feature test asserts exactly that string is not a loadable class, so the gate opens with this release.

2. **The internal `roster.sso` / `roster.scim` registrations are gone.** They become Guardian's `guardian.*` (D-004).
   - `app(Resources::class)->model('roster.sso')` and `->model('roster.scim')` answer `null`.
   - `app(PermissionCatalogue::class)->all()` has no name starting with `roster.sso.` or `roster.scim.`.
   - `Authorizer::check()` for `roster.sso.view` denies an organization `admin` who is not a super-admin, because an unregistered prefix resolves no policy and is denied.
   - `app(MembershipSources::class)->has('scim')` is false.
   - With Keen installed and `roster.audit.enabled` on, an entry written for a Roster action carries no `scim_token` context key, and carries its other context keys (for example the impersonator context, with an impersonation active) on the same entry as a positive control. Old Keen entries that already hold a `scim_token` context key are left as written. Keen's history viewer ignoring a key without a registered hook is accepted, and one test asserts that such an entry still renders.
   - Proof: one feature test per bullet.

3. **Permission rows already in the database are kept.**
   - On a database that already has `roster.sso.view`, `roster.sso.manage` and `roster.scim.manage` as system permissions granted to `admin` (the state an upgraded install is in), `roster:sync-permissions` reports each of the three as unregistered (`expectsOutputToContain`, the seams behaviour).
   - The rows and their `admin` grants still exist afterwards, also after running `roster:sync-permissions` with every option the command offers (including any prune, force or delete flag). The test reads the option list from the command definition, so a flag added later is covered.
   - Proof: a feature test.

4. **Membership source `scim` stays readable** (D-003, D-011, D-014: the raw stored value, no new code).
   - Without a registration, a membership stored with `source = 'scim'` reads back as `'scim'`, and `sourceEnum()` answers `null`. `MemberResource` serializes `source` as `'scim'`, the member list (API, MCP `mcpTool()` with its output schema, and the Atrium members tab) answers 200 and shows `scim`, and nothing errors.
   - With `scim` registered through `MembershipSources::register('scim', 'SCIM')`, as Guardian does, the same row shows `SCIM`.
   - Write and filter paths accept a stored `scim` value without a registration: updating a membership that has `source = 'scim'`, importing a row for it, and filtering the member list by `source=scim` neither fail validation nor drop or rewrite the source.
   - Proof: a feature test for each case.

### Surfaces

5. **The SSO and SCIM routes are gone.**
   - `Route::has()` is false for every one of these route names:
     - `roster.organizations.sso-connections.index|store`
     - `roster.sso-connections.show|update|destroy`
     - `roster.users.sso-identities.index`
     - `roster.sso-identities.destroy`
     - `roster.organizations.scim-tokens.index|store`
     - `roster.scim-tokens.destroy`
     - `roster.sso.discover|start|callback|metadata|link`
     - `atrium.roster.sso.store|show|update|destroy`
     - `atrium.roster.sso-identities.destroy`
     - `atrium.roster.scim-tokens.store|revoke`
   - No registered route name starts with `roster.scim.`.
   - `RosterServiceProvider::registerSurfaces()` names only the `web` surface. The observable is the surface registry content on Keystone's `Surface` for Roster: it holds no `scim` entry and no prefix matching the sso/scim regex of criterion 12.
   - Proof: a dataset feature test over the names, plus a route-table test that iterates `Route::getRoutes()` and asserts that no route name or URI matches the criterion 12 regex. That covers unnamed routes, `scim/v2/...`, `sso/...` and `.well-known` URIs, and the `roster.sso.`, `atrium.roster.sso` and `roster.scim.` prefixes.

6. **The MCP tools are gone.**
   - `RosterServer`'s tool list has none of these: `ListSsoConnectionsTool`, `ShowSsoConnectionTool`, `CreateSsoConnectionTool`, `UpdateSsoConnectionTool`, `DeleteSsoConnectionTool`, `ListSsoIdentitiesTool`, `UnlinkSsoIdentityTool`, `ListScimTokensTool`, `CreateScimTokenTool`, `RevokeScimTokenTool`.
   - Proof: a feature test asserts that no tool name the server lists matches the criterion 12 regex.

7. **The organization screen loses its `sso` and `scim` built-in tabs** (D-005).
   - `roster.organization` declares exactly `members`, `teams`, `invitations`, `roles`, `settings` through `Screen::tabs()`, in that order, and `members` stays the default tab.
   - The tab-permission map in `OrganizationUiController` and the view's icon map lose their `sso` / `scim` entries. This is observable as follows: `?tab=sso` is not permission-gated (a viewer without `roster.sso.view` still gets 200 with `members`), and the rendered tab bar has no sso/scim icon or test id.
   - `GET atrium.roster.organizations.show` with `?tab=sso` or `?tab=scim`, for a viewer holding every organization permission, answers 200 with `members` open (`ResolvedScreen::$openTab === 'members'`). This is Atrium's unknown-key fallback.
   - `SsoUiController`, `ScimUiController` and `resources/views/ui/sso/` are deleted, along with any `roster.sso-connection` screen declaration that `adopt-atrium-screen-registry` added.
   - Proof: feature tests for the tab list and each fallback, and `class_exists()` false for both controllers.

8. **The user overview loses its SSO identities card.**
   - `UserUiController` no longer passes `ssoIdentities`.
   - `ui/users/partials/overview.blade.php` (and `users/show`) render no SSO markup. Guardian places its card through the `roster.user` `aside` slot.
   - Proof: a feature test asserts that the overview response has no `ssoIdentities` view data and no `unlink-sso-identity` test id.

### Config, data and dependencies

9. **The config is gone.**
   - `config/roster.php` has no `sso` or `scim` section, and their banners are gone with them (the criterion 12 grep over `config/` covers the banners).
   - `roster.tables` and `Support\Tables::DEFAULTS` have no `sso_connections`, `sso_identities`, `scim_tokens`, `scim_users` or `scim_groups` key.
   - The `policies` banner no longer mentions SSO or SCIM.
   - Proof: a feature test asserts that `config('roster.sso')` and `config('roster.scim')` are `null` and checks both key lists. The ArchTest `literalTableNames()` and `modelsWithoutPolicies()` checks stay green.

10. **The migrations remove nothing.** Covers D-001.
    - `2026_01_01_000006_create_roster_sso_tables.php` and `2026_01_01_000007_create_roster_scim_tables.php` are deleted.
    - `2026_01_01_000010_add_roster_lookup_indexes.php` drops its `sso_connections`, `scim_tokens` and `scim_groups` entries. `2026_10_12_000001_add_roster_performance_indexes.php` drops `scim_users` and `scim_groups`.
    - No remaining file under `database/migrations/` names any of the five SSO/SCIM tables or keys. The single strict check is the criterion 12 regex over `database/`, which subsumes any drop, rename or `Schema::table` for them.
    - Proof:
      - On a fresh database, `migrate` creates none of `roster_sso_connections`, `roster_sso_identities`, `roster_scim_tokens`, `roster_scim_users` or `roster_scim_groups`.
      - An upgrade test creates the five tables with one row each. The schema comes from a fixture file, `tests/Fixtures/legacy_sso_scim_schema.php`, copied verbatim from the deleted 000006/000007 files, not hand-written. It then runs the full Roster migration set, then `migrate:rollback`, `migrate:refresh` and `migrate:fresh`-style runs over the Roster migrations. The five tables and their rows still exist after the rollback and refresh runs; for `migrate:fresh` the test only asserts that no Roster migration errors or creates a second set of tables.
      - A stray `migrations` row for `create_roster_sso_tables` or `create_roster_scim_tables` does not make `migrate` or `migrate:status` fail.
      - A grep test asserts that no file under `database/` matches the criterion 12 regex.

11. **The composer suggestions change.**
    - `composer.json` `suggest` drops `laravel/socialite`, `socialiteproviders/saml2`, `firebase/php-jwt` and `socialiteproviders/microsoft-azure`. It adds `"refactor-circus/guardian": "Single sign-on (OIDC, SAML, Entra ID) and SCIM provisioning for Roster's organizations (dev-main)."`.
    - `require-dev` drops the same four vendor packages, since no remaining test uses them.
    - `require` is unchanged by this spec, and so is the `conflict` on `refactor-circus/atrium` `<0.1.0` added by `adopt-atrium-screen-registry` (D-008); the composer test asserts it is still there.
    - `composer validate --strict` passes. It is run in the PR gate by hand and is not part of `composer test`.
    - `composer update` and `composer analyse` still pass after the four dev packages are dropped.
    - Proof: a docs/composer feature test decodes `composer.json` and checks both lists, and a grep test asserts that `tests/`, `workbench/`, `src/`, `config/` and `resources/` reference none of `Laravel\Socialite`, `SocialiteProviders` or `Firebase\JWT`.

12. **No source names SSO or SCIM any more.**
    - The scanned set is exactly `src/`, `config/`, `database/`, `lang/`, `resources/` (views, Boost skill, import templates) and `workbench/`. No file in it matches this case-sensitive regex, which the test uses verbatim: `/(?<![A-Za-z])(?:sso|scim)|Sso|Scim|(?<![A-Z])(?:SSO|SCIM)(?![A-Z])/`. It covers snake_case, kebab-case, a leading word, camelCase and PascalCase identifiers, and upper-case constants, while skipping words such as "association". The allow-list is empty (D-012).
    - `README.md`, `CHANGELOG.md`, `composer.json`, `CLAUDE.md` and `AGENTS.md` are outside this scan on purpose. They are checked by their own criteria (11, 14, 15). `tests/` and `docs/` are not scanned.
    - Comments and descriptions that mentioned SSO/SCIM say "externally provisioned" accounts or "a registered sign-in requirement" instead. This covers `PurgeOrganizationAction`, `PurgeOrganizationTool`, `DescribesOrganization`, `CreateOrganizationData`, `OrganizationRules`, migration 000002, the email-index migration, `Roster::provisionedStatus()`, `ProvisionedStatus`, `ExternalSignIn`, `Policy`, `Audit`, `PermissionServiceProvider`, `RosterServiceProvider` and `import_users.csv`.
    - Every `sso*` / `scim*` key, plus `link_sso` and `unknown_sso_connection`, leaves `lang/en/roster.php`.
    - Proof: a feature grep test over the scanned set.

13. **The sign-in rule** (D-012, D-015).
    - `NotSsoEnforced` leaves with the SSO domain.
    - Roster keeps the generic rule as `RefactorCircus\Roster\Support\Rules\NoExternalSignIn implements ValidationRule`. Its body is the same: it fails when `Roster::externalSignInFor($value)` answers non-null, with the message `roster::roster.external_sign_in_required` (`:organization` from `label`, `:url` from `url`). The English text is unchanged from today's `sso_required`: `:organization requires single sign-on. Sign in at :url`. It contains no sso/scim token, so criterion 12 holds.
    - Proof: a feature test registers a fake `SignInRequirement` through the seams registration call `Roster::requireSignIn()` and covers each case: the rule fails with the exact translated string above, `:organization` and `:url` replaced from the label and url, passes for `null`, a super-admin passes, and a throwing requirement propagates.

### Documentation and gate

14. **The CHANGELOG `[Unreleased]` → `### Breaking` entry, "SSO and SCIM moved to refactor-circus/guardian".**
    - Its first line, and the first line of the README section, is bold: **Without Guardian, organizations that enforce SSO accept password sign-in.**
    - It lists what was removed: the domains, routes, Atrium tabs and pages, MCP tools, config, table keys, permissions, `MembershipSource` registration, `NotSsoEnforced` and vendor suggestions.
    - It gives the upgrade steps:
      1. Install `refactor-circus/guardian` in the same deploy as this Roster version.
      2. **Before running any migrate command**, delete your published copies of `create_roster_sso_tables` and `create_roster_scim_tables`, and never roll them back, because their `down()` drops the tables. Remove the SSO/SCIM keys from published copies of the two index migrations.
      3. Publish and run Guardian's migrations, before running `roster:sync-permissions`. Guardian's upgrade migration renames `roster_sso_*` / `roster_scim_*` to `guardian_*` and role grants of `roster.sso.*` / `roster.scim.*` to `guardian.sso.*` / `guardian.scim.*`; it acts only once this Roster version is installed (Guardian's handover gate). **If you installed Guardian before this Roster version**, its upgrade migration already ran and skipped, so run `php artisan guardian:upgrade-from-roster` after upgrading Roster to finish the rename (D-010).
      4. Move published config from `roster.sso.*` / `roster.scim.*` / `roster.tables.sso_*|scim_*` to the matching `guardian.*` keys, and check that Guardian's SCIM prefix matches what your identity providers call (Roster's default was `scim/v2`).
      5. Replace `RefactorCircus\Roster\Domains\Sso|Scim\…` classes, `roster.sso.*` / `roster.scim.*` route names and permission names (`@rosterCan`, seeders) with Guardian's.
      6. Replace `NotSsoEnforced` with `RefactorCircus\Roster\Support\Rules\NoExternalSignIn` (one `use` change; message key `external_sign_in_required`).
    - It describes the behaviour after upgrading:
      - Old `?tab=sso` and `?tab=scim` links open the default (members) tab. Guardian's tabs are `guardian.sso` / `guardian.scim`.
      - Without Guardian, the tables and rows stay untouched, SSO sign-in and the SCIM endpoints answer 404, the three permission rows are kept and reported by `roster:sync-permissions`, and `scim` memberships show their raw value.
    - Proof: a DocsTest extracts the `[Unreleased]` → `### Breaking` section for the heading "SSO and SCIM moved to refactor-circus/guardian" and asserts, inside that slice only, that it contains `refactor-circus/guardian`, `guardian.sso`, `?tab=sso`, `?tab=scim`, `roster.sso.`, `create_roster_sso_tables`, `guardian:upgrade-from-roster`, `NoExternalSignIn` and the bold warning sentence; that the six numbered steps appear in order; and that step 2 is bold. The behaviour claims (404s, rows kept, permissions reported, `scim` shown raw) are proved by criteria 3, 4 and 5; the test only asserts that the slice states them.

15. **README and Boost skill.**
    - The README's "Single sign-on", "Requiring SSO" and "SCIM provisioning" sections are replaced by one section, "SSO and SCIM (refactor-circus/guardian)". Its first line is the bold fail-open warning from criterion 14 (D-017). It covers the same upgrade steps, in short, including `guardian:upgrade-from-roster` for early Guardian installs, and links to Guardian.
    - "Extending Roster" no longer cites Roster's own SSO/SCIM as users of the seams. Its `NotSsoEnforced` / `SsoConnectionModel` mentions become `NoExternalSignIn` and "a registered sign-in requirement".
    - The config section list and the "Atrium dashboard" tab list drop `sso` / `scim`.
    - The Boost skill `resources/boost/skills/roster-development/SKILL.md` gets the same changes, regenerated per `package-generate-skill`.
    - `CLAUDE.md` and `AGENTS.md` drop `Sso` and `Scim` from the domain list, `scim.php` from the route files, the `scim` route surface, and the SCIM token audit-context wording.
    - `docs/agent/product/roadmap.md` gets a one-line note that SSO/SCIM moved to Guardian. The historic specs stay untouched.
    - The local standard `docs/agent/standards/http/authorization.md` drops "SSO/SCIM policies from their domain providers" (in favour of "package resources register their own policies"). `vendor/bin/ringside sync` is rerun.
    - Proof: a DocsTest asserts that the new heading exists, that its first line is the bold warning sentence, that it contains `guardian:upgrade-from-roster`, that no `## Single sign-on` or `## SCIM provisioning` heading remains, and that the seams-era "Extending Roster" checks stay green. It also asserts that the README and `SKILL.md` contain no `NotSsoEnforced`, `SsoConnectionModel` or `Domains\Sso` outside the new Guardian section, that their tab lists have no sso/scim entry, and that `CLAUDE.md` and `AGENTS.md` match neither the criterion 12 regex nor `scim.php`. `vendor/bin/ringside sync` is a manual step and is not tested.

16. **`composer test` and `composer test:browser` pass.**
    - `composer test` runs analyse, lint, type coverage and the non-browser suites. It does not include the browser suite, which is checked separately.
    - Existing tests may change only where this spec forces it mechanically: removed classes, routes, tabs, config, permissions or registrations. This rule is verified by review, not by a test. Each changed or deleted test is listed in the PR by file and test name, and the count of remaining non-SSO/SCIM tests is not lower than before the change. The `composer test` coverage and type-coverage thresholds in `composer.json` must still pass unchanged; lowering them is not allowed.
    - **Without Atrium (D-008).** The `tests/Modes/AtriumUnavailable` suite from `adopt-atrium-screen-registry` stays in `composer test` and passes, and the `without-atrium` CI job stays green, now with no SSO/SCIM code to boot.
    - The browser suite is a manual gate. The only expected browser-test edits are deleting the two SSO/SCIM browser tests listed below; no other browser test is expected to change.
    - Expected deletions, as the suite stands after the seams PR and `adopt-atrium-screen-registry`:
      - `tests/Feature/Sso/`, `tests/Feature/Scim/`
      - `tests/Feature/Http/SsoHttpTest.php`, `ScimTokensHttpTest.php`
      - `tests/Feature/Ui/SsoPagesTest.php`, `ScimPagesTest.php`
      - `tests/Browser/SsoBrowserTest.php`, `ScimBrowserTest.php`
      - `tests/Modes/SsoUnavailable/` and `tests/SsoUnavailableTestCase.php`, with its `uses()` line in `tests/Pest.php`
    - Expected edits (only the SSO/SCIM cases, rows or assertions):
      - `tests/Pest.php`: the `SsoLoginAction` / `LinkSsoIdentityAction` `PARITY_EXCEPTIONS`
      - `tests/ArchTest.php`: comments; its SSO/SCIM pattern tests stay
      - `tests/Authorization/fixtures.php`, `HttpAuthorizationTest.php`, `McpAuthorizationTest.php`, `PoliciesTest.php`, `ScreenControlsTest.php`
      - `tests/Feature/DocsTest.php`, `IndexesTest.php`, `Organization/MembershipSourceTest.php` (register `scim` in the test instead of relying on the SCIM domain), `PerformanceTest.php`, `Permission/PermissionSyncTest.php`, `Permission/RegisteredResourcesTest.php`, `ProvisionedStatusTest.php`, `SoftDeleteTest.php`, `StoredIdentifiersTest.php`, `Transfers/TemplateTest.php`
      - the organization tab-bar and registered-tab tests (expected built-in list)
      - the user registered-tabs test (the overview-only query probe moves to another overview-only table)
      - `UserApprovalTest.php`
      - `tests/Modes/Keen/KeenAuditTest.php`, `tests/Modes/RenamedTables/RenamedTablesTest.php`
      - `tests/Unit/Organization/MembershipSourcesTest.php`, `tests/Unit/Permission/PermissionCatalogueTest.php`

## Design

**Base branch.** This branch is built on top of `ring-master/2026-10-29-2038-auth-extension-seams` and `ring-master/2026-10-29-2038-adopt-atrium-screen-registry` (the orchestrator rebases it before the build; see the note at the top of `tasks.md`), then `composer local:install`. Every class named here (`Resources`, `PermissionCatalogue`, `MembershipSources`, `SignInRequirement`, `Roster::requireSignIn()`, `externalSignInFor()`) exists only after the seams PR. The Atrium screen API (`Screen::standard()->tabs()`, `ResolvedScreen::$openTab`, the `aside` slot) and the `AtriumUnavailable` mode exist only after the adopt spec. The Guardian package does not need to be installed for this build: no test or code here references it.

**Deletion, not migration.** The work is almost all removal:

- The domain folders, the two Atrium controllers, the `ui/sso` views, the SSO/SCIM blocks of `organizations/show.blade.php` and `users/partials/overview.blade.php`, the plugin's seven routes, ten `RosterServer` tools, `DescribesSsoConnection` and the factory all go.
- The domain providers took the `roster.sso` / `roster.scim` resources, permissions, Gate policies, the `scim` membership source and the `scim_token` `AuditHooks` closure with them. So the registrations disappear without touching `Resources`, `PermissionCatalogue`, `MembershipSources` or `Support\Audit`.
- The `@internal` `rosterResource()` / `rosterPermission()` methods stay for the core domains.

**Organization screen.** After `adopt-atrium-screen-registry`, `OrganizationUiController` declares `Screen::make('roster.organization')->tabs([...])`. This spec removes `sso` and `scim` from that list, from the tab-permission map and from the view's icon map. With the built-in keys gone, `?tab=sso` and `?tab=scim` are unknown keys, and Atrium opens the default tab with a 200 (Atrium criterion 7). Guardian's `guardian.sso` / `guardian.scim` registrations never clashed with the built-ins (Guardian D-007), so nothing else changes.

**Migrations.** Removing 000006/000007 from the package leaves the `migrations` rows of existing installs dangling, which Laravel ignores. Nothing in Roster drops, renames or alters the five tables, and their rows stay where they are until Guardian's rename migration moves them. The two index migrations lose their SSO/SCIM entries. Otherwise `Tables::name()` would hit a missing key on fresh installs. Existing installs already ran them, and the indexes travel with the renamed tables.

**Membership source (D-011, D-014).** No code: the seams PR already renders an unregistered stored value as-is.

**Sign-in rule (D-012, D-015).** The rule's body moves to `src/Support/Rules/NoExternalSignIn.php`, with the message key renamed. Nothing in `tests/ArchTest.php` constrains `Support/` beyond strict types and the shared presets.

**Guardian handover (D-010).** Deleting `Domains\Sso\Models\SsoConnectionModel` is what opens Guardian's handover gate: from then on Guardian's upgrade migration and `guardian:upgrade-from-roster` rename the tables and grants, Guardian loads its SSO and SCIM routes, and its morph aliases take over the old class names. Roster adds nothing for this; criterion 1 pins the class name. Guardian adds its `conflict` on older Roster once this removal is tagged.

**Integration config (D-009).** Roster adds no integration config (screen maps or slots) in this spec, so D-009's rule has no surface here.

**Composer.** `suggest` and `require-dev` change as in criterion 11. `bin/composer-local.php` regenerates `composer.local.json` from it.

## Out of scope

- Creating Guardian, its rename migrations, its `guardian.*` permissions, tabs, the `roster.user` `aside` section, and its SCIM prefix default. All of that is the Guardian spec.
- Moving `refactor-circus/atrium` from `require` to `suggest`, the Atrium screen declarations and the Atrium-less test lane. All of that is `adopt-atrium-screen-registry`.
- `organizations.provisioned_status`, `Roster::provisionedStatus()` and the seams themselves, which stay (D-002).
- Deleting the permission rows or the tables of an install that never installs Guardian.
- Historic specs under `docs/agent/specs/` (`2026-10-01-1251-sso`, `2026-10-01-1308-scim`) and the roadmap. They stay as history. The roadmap gets only the one-line note of criterion 15.

## Risks

- **Fail-open without Guardian (D-017, accepted).** Upgrading Roster without installing Guardian turns SSO sign-in and SCIM off at once. Identity providers get 404s and enforced-SSO users fall back to passwords, because no requirement is registered any more. Roster adds no guard; the CHANGELOG and README open with a bold warning (criteria 14, 15), and the DocsTest asserts it. Data is safe either way.
- **Early Guardian installs (D-010).** Guardian's upgrade migration skipped while Roster still owned the tables, and its create migrations made empty `guardian_*` tables (Guardian's "stranded" state, warn-only). The upgrade note tells these apps to run `guardian:upgrade-from-roster` after upgrading Roster.
- **Published migrations (D-013, D-016, D-018).** Published copies of 000006/000007 and the two index migrations call the removed `Tables` keys and would throw, or recreate empty `roster_sso_*` tables. Roster relies on the bold CHANGELOG step 2, asserted by the DocsTest; it keeps no deprecated keys.
- **Purging an organization.** It still removes Guardian's rows through their foreign keys to `roster_organizations`, which survive the rename. That is Guardian's contract, and Roster no longer documents it.
- **Briefly stale wording.** The seams-era CHANGELOG entries in `[Unreleased]` mention `Domains\Sso\…`. They are left as written, and the new Breaking entry supersedes them.

## Changelog

- Applied D-008: the AtriumUnavailable mode, `without-atrium` lane and Atrium `conflict` stay green and in place (criteria 11, 16).
- Applied D-009: noted no integration config surface (Design).
- Applied D-010: Guardian handover contract pinned (criterion 1); upgrade step 3, README and DocsTest name `guardian:upgrade-from-roster` for early Guardian installs (criteria 14, 15, Design, Risks).
- Applied D-011, D-014: raw `scim` source (criterion 4); Q1 removed.
- Applied D-012, D-015: `Support\Rules\NoExternalSignIn` (criteria 12, 13, 14 step 6, 15); seams call named `Roster::requireSignIn()`; Q2 removed.
- Applied D-013, D-016, D-018: bold CHANGELOG step with DocsTest (criterion 14, Risks); Q3 removed.
- Applied D-017: fail-open accepted and documented in the CHANGELOG and README (Goal, criteria 14, 15, Risks); Q4 removed.
