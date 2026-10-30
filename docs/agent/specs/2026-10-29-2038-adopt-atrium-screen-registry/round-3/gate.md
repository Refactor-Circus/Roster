VERDICT: PASS

Gate results (round 3)
- composer test: passed (phpstan 0 errors, pint passed, pest 1271 passed, 4760 assertions, coverage 100%)
- composer lint:check: passed
- composer exec ringside check: passed

Criteria to proof (by test file; names not individually enumerated)
1. tests/ArchTest.php (class_exists / view()->exists / content scan)
2. tests/Authorization/ScreenRegistryResetTest.php, tests/Feature/Ui/ScreenRegistryResetTest.php, tests/Pest.php
3. tests/Feature/Ui/ScreenDeclarationsTest.php
4. tests/Feature/Ui/ScreenAccessOnTest.php
5. tests/Feature/Ui/ScreenOrderOfEvaluationTest.php
6. tests/Feature/Ui/OrganizationTabBarTest.php, OrganizationRegisteredTabsTest.php; OrganizationUiController uses reserve()
7. tests/Modes/Keen/OrganizationActivityRemovedTest.php, tests/Authorization/ScreenControlsTest.php
8. tests/Feature/Ui/RemovedIntegrationsTest.php, tests/ArchTest.php
9. tests/Feature/Ui/UserPagesTest.php, UserRegisteredTabsTest.php
10. SharedTabBarTest, OrganizationRegisteredTabsTest, UserRegisteredTabsTest, OrganizationTabBarTest
11. tests/Feature/Ui/RemovedIntegrationsTest.php, tests/Feature/Ui/RosterPluginTest.php
12. tests/Feature/ComposerTest.php
13. tests/ArchTest.php
14. tests/Modes/AtriumUnavailable/AtriumUnavailableTest.php, tests/AtriumUnavailableTestCase.php
15. .github/workflows/tests.yml (CI job; not runnable locally)
16. tests/Feature/AtriumSeamTest.php, tests/Feature/Web/InvitationPageTest.php, ImpersonationFlowTest.php
17. tests/Pest.php guard (CI job proves)
18-19. tests/Feature/DocsTest.php
20. composer test passed; composer test:browser not part of this gate run
21. Merge gate: PR description / meta.yml (outside this gate)
