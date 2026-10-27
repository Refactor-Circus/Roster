# References for SSO

## Similar Implementations

### Domain trust (this package)
- **Location:** `src/Models/OrganizationDomain.php`, `src/Actions/JoinOrganizationsByDomainAction.php`
- **Relevance:** an organization's domains decide where the IdP is authoritative (JIT and auto-linking).

### Web flows (this package)
- **Location:** `src/Http/Web/InvitationWebController.php`, `src/Http/Web/ImpersonationWebController.php`, `routes/web.php`
- **Relevance:** browser-only Actions and parity exceptions.

### Libraries
- **Location:** `vendor/laravel/socialite/src/Two/AbstractProvider.php`, `vendor/socialiteproviders/saml2/src/Provider.php`, `vendor/firebase/php-jwt/src/{JWT,JWK}.php`
- **Relevance:** the OIDC driver extends Socialite's AbstractProvider; SAML is configured per connection at runtime; id_tokens are verified against the JWKS.
