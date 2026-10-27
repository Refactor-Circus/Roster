# References for Organizations, Teams, Members

## Similar Implementations

### Feature 1: Users + Profiles (this package)
- **Location:** `src/Actions/`, `src/Http/{Request.php,Requests,Controllers,Resources,Ui}`, `src/Mcp/`, `src/Atrium/RosterPlugin.php`, `tests/Pest.php`
- **Relevance:** the established shape for Actions, events, both request layers, the Atrium plugin and the parity test.
- **Key patterns:** `UserRequest` / `UserMcpRequest` model-binding bases, `Support\Users`, the `ChangesStatus` guard trait, `mcpTool()` test helper.

### Impex run owners
- **Location:** `../Impex/src/Actions/AttachRunOwnerAction.php`, `../Impex/database/migrations/*run_owner*`
- **Relevance:** membership-style pivot with a uniqueness guard.

### Laravel framework
- **Location:** `Illuminate\Auth\Events\Verified`, `URL::temporarySignedRoute`, `Notification::route('mail', ...)`
- **Relevance:** domain-join trigger, invitation links, invitations for unregistered emails.
