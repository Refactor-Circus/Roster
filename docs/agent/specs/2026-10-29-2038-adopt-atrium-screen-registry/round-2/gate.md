VERDICT: PASS

# Gate, round 2

| Command | Result |
|---|---|
| `composer --working-dir=... test` | passed: phpstan 0 errors, pint passed, pest 1271/1271 (4758 assertions), coverage 100% |
| `composer --working-dir=... lint:check` | passed (pint) |
| `composer --working-dir=... exec ringside check` | `ringside: check passed.` |

## Criteria

1. tests/ArchTest.php (class/view absence + file-content scan)
2. tests/Feature/Ui/ScreenRegistryResetTest.php, tests/Authorization/ScreenRegistryResetTest.php, tests/Pest.php
3. tests/Feature/Ui/ScreenDeclarationsTest.php
4. tests/Feature/Ui/ScreenAccessOnTest.php
5. tests/Feature/Ui/ScreenOrderOfEvaluationTest.php
6. tests/Feature/Ui/OrganizationTabBarTest.php, OrganizationRegisteredTabsTest.php (Atrium `Screen::reserve()` present in vendor)
7. tests/Modes/Keen/OrganizationActivityRemovedTest.php, tests/Authorization/ScreenControlsTest.php
8. tests/Feature/Ui/RemovedIntegrationsTest.php, tests/ArchTest.php
9. tests/Feature/Ui/UserPagesTest.php, UserRegisteredTabsTest.php
10. OrganizationRegisteredTabsTest, UserRegisteredTabsTest, SharedTabBarTest, OrganizationTabBarTest
11. tests/Feature/Ui/RemovedIntegrationsTest.php, RosterPluginTest.php
12. tests/Feature/ComposerTest.php
13. tests/ArchTest.php
14. tests/Modes/AtriumUnavailable/AtriumUnavailableTest.php, tests/AtriumUnavailableTestCase.php
15. .github/workflows/tests.yml `without-atrium` job (green status only provable on the PR)
16. tests/Feature/AtriumSeamTest.php, tests/Feature/Web/InvitationPageTest.php, ImpersonationFlowTest.php, StylesTest.php
17. tests/Pest.php guard; CI lane
18. tests/Feature/DocsTest.php
19. tests/Feature/DocsTest.php
20. composer test green (test:browser not part of this gate)
21. meta.yml `blockers:`; PR description at ship time
