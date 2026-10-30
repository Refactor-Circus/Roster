# Remove SSO and SCIM

## Goal

Roster stops shipping SSO and SCIM, now that `refactor-circus/guardian` owns them. Roster is left with the seams from `auth-extension-seams` and `organizations.provisioned_status` (D-002). It no longer names an SSO or SCIM class, route, permission, table, config key or vendor library. Existing installs lose no data: Roster drops nothing, and Guardian's upgrade migration renames the tables and role grants (D-001, D-004). Apps get a clear upgrade path in the CHANGELOG and README.

## Acceptance criteria

### Code and registrations

1. **The domains are gone.**
   - `src/Domains/Sso/` and `src/Domains/Scim/` do not exist. `Domains\DomainServiceProvider::$providers` lists neither `SsoServiceProvider` nor `ScimServiceProvider`.
   - `database/factories/SsoConnectionFactory.php` and `src/Mcp/Concerns/DescribesSsoConnection.php` are deleted.
   - Proof: a new arch test asserts that no class under `RefactorCircus\Roster` uses `RefactorCircus\Roster\Domains\Sso` or `RefactorCircus\Roster\Domains\Scim`. A feature test asserts that `is_dir()` is false for both folders, and that `class_exists()` is false for `Domains\Sso\SsoServiceProvider`, `Domains\Scim\ScimServiceProvider` and `Mcp\Concerns\DescribesSsoConnection` (`trait_exists` for the trait).

2. **The internal `roster.sso` / `roster.scim` registrations are gone.** They become Guardian's `guardian.*` (D-004).
   - `app(Resources::class)->model('roster.sso')` and `->model('roster.scim')` answer `null`.
   - `app(PermissionCatalogue::class)->all()` has no name starting with `roster.sso.` or `roster.scim.`.
   - `Authorizer::check()` for `roster.sso.view` denies an organization `admin` who is not a super-admin, because an unregistered prefix resolves no policy and is denied.
   - `app(MembershipSources::class)->has('scim')` is false.
   - With Keen installed and `roster.audit.enabled` on, an entry written for a Roster action carries no `scim_token` context key.
   - Proof: one feature test per bullet.

3. **Permission rows already in the database are kept.**
   - On a database that already has `roster.sso.view`, `roster.sso.manage` and `roster.scim.manage` as system permissions granted to `admin` (the state an upgraded install is in), `roster:sync-permissions` reports each of the three as unregistered (`expectsOutputToContain`, the seams behaviour).
   - The rows and their `admin` grants still exist afterwards.
   - Proof: a feature test.

4. **Membership source `scim` stays readable** (D-003, pending Q1, written to its recommendation).
   - Without a registration, a membership stored with `source = 'scim'` reads back as `'scim'`, and `sourceEnum()` answers `null`. `MemberResource` serializes `source` as `'scim'`, the member list (API, MCP `mcpTool()` with its output schema, and the Atrium members tab) answers 200 and shows `scim`, and nothing errors.
   - With `scim` registered through `MembershipSources::register('scim', 'SCIM')`, as Guardian does, the same row shows `SCIM`.
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
   - `RosterServiceProvider::registerSurfaces()` names only the `web` surface routes. It no longer names `roster.scim.`.
   - Proof: a dataset feature test over the names, plus a test that iterates `Route::getRoutes()` for the prefix.

6. **The MCP tools are gone.**
   - `RosterServer`'s tool list has none of these: `ListSsoConnectionsTool`, `ShowSsoConnectionTool`, `CreateSsoConnectionTool`, `UpdateSsoConnectionTool`, `DeleteSsoConnectionTool`, `ListSsoIdentitiesTool`, `UnlinkSsoIdentityTool`, `ListScimTokensTool`, `CreateScimTokenTool`, `RevokeScimTokenTool`.
   - Proof: a feature test asserts that no tool name the server lists matches `/sso|scim/i`.

7. **The organization screen loses its `sso` and `scim` built-in tabs** (D-005).
   - `roster.organization` declares exactly `members`, `teams`, `invitations`, `roles`, `settings` through `Screen::tabs()`, in that order, and `members` stays the default tab.
   - The tab-permission map in `OrganizationUiController` loses its `sso` / `scim` entries, and the view's icon map loses them too.
   - `GET atrium.roster.organizations.show` with `?tab=sso` or `?tab=scim`, for a viewer holding every organization permission, answers 200 with `members` open (`ResolvedScreen::$openTab === 'members'`). This is Atrium's unknown-key fallback.
   - `SsoUiController`, `ScimUiController` and `resources/views/ui/sso/` are deleted, along with any `roster.sso-connection` screen declaration that `adopt-atrium-screen-registry` added.
   - Proof: feature tests for the tab list and each fallback, and `class_exists()` false for both controllers.

