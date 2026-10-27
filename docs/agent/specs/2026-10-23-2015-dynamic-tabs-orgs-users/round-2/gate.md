VERDICT: PASS

Gate (worktree /Users/jay/Projects/packages/Roster--2026-10-23-2015-dynamic-tabs-orgs-users):
- composer test: phpstan passed (0 errors), pint passed, pest passed (1239 tests, 4349 assertions).
- composer lint:check: pint passed.
- vendor/bin/ringside check: passed.
- composer test:browser was not part of this gate run.

Criteria to proof (file-level; test names are in the files):
1. Singleton and registration: tests/Unit/TabsTest.php; binding in src/RosterServiceProvider.php; README "Registered tabs" section.
2. TabDefinition API, readonly state, private closures: tests/Unit/TabDefinitionTest.php.
3. Key rules, reserved keys, duplicates, clone, numeric keys: tests/Unit/TabsTest.php, tests/Unit/OrganizationTabsTest.php (constant vs controller map), tests/Feature/Ui/OrganizationTabBarTest.php, OrganizationRegisteredTabsTest.php (123 / 007).
4. flush(): tests/Unit/TabsTest.php; tests/Pest.php (beforeEach/afterEach flush).
5. UI disabled registration: tests/Unit/TabsTest.php.
6. Org bar order, escaping, icon fallback: tests/Feature/Ui/OrganizationRegisteredTabsTest.php.
7. Resolver data and call counts, Roster variables win, component data prop: OrganizationRegisteredTabsTest.php; authorization-first on both screens in OrganizationRegisteredTabsTest.php and UserRegisteredTabsTest.php.
8. Default tab from built-ins only: OrganizationRegisteredTabsTest.php.
9. Permission/closure/public/neither, throwing closure, memo, scoped reset: OrganizationRegisteredTabsTest.php, UserRegisteredTabsTest.php, tests/Unit/TabDefinitionTest.php; src/Atrium/TabVisibility.php (scoped memo).
10. 403 for hidden tab, string-only tab, unknown key fallback: OrganizationRegisteredTabsTest.php, UserRegisteredTabsTest.php.
11. User screen no-tabs unchanged, dataset over two states: UserRegisteredTabsTest.php, tests/Feature/Ui/UserPagesTest.php.
12. Overview wrapper, default, no overview queries (DB::listen): UserRegisteredTabsTest.php; resources/views/ui/users/partials/overview.blade.php.
13. Redirects without tab=: UserRegisteredTabsTest.php.
14. Shared partial, aria-current, characterization: tests/Feature/Ui/SharedTabBarTest.php, OrganizationTabBarTest.php; StylesTest passes in the suite.
15. Org markup unchanged: existing org feature tests pass unmodified in the 1239-test run (browser suite not run here).
16. Deleted records: OrganizationRegisteredTabsTest.php, UserRegisteredTabsTest.php.
17. Docs strings: tests/Feature/DocsTest.php; README.md, resources/boost/skills/roster-development/SKILL.md, CHANGELOG.md.
18. Unit and Feature/Ui tests exist; ArchTest passes within pest.

Changed files:
CHANGELOG.md, README.md, lang/en/roster.php, phpunit.xml.dist, resources/boost/skills/roster-development/SKILL.md, resources/views/ui/organizations/show.blade.php, resources/views/ui/partials/registered-tab.blade.php, resources/views/ui/partials/tabs.blade.php, resources/views/ui/users/partials/overview.blade.php, resources/views/ui/users/show.blade.php, src/Atrium/Http/Controllers/OrganizationUiController.php, src/Atrium/Http/Controllers/UserUiController.php, src/Atrium/TabDefinition.php, src/Atrium/TabVisibility.php, src/Atrium/Tabs.php, src/RosterServiceProvider.php, tests/Feature/DocsTest.php, tests/Feature/Ui/OrganizationRegisteredTabsTest.php, tests/Feature/Ui/OrganizationTabBarTest.php, tests/Feature/Ui/RosterPluginTest.php, tests/Feature/Ui/SharedTabBarTest.php, tests/Feature/Ui/UserPagesTest.php, tests/Feature/Ui/UserRegisteredTabsTest.php, tests/Pest.php, tests/Unit/OrganizationTabsTest.php, tests/Unit/TabDefinitionTest.php, tests/Unit/TabsTest.php
