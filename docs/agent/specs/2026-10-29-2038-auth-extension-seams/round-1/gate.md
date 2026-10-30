VERDICT: PASS

Gate results:
- composer test: passed (phpstan 0 errors, pint passed, pest 1388/1388, 4798 assertions)
- composer lint:check: passed
- composer exec ringside check: passed

Criteria to proof (by test file; mapped from the branch diff, test names not individually re-read):
1. tests/Unit/Permission/ResourcesTest.php, tests/Feature/Permission/RegisteredResourcesTest.php
2. tests/Feature/Permission/RegisteredResourcesTest.php, tests/Authorization/PoliciesTest.php, tests/ArchTest.php
3. tests/Unit/Permission/PermissionCatalogueTest.php, tests/Feature/Permission/PermissionSyncTest.php
4. tests/Unit/Organization/MembershipSourcesTest.php, tests/Feature/Organization/MembershipSourceTest.php, tests/ArchTest.php
5. tests/Feature/Scim/ScimAuditContextTest.php
6. tests/Unit/SignInRequirementsTest.php, tests/Feature/Sso/SsoWiringTest.php
7. tests/Unit/ProvisionedStatusTest.php, tests/Feature/Scim/ScimUsersTest.php
8. tests/ArchTest.php
9. tests/Feature/DocsTest.php, tests/Feature/ExtendingRosterExamplesTest.php
10. composer test passes; existing-test edits limited to the files allowed by D-005, D-006, D-020, D-022.
