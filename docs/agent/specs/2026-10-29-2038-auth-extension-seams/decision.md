# Factory decision

Status: needs-decision (stage: build, round 0)

Group 1 is committed except for removing the SSO/SCIM entries from `config/roster.php` `policies`.

Questions: Q-001 (config policies vs the ArchTest/PoliciesTest freeze), Q-002 (missing-policy throw scope deviation).

Other deviations the build reported:
- `PermissionCatalogue` reads `BuiltInRoles` (Role domain) for slugs and core names. The Permission domain already depends on it.
- An app that removed an SSO/SCIM model from its published `roster.policies` gets the bundled policy back, because the domain provider registers it. An app's own policy class still wins.
