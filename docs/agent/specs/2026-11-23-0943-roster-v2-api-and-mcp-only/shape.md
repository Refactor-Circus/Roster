# Roster v2: API and MCP only — Shaping Notes

## Scope

Roster keeps its JSON API (users, organizations, teams, invitations, roles, permissions, impersonations, transfers) and MCP, and drops its Atrium plugin and its `Atrium\AtriumServiceProvider` (14 screens, 2 widgets, 2 search sources, the public invitation page binding, `@rosterCan`).

This package's part of the suite move to a headless Atrium v2. It changes this package only.

- New API, missing today:
  - `PATCH roster/users/{user}/status`, or the UI's status dispatcher mapped onto the existing suspend, deactivate and reactivate routes
  - `GET roster/organizations/{organization}/role-assignments`
  - `GET roster/invitations/{token}`, the public lookup the invitation page needs, with no auth beyond the token
  - `GET roster/stats`: user counts by status, and organization, team and pending-invitation counts
  - `GET roster/search?q=&type=users|organizations`
- The `InvitationPage` contract stays in Roster, so the host app or Atrium can bind the URL of the public page; Roster's Atrium binding goes.
- `roster.routes.enabled` defaults to false today (see the open question in Polycart, which is the same).
- Run Scramble against the workbench and commit `openapi.json`; generate and commit `client/` (`openapi-typescript` + `openapi-fetch`); add a composer script (`client:generate`) and a staleness test.
- Delete `src/Atrium/**`, Atrium Blade views, Atrium-only tests and Atrium config keys; remove the Atrium composer entries and the provider's Atrium registration.
- README, CHANGELOG (v2.0.0 upgrade note), Boost skill and `CLAUDE.md` describe the package as API + MCP, with Atrium v2 as the optional UI.

## Decisions

- **Atrium hosts this package's front end.** Atrium's v2 spec (`Atrium/docs/agent/specs/2026-11-22-1823-atrium-v2-headless`) ports this package's pages, widgets, search and settings into Atrium. Atrium registers them only when this package is installed (`Composer\InstalledVersions` plus `atrium.integrations.roster.enabled`). Atrium's Vue calls this package's JSON API through this package's generated client. Decided with the user, 2026-11-23.
- **This package keeps only its JSON API and MCP.** It deletes `src/Atrium`, its Blade views and its Atrium-only tests. It drops `refactor-circus/atrium` from `require-dev`, `suggest` and `conflict`, and drops `extra.atrium`. Nothing in `src/` names Atrium.
- **No knowledge of Atrium.** The suite rule still holds for this package: Keystone is the only suite `require`, and the code never names or detects another suite package. Atrium is the one package allowed to know the others.
- **API client.** OpenAPI comes from `dedoc/scramble` (`require-dev`), exported against the workbench into a committed `openapi.json`. The TypeScript client is generated with `openapi-typescript` plus `openapi-fetch` into a committed `client/` folder, with its own `package.json` (`@refactor-circus/roster-client`). A test or CI job fails when either is stale. Atrium's Nuxt layer pins the client as a git dependency on this package's v2 tag.
- **Missing data becomes API.** Anything the Blade UI computed in PHP that has no API route becomes a new endpoint here: widget aggregates (`GET {prefix}/stats`), search (`GET {prefix}/search?q=`), and a settings summary (`GET {prefix}/settings-summary`) where the package had a settings panel. These follow the package's existing Request/Action/Resource pattern and its policies.
- **Atrium-only config goes.** `roster.ui.enabled`, `roster.atrium.*` and the `RosterSupportFeature` move to Atrium's `atrium.integrations.roster.*`, owned by the Atrium spec.
- **Release v2.0.0.** This is a breaking change (Atrium UI removed). The CHANGELOG has an upgrade note pointing to Atrium v2.
- Config: Transfers use Impex classes (`StartImportAction`, `CancelTransferAction`). That cross-package reference is flagged and out of scope.

## Context

- **Inventory** (2026-11-23): the plugin, pages, widgets, search, settings and extensions are listed in `references.md`.
- **Order:** this spec waits for Keystone `2026-11-23-0943-drop-the-atrium-plugin-hook`, which removes `registerAtriumPlugin()` and the `ui.enabled` convention. Atrium's v2 integration groups wait for this package's v2.0.0 tag, because Atrium's layer pins the client to it.
- **Product docs:** none to align with.
- **Open for write-spec:** whether the package's API stays off by default (`<pkg>.routes.enabled`); the exact stats payload; and whether any package-to-package references flagged above become their own specs.

## Standards

- `http/routes`: new endpoints in the package's route group, named `roster.*`.
- `http/resources`: every new payload is a final resource.
- `http/request-persist`: new endpoints go through Requests and Actions.
- `http/authorization`: stats, search and settings summary authorize through policies.
- `architecture/dependencies`: no Atrium reference left; Keystone is the only suite require.
- `architecture/configuration`: removed Atrium keys; banner notes for the client.
- `testing/test-layers`: API wiring tests; behavior stays at the actions.
