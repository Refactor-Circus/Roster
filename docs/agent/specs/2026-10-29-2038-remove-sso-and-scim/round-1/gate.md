VERDICT: CHANGES

## Gate results

- `composer test`: FAILED (analyse and pint passed; pest 1286 tests, 2 failed).
- `composer lint:check`: passed.
- `composer exec ringside check`: passed.

## Blockers

1. `tests/Feature/Mcp/OutputSchemaTest.php:28` hard-codes 64 structured tools. The ten SSO/SCIM tools are gone, so it now finds 56. Update the count to 56 (mechanical change forced by criterion 6).
2. `tests/Feature/PaginationTest.php:24` hard-codes 13 paged List*Data classes. With Sso and Scim removed there are 10. Update the count to 10 (mechanical change forced by criterion 1).

## Acceptance criteria

Only the test files are named. The individual test names were not checked, because the test run stopped on the two failures above.

- 1 (domains gone): tests/Feature/RemovedSsoScimTest.php, tests/ArchTest.php.
- 2 (registrations): tests/Feature/RemovedSsoScimTest.php, tests/Modes/Keen/KeenAuditTest.php, tests/Unit/Permission/PermissionCatalogueTest.php.
- 3 (permission rows kept): tests/Feature/Permission/PermissionSyncTest.php.
- 4 (scim source readable): tests/Feature/Organization/MembershipSourceTest.php, tests/Unit/Organization/MembershipSourcesTest.php.
- 5, 6 (routes, MCP tools): tests/Feature/RemovedSsoScimTest.php.
- 7, 8 (tabs, user overview): tests/Feature/Ui/RemovedIntegrationsTest.php, tests/Feature/Ui/OrganizationTabBarTest.php, tests/Feature/Ui/UserPagesTest.php.
- 9 to 12 (config, migrations, composer, grep): tests/Feature/RemovedSsoScimTest.php, tests/Feature/LegacySsoScimUpgradeTest.php (fixture tests/Fixtures/legacy_sso_scim_schema.php), tests/Feature/ComposerTest.php.
- 13 (sign-in rule): tests/Feature/NoExternalSignInTest.php.
- 14, 15 (docs): tests/Feature/DocsTest.php.
- 16 (gate): fails on the two blockers. `composer test:browser` is a manual gate and was not run.

## Changed files

The changed files (spec folder left out) are the output of `git diff --name-only main...ring-master/2026-10-29-2038-remove-sso-and-scim`. They are also returned in the structured output.