8. **The user overview loses its SSO identities card.**
   - `UserUiController` no longer passes `ssoIdentities`.
   - `ui/users/partials/overview.blade.php` (and `users/show`) render no SSO markup. Guardian places its card through the `roster.user` `aside` slot.
   - Proof: a feature test asserts that the overview response has no `ssoIdentities` view data and no `unlink-sso-identity` test id.

### Config, data and dependencies

9. **The config is gone.**
   - `config/roster.php` has no `sso` or `scim` section, and their banners are gone with them.
   - `roster.tables` and `Support\Tables::DEFAULTS` have no `sso_connections`, `sso_identities`, `scim_tokens`, `scim_users` or `scim_groups` key.
   - The `policies` banner no longer mentions SSO or SCIM.
   - Proof: a feature test asserts that `config('roster.sso')` and `config('roster.scim')` are `null` and checks both key lists. The ArchTest `literalTableNames()` and `modelsWithoutPolicies()` checks stay green.

10. **The migrations remove nothing.** Covers D-001.
    - `2026_01_01_000006_create_roster_sso_tables.php` and `2026_01_01_000007_create_roster_scim_tables.php` are deleted.
    - `2026_01_01_000010_add_roster_lookup_indexes.php` drops its `sso_connections`, `scim_tokens` and `scim_groups` entries. `2026_10_12_000001_add_roster_performance_indexes.php` drops `scim_users` and `scim_groups`.
    - No remaining file under `database/migrations/` contains `drop`, `rename` or `Schema::table` for any of the five SSO/SCIM table names or keys.
    - Proof:
      - On a fresh database, `migrate` creates none of `roster_sso_connections`, `roster_sso_identities`, `roster_scim_tokens`, `roster_scim_users` or `roster_scim_groups`.
      - An upgrade test creates the five tables with one row each (the 000006/000007 schema, built in the test). It then runs `migrate`, and runs `migrate:rollback` for the two index migrations by `--path`. All five tables and their rows still exist afterwards.
      - A grep test asserts that no file under `database/` matches `/(^|[^a-z])(sso|scim)/i`.

11. **The composer suggestions change.**
    - `composer.json` `suggest` drops `laravel/socialite`, `socialiteproviders/saml2`, `firebase/php-jwt` and `socialiteproviders/microsoft-azure`. It adds `"refactor-circus/guardian": "Single sign-on (OIDC, SAML, Entra ID) and SCIM provisioning for Roster's organizations (dev-main)."`.
    - `require-dev` drops the same four vendor packages, since no remaining test uses them.
    - `require` is unchanged by this spec.
    - `composer validate --strict` passes.
    - Proof: a docs/composer feature test decodes `composer.json` and checks both lists.

12. **No source names SSO or SCIM any more.**
    - No file under `src/`, `config/`, `database/`, `lang/`, `resources/` (views, Boost skill, import templates) or `workbench/` matches `/(^|[^a-z])(sso|scim)/i`. The one exception is whatever Q2 decides the sign-in rule and its message key are called.
    - Comments and descriptions that mentioned SSO/SCIM say "externally provisioned" accounts or "a registered sign-in requirement" instead. This covers `PurgeOrganizationAction`, `PurgeOrganizationTool`, `DescribesOrganization`, `CreateOrganizationData`, `OrganizationRules`, migration 000002, the email-index migration, `Roster::provisionedStatus()`, `ProvisionedStatus`, `ExternalSignIn`, `Policy`, `Audit`, `PermissionServiceProvider`, `RosterServiceProvider` and `import_users.csv`.
    - Every `sso*` / `scim*` key, plus `link_sso` and `unknown_sso_connection`, leaves `lang/en/roster.php`.
    - Proof: a feature grep test. `tests/` and `docs/` are not scanned.

13. **The sign-in rule** (pending Q2, written to its recommendation).
    - `NotSsoEnforced` leaves with the SSO domain.
    - Roster keeps the generic rule as `RefactorCircus\Roster\Support\Rules\NoExternalSignIn implements ValidationRule`. Its body is the same: it fails when `Roster::externalSignInFor($value)` answers non-null, with the message `roster::roster.external_sign_in_required` (`:organization` from `label`, `:url` from `url`). The text is unchanged from today's `sso_required`.
    - Proof: a feature test with a fake `SignInRequirement` covers each case: the rule fails with the label and url, passes for `null`, a super-admin passes, and a throwing requirement propagates.

### Documentation and gate

