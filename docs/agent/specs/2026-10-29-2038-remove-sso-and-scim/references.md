# References

- Domains: `src/Domains/Sso/` and `src/Domains/Scim/`; registered in `src/Domains/DomainServiceProvider.php:13-14, :34-35`.
- UI: `src/Atrium/Http/Controllers/SsoUiController.php` and `ScimUiController.php`, `resources/views/ui/sso/`, and the routes in `src/Atrium/RosterPlugin.php:200-207`.
- MCP: `src/Mcp/RosterServer.php:51-60, :194+` and `src/Mcp/Concerns/DescribesSsoConnection.php`.
- Config: `config/roster.php` (sso :244, scim :268, policies :538-542, tables :576-580).
- Migrations: `database/migrations/2026_01_01_000006_create_roster_sso_tables.php` and `000007_create_roster_scim_tables.php`.
- Historic specs: `docs/agent/specs/2026-10-01-1251-sso`, `2026-10-01-1308-scim`.
