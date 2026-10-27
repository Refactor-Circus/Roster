# References for Organization Sync

## This package
- **Organization Actions:** `src/Actions/CreateOrganizationAction.php`, `UpdateOrganizationAction.php` and `ListOrganizationsAction.php`, plus `Actions/Concerns/OrganizationRules` (domains, `auto_join`)
- **Owner invariant:** `Organization::isOwnedBy()`, `Access/Permissions.php` (the owner holds every organization permission), `RemoveMemberAction`, `TransferOwnershipAction`, `ScimUsers`
- **Upsert-by-external-id precedent:** `src/Scim/ScimUsers.php` (`externalId` handling, subject linking)
- **CSV planner pattern:** `src/Transfers/Planners/*Planner.php`, `TransferType`
- **Per-record bulk results:** `src/Scim/Bulk.php`