14. **The CHANGELOG `[Unreleased]` → `### Breaking` entry, "SSO and SCIM moved to refactor-circus/guardian".**
    - It lists what was removed: the domains, routes, Atrium tabs and pages, MCP tools, config, table keys, permissions, `MembershipSource` registration, `NotSsoEnforced` and vendor suggestions.
    - It gives the upgrade steps:
      1. Install `refactor-circus/guardian` in the same deploy as this Roster version.
      2. Delete your published copies of `create_roster_sso_tables` and `create_roster_scim_tables`, and never roll them back, because their `down()` drops the tables. Remove the SSO/SCIM keys from published copies of the two index migrations.
      3. Publish and run Guardian's migrations. They rename `roster_sso_*` / `roster_scim_*` to `guardian_*` and rename role grants of `roster.sso.*` / `roster.scim.*` to `guardian.sso.*` / `guardian.scim.*`.
      4. Move published config from `roster.sso.*` / `roster.scim.*` / `roster.tables.sso_*|scim_*` to the matching `guardian.*` keys, and check that Guardian's SCIM prefix matches what your identity providers call (Roster's default was `scim/v2`).
      5. Replace `RefactorCircus\Roster\Domains\Sso|Scim\…` classes, `roster.sso.*` / `roster.scim.*` route names and permission names (`@rosterCan`, seeders) with Guardian's.
      6. Replace `NotSsoEnforced` per Q2.
    - It describes the behaviour after upgrading:
      - Old `?tab=sso` and `?tab=scim` links open the default (members) tab. Guardian's tabs are `guardian.sso` / `guardian.scim`.
      - Without Guardian, the tables and rows stay untouched, SSO sign-in and the SCIM endpoints answer 404, the three permission rows are kept and reported by `roster:sync-permissions`, and `scim` memberships show their raw value.
    - Proof: a DocsTest asserts the entry contains `refactor-circus/guardian`, `guardian.sso`, `?tab=sso`, `?tab=scim`, `roster.sso.` and `create_roster_sso_tables`.

15. **README and Boost skill.**
    - The README's "Single sign-on", "Requiring SSO" and "SCIM provisioning" sections are replaced by one section, "SSO and SCIM (refactor-circus/guardian)". It covers the same upgrade steps, in short, and links to Guardian.
    - "Extending Roster" no longer cites Roster's own SSO/SCIM as users of the seams. Its `NotSsoEnforced` / `SsoConnectionModel` mentions follow Q2.
    - The config section list and the "Atrium dashboard" tab list drop `sso` / `scim`.
    - The Boost skill `resources/boost/skills/roster-development/SKILL.md` gets the same changes, regenerated per `package-generate-skill`.
    - The local standard `docs/agent/standards/http/authorization.md` drops "SSO/SCIM policies from their domain providers" (in favour of "package resources register their own policies"). `vendor/bin/ringside sync` is rerun.
    - Proof: a DocsTest asserts that the new heading exists, that no `## Single sign-on` or `## SCIM provisioning` heading remains, and that the seams-era "Extending Roster" checks stay green.

16. **`composer test` and `composer test:browser` pass.**
    - `composer test` runs analyse, lint, type coverage and the non-browser suites. It does not include the browser suite, which is checked separately.
    - Existing tests may change only where this spec forces it mechanically: removed classes, routes, tabs, config, permissions or registrations. Each changed or deleted test is listed in the PR by file and test name.
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

**Rebase first.** This worktree's branch (`ring-master/2026-10-29-2038-remove-sso-and-scim`) was cut from `main` before the seams PR (#11) merged. Before building, rebase it onto `ring-master/2026-10-29-2038-auth-extension-seams`, or onto `main` once #11 has merged, and then onto `adopt-atrium-screen-registry` once that lands. Then run `composer local:install`. Every class named here (`Resources`, `PermissionCatalogue`, `MembershipSources`, `SignInRequirement`, `externalSignInFor()`) exists only after the seams PR. The Atrium screen API (`Screen::make()->tabs()`, `ResolvedScreen::$openTab`, the `aside` slot) exists only after the adopt spec. The Guardian package does not need to be installed for this build: no test or code here references it.

**Deletion, not migration.** The work is almost all removal:

- The domain folders, the two Atrium controllers, the `ui/sso` views, the SSO/SCIM blocks of `organizations/show.blade.php` and `users/partials/overview.blade.php`, the plugin's seven routes, ten `RosterServer` tools, `DescribesSsoConnection` and the factory all go.
- The domain providers took the `roster.sso` / `roster.scim` resources, permissions, Gate policies, the `scim` membership source and the `scim_token` `AuditHooks` closure with them. So the registrations disappear without touching `Resources`, `PermissionCatalogue`, `MembershipSources` or `Support\Audit`.
- The `@internal` `rosterResource()` / `rosterPermission()` methods stay for the core domains.

