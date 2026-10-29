# References

- Scope types: `src/Domains/Permission/Services/Authorizer.php:17-18, :51-52`.
- Membership source: `src/Domains/Organization/Enums/MembershipSource.php:16`.
- Audit context: `src/Support/Audit.php:161-190` and `src/Domains/Scim/Services/ScimContext.php`.
- Sign-in enforcement: `src/Roster.php:93-117` (`ssoRequiredFor`), plus its callers in `src/Domains/Sso/`.
- Provisioned status: `src/Domains/Sso/Actions/SsoLoginAction.php:143`, `src/Domains/Scim/Services/ScimUsers.php:115` and `src/Domains/Organization/Resources/OrganizationResource.php:33, :63`.
- Policies and permissions: `config/roster.php:25-34, :514-542`, plus `PermissionServiceProvider`.
- Precedent for a contract plus a null default: Keystone `Audit\Contracts\AuditTrail` / `NullAuditTrail`, which Keen binds.
