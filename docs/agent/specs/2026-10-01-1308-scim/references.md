# References for SCIM

## Specifications
- **RFC 7643** (SCIM core schema): User (§4.1), Group (§4.2), meta and version (§3.1), ServiceProviderConfig, ResourceType and Schema (§5–7).
- **RFC 7644** (SCIM protocol): filtering (§3.4.2.2), pagination (§3.4.2.4), PATCH (§3.5.2), Bulk (§3.7), errors (§3.12), versioning/ETags (§3.14).

## Identity-provider request shapes
- **Okta:** POST /Users with `userName`, `name`, `emails`, `active`, `externalId`. Deprovisions with PATCH `{"op":"replace","value":{"active":false}}`. Group membership via PATCH `members` add/remove with `members[value eq "id"]`.
- **Microsoft Entra ID:** PATCH ops with capitalised `"Replace"`/`"Add"`, `path: "active"` or no path, and string booleans (`"False"`). Probes with `GET /Users?filter=userName eq "x"` before creating.

## This package
- **Location:** `src/Actions/{CreateUser,UpdateUser,UpdateProfile,DeactivateUser,ReactivateUser,RemoveMember,CreateTeam,UpdateTeam,DeleteTeam,AddTeamMember,RemoveTeamMember}Action.php`, `src/Actions/Concerns/ManagesMemberships.php`, `src/Models/SsoIdentity.php`, `src/Audit/{Surface,AuditRecorder}.php`, `src/Http/Middleware/*`
- **Relevance:** SCIM services call these Actions; nothing duplicates their rules.