**Organization screen.** After `adopt-atrium-screen-registry`, `OrganizationUiController` declares `Screen::make('roster.organization')->tabs([...])`. This spec removes `sso` and `scim` from that list, from the tab-permission map and from the view's icon map. With the built-in keys gone, `?tab=sso` and `?tab=scim` are unknown keys, and Atrium opens the default tab with a 200 (Atrium criterion 7). Guardian's `guardian.sso` / `guardian.scim` registrations never clashed with the built-ins (Guardian D-007), so nothing else changes.

**Migrations.** Removing 000006/000007 from the package leaves the `migrations` rows of existing installs dangling, which Laravel ignores. Nothing in Roster drops, renames or alters the five tables, and their rows stay where they are until Guardian's rename migration moves them. The two index migrations lose their SSO/SCIM entries. Otherwise `Tables::name()` would hit a missing key on fresh installs. Existing installs already ran them, and the indexes travel with the renamed tables.

**Membership source.** Q1's recommendation needs no code: the seams PR already renders an unregistered stored value as-is.

**Sign-in rule.** Q2's recommendation moves the rule's body to `src/Support/Rules/NoExternalSignIn.php`, with the message key renamed. Nothing in `tests/ArchTest.php` constrains `Support/` beyond strict types and the shared presets.

**Composer.** `suggest` and `require-dev` change as in criterion 11. `bin/composer-local.php` regenerates `composer.local.json` from it.

## Out of scope

- Creating Guardian, its rename migrations, its `guardian.*` permissions, tabs, the `roster.user` `aside` section, and its SCIM prefix default. All of that is the Guardian spec.
- Moving `refactor-circus/atrium` from `require` to `suggest`, the Atrium screen declarations and the Atrium-less test lane. All of that is `adopt-atrium-screen-registry`.
- `organizations.provisioned_status`, `Roster::provisionedStatus()` and the seams themselves, which stay (D-002).
- Deleting the permission rows or the tables of an install that never installs Guardian.
- Historic specs under `docs/agent/specs/` (`2026-10-01-1251-sso`, `2026-10-01-1308-scim`) and the roadmap. They stay as history. The roadmap line may note the move.

## Risks and open questions

**Q1. What does Roster show for a stored `scim` membership source when Guardian isn't installed?** (D-003)
- **A: the raw stored value `scim`.** This is the seams PR's existing behaviour for unregistered sources. It needs no code, and Guardian's registration restores the "SCIM" label.
- **B: a configurable label map in Roster** (`roster.membership_sources.labels`). This means new config, and `scim` would reappear in Roster's config.
- **C: a generic label such as "External".** This hides the real value.
- **Recommendation: A.** The criteria are written to it.

**Q2. Where does the password-login rule `NotSsoEnforced` go?** It lives in the SSO domain today, but it enforces every registered `SignInRequirement`, and the seam is Roster's.
- **A: Roster keeps a generic rule,** `Support\Rules\NoExternalSignIn`, with message key `external_sign_in_required`. Apps change one `use`. Any package's requirement keeps being enforced without Guardian's help.
- **B: the rule moves to Guardian with SSO.** Apps without Guardian then have no rule, and must call `Roster::externalSignInFor()` themselves.
- **C: keep the class name `NotSsoEnforced` at a core path.** This leaves an SSO name in core, against criterion 12.
- **Recommendation: A.** The Guardian spec should then reference Roster's rule in its README rather than ship its own.

**Q3. What about apps with published migrations?** The README tells apps to publish Roster's migrations, so most installs carry copies of 000006/000007 and of the two index migrations, which call `Tables::name('sso_connections')` and the like. With the keys gone from `Tables::DEFAULTS`, and absent from a published config, those copies throw "Undefined array key" on `migrate:fresh`, `migrate:refresh` and `migrate:rollback`. Running `migrate:fresh` with the 000006/000007 copies in place would also recreate empty `roster_sso_*` tables beside Guardian's.
- **A: rely on the CHANGELOG step** (delete the two published copies and edit the index copies). This follows the shape.
- **B: also keep the five keys in `Tables::DEFAULTS`** (not in `config/roster.php`) as deprecated, for one release, so unedited copies keep working. This breaks criterion 12's grep for `src/`.
- **Recommendation: A**, with the CHANGELOG step in bold. Spec review should confirm.

**Other risks**

- **Deploy ordering.** Upgrading Roster without installing Guardian turns SSO sign-in and SCIM off at once. Identity providers get 404s and enforced-SSO users fall back to passwords, because no requirement is registered any more. The CHANGELOG says to install Guardian in the same deploy. Data is safe either way.
- **Purging an organization.** It still removes Guardian's rows through their foreign keys to `roster_organizations`, which survive the rename. That is Guardian's contract, and Roster no longer documents it.
- **Briefly stale wording.** The seams-era CHANGELOG entries in `[Unreleased]` mention `Domains\Sso\…`. They are left as written, and the new Breaking entry supersedes them.
