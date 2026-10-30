VERDICT: PASS

## Gate
- `composer test`: phpstan passed (0 errors), pint passed, pest passed
- `composer lint:check`: pint passed
- `composer exec ringside check`: check passed

## Criteria
1. tests/Unit/Permission/ResourcesTest.php, tests/Feature/Permission/RegisteredResourcesTest.php
2. tests/Feature/Permission/RegisteredResourcesTest.php, tests/ArchTest.php, tests/Authorization/PoliciesTest.php
3. tests/Unit/Permission/PermissionCatalogueTest.php, tests/Feature/Permission/PermissionSyncTest.php
4. tests/Unit/Organization/MembershipSourcesTest.php, tests/Feature/Organization/MembershipSourceTest.php
5. tests/Feature/Scim/ScimAuditContextTest.php
6. tests/Unit/SignInRequirementsTest.php, tests/Feature/Sso/SsoWiringTest.php
7. tests/Unit/ProvisionedStatusTest.php, tests/Feature/ProvisionedStatusTest.php
8. tests/ArchTest.php
9. tests/Feature/DocsTest.php, tests/Feature/ExtendingRosterExamplesTest.php
10. composer test passes; test:browser not run by this gate
