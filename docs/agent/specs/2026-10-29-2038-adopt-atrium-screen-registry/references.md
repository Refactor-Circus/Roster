# References

- **Roster PR #5 spec:**
  - Location: `docs/agent/specs/2026-10-23-2015-dynamic-tabs-orgs-users/`.
  - Relevance: the behaviour that must survive the move.
  - Borrow: the acceptance criteria as a regression checklist.
- **Organization screen:**
  - Location: `src/Atrium/Http/Controllers/OrganizationUiController.php` (`TABS` map near :78, `offers()` at :303-312) and `resources/views/ui/organizations/show.blade.php` (`activity` at :321, icons map at :19).
  - Relevance: the built-in tabs plus the Keen- and Cortex-gated tabs to remove.
- **User screen:**
  - Location: `src/Atrium/Http/Controllers/UserUiController.php` (:326 SSO identities, :328 redirect domains) and `resources/views/ui/users/partials/overview.blade.php` (:215 audit-trail, :249 redirect-domains card).
- **Team screen:**
  - Location: `resources/views/ui/teams/show.blade.php` (:76 audit-trail) and `TeamUiController`.
- **Role screen:**
  - Location: `resources/views/ui/roles/show.blade.php:54` (audit-trail).
- **Redirect domains (moving to Cortex):**
  - Location: `src/Atrium/RedirectDomains.php`, `src/Atrium/Http/Controllers/RedirectDomainUiController.php`, `resources/views/ui/partials/redirect-domains.blade.php`, the routes in `src/Atrium/RosterPlugin.php:149-150, :174-175`, and `tests/Cortex/RedirectDomainsTest.php`.
- **Atrium registry:**
  - Location: `Atrium` spec `2026-10-29-2037-screen-extensions-registry`.
