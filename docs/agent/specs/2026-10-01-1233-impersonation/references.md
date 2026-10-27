# References for Impersonation

## Similar Implementations

### Invitations (this package)
- **Location:** `src/Support/InvitationTokens.php`, `src/Http/Web/InvitationWebController.php`, `routes/web.php`, `src/Notifications/InvitationNotification.php` (`temporarySignedRoute`)
- **Relevance:** hashed one-time tokens, signed web routes behind `auth`, and checking that the signed-in user is the right one.

### Access and escalation (this package)
- **Location:** `src/Access/Authorizer.php`, `src/Access/Permissions.php`, `src/Actions/Concerns/GuardsEscalation.php`, `RosterServiceProvider::registerGate()`
- **Relevance:** the impersonate permission, the "no escalation" target check, and blocking abilities while impersonating.

### Audit (this package)
- **Location:** `src/Audit/AuditRecorder.php`, `src/Actions/RecordAuditEventAction.php`
- **Relevance:** the impersonator goes into the context of every entry made while impersonating.
