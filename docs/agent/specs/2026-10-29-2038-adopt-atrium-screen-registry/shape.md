# Adopt Atrium screen registry — Shaping Notes

## Scope

Move Roster's screens onto Atrium's screen extensions registry (Atrium spec `2026-10-29-2037-screen-extensions-registry`), and remove every place where a Roster screen renders another package's UI because that package is installed. This is section A of the cross-package audit, for Roster.

In scope:

- **Delete Roster's tab registry.** Remove `Atrium\Tabs`, `TabDefinition`, `TabVisibility` and the `roster::ui.partials.tabs` / `registered-tab` partials. Registrations move to Atrium's registry under the screen keys `roster.organization` and `roster.user`. This is breaking, accepted per Atrium D-001.
- **Use Atrium's components on every Roster tab bar:**
  - organization: `members`, `teams`, `invitations`, `roles`, `settings`, plus `sso` / `scim` until the remove-sso-scim spec drops them
  - user: `overview` plus registered tabs
  - team: its existing tabs
  - SSO connection: its tabs, until the screen moves to Guardian
- **Make team and SSO connection screens registrable** under `roster.team` and `roster.sso-connection`.
- **Remove optional-package UI from Roster screens:**
  - The organization `activity` tab and `OrganizationUiController::offers()` match (Keen). History reaches the screen through Keen's capability section instead (Keen spec).
  - The organization `mcp` tab, the user overview redirect-domains card, `Atrium\RedirectDomains`, `RedirectDomainUiController`, its four routes (`*.redirect-domains.store|destroy`) and `ui/partials/redirect-domains.blade.php`, plus `tests/Cortex/RedirectDomainsTest.php`. All of these move to Cortex (Cortex spec).
  - The inline `<x-atrium::audit-trail>` on the role, team, user overview and organization screens. Each screen instead declares its audited subject (package `roster`; subject is the role, team or user; for organizations, `scope` is the organization).
- **Declare section slots** where removed UI used to sit, so registrants can place sections there. At minimum:
  - `roster.user` slot `overview.aside`, where the redirect-domains and SSO identities cards go
  - `roster.organization` tabs for MCP and activity

  Spec writing settles exact slot names.

## Decisions

- D-001: Roster depends on Atrium's registry only, and no longer references Cortex or Keen for screen UI. `composer.json` keeps `refactor-circus/cortex` and `refactor-circus/keen` in `suggest`, with updated wording.
- D-002: the built-in organization tab keys stay reserved as Atrium built-ins for `roster.organization`. `activity` and `mcp` stop being built-in, so Keen and Cortex can register them.
- D-003: the behaviour pinned by Roster PR #5 (criteria 1 to 18) is preserved through Atrium's registry. Its tests move to, or are re-expressed against, the Atrium API.
- D-004: SSO and SCIM stay in Roster for this spec, and only their screens switch to Atrium's components. The extraction is in `remove-sso-and-scim` and the Guardian spec.

## Context

- **Depends on:** Atrium `2026-10-29-2037-screen-extensions-registry`.
- **Must land together with:** the Cortex spec `register-roster-redirect-domains` and the Keen spec `history-section-by-capability`. Otherwise the MCP and activity features disappear from Roster screens until those merge. RingMaster links these as dependencies, and Cortex and Keen should merge before or with this spec.
- **Product:** Roster's mission is headless user management for the suite. Other packages should extend its screens, not be hard-wired into them.
- **References:** see `references.md`.

## Standards

- architecture/dependencies: removes Roster's `class_exists(CortexServiceProvider)` check and its Keen-gated UI.
- architecture/public-api: deleting `Tabs` / `TabDefinition` is a breaking public API change and needs a CHANGELOG upgrade note.
- architecture/domain-modules: where the remaining Atrium screen declarations sit in Roster.
- atrium/components: tab bars through Atrium components only (`StylesTest` stays green).
- http/authorization: `ScreenAccess::allows()` is the access check Roster passes to Atrium for registered tabs and sections.
- testing/test-layers: the feature suites for the organization, user, team and SSO screens keep passing, along with the browser suite.
