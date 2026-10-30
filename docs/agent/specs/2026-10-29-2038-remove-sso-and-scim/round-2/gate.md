VERDICT: PASS

# Gate, round 2: Remove SSO and SCIM

| Command | Result |
|---|---|
| `composer test` | passed (phpstan 0 errors, pint passed, pest 1286/1286 passed, 4555 assertions, coverage 100%) |
| `composer lint:check` | passed |
| `composer exec ringside check` | `ringside: check passed.` |

## Acceptance criteria

1. tests/Feature/RemovedSsoScimTest.php: "has no SSO or SCIM domain folder, provider, factory or MCP trait", "registers no SSO or SCIM domain provider", "opens Guardian's handover gate"; tests/ArchTest.php covers the namespace use.
2. RemovedSsoScimTest: "registers no roster.sso or roster.scim resource", "catalogues no roster.sso. or roster.scim. permission", "denies roster.sso.view to an organization admin…", "registers no scim membership source"; tests/Modes/Keen/KeenAuditTest.php covers the scim_token context.
3. tests/Feature/Permission/PermissionSyncTest.php covers the kept rows and the report from roster:sync-permissions.
4. tests/Feature/Organization/MembershipSourceTest.php and tests/Unit/Organization/MembershipSourcesTest.php cover the raw `scim` source.
5. RemovedSsoScimTest: "has no route named :dataset", "has no route whose name or URI names SSO or SCIM…", "names only the web surface for Roster".
6. RemovedSsoScimTest: "lists no MCP tool naming SSO or SCIM".
7. RemovedSsoScimTest: "declares exactly members, teams, invitations, roles and settings…", "opens members for ?tab=:dataset…", "no longer gates ?tab=sso…", "has no SSO or SCIM Atrium controller or page".
8. RemovedSsoScimTest: "shows the user overview without SSO identities".
9. RemovedSsoScimTest: "has no sso or scim config, nor their table keys"; ArchTest literalTableNames/modelsWithoutPolicies.
10. tests/Feature/LegacySsoScimUpgradeTest.php (fresh, upgrade with rollback/refresh, migrate:fresh, stray rows, removed migrations); fixture tests/Fixtures/legacy_sso_scim_schema.php.
11. tests/Feature/ComposerTest.php; RemovedSsoScimTest "references no SSO vendor library…".
12. RemovedSsoScimTest: "names SSO or SCIM nowhere in src, config, database, lang, resources or workbench", "catches each form of the names…".
13. tests/Feature/NoExternalSignInTest.php (all six cases).
14. and 15. tests/Feature/DocsTest.php (CHANGELOG Breaking slice, README Guardian section, SKILL.md, CLAUDE.md/AGENTS.md).
16. `composer test` passes (1286 tests). The browser suite is a manual gate and was not run.
