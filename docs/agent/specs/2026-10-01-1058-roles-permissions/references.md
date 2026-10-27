# References for Roles & Permissions

## Similar Implementations

### Features 1–2 (this package)
- **Location:** `src/Http/Request.php`, `src/Mcp/Request.php`, `src/Actions/Concerns/ManagesMemberships.php`, `src/Roster.php`, `src/Atrium/RosterPlugin.php`
- **Relevance:** the request bases gain authorization; the membership concern gains default-role assignment; the context resolver supplies the default scope.

### Impex Authorizer
- **Location:** `../Impex/src/Access/Authorizer.php`, `../Impex/src/Http/Request.php` (`allows()`), `../Impex/tests/TestCase.php` (`impex.authorization` false in tests)
- **Relevance:** the authorization toggle, an `actor()`/`allows()` helper on both request layers, and keeping surface tests behaviour-focused.

### Laravel Gate
- **Location:** `Gate::before`, `Gate::has`, `Authorizable::can`
- **Relevance:** host-facing permission checks and Atrium's `viewAtrium`.
