# References for User Approval

- **Status transitions:** `src/Actions/Concerns/ChangesStatus.php`, `SuspendUserAction.php`, `DeactivateUserAction.php`, `ReactivateUserAction.php`
- **Enforcement:** `src/Http/Middleware/EnsureUserIsActive.php`, `SsoLoginAction` (inactive refusal), `StartImpersonationAction`
- **Laravel auth event listener:** `src/Listeners/JoinOrganizationsOnVerified.php`
- **On-demand mail notification:** `src/Notifications/InvitationNotification.php`
- **Organization setting precedent:** `auto_join` in `OrganizationRules` / Create and Update Organization Actions
