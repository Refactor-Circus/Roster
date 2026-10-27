# References for Audit Log

## Similar Implementations

### Action events (this package)
- **Location:** `src/Events/Action/*`, `src/Contracts/ActionStartingEvent.php`, `src/Contracts/ActionFinishedEvent.php`
- **Relevance:** every Action dispatches a start/finish pair carrying its models; the recorder listens to both contracts, so Actions stay unchanged.

### Access (this package)
- **Location:** `src/Access/Authorizer.php`, `src/Access/Permissions.php`, `src/Support/Scopes.php`
- **Relevance:** visibility uses the same ability/scope/self pattern as every other surface.

### Surfaces (features 3–4)
- **Location:** `src/Http/Requests/IndexRolesRequest.php` (input scope), `src/Http/Ui/RoleUiController.php`, `src/Mcp/Requests/ListRolesMcpRequest.php`, `tests/Authorization/fixtures.php`
