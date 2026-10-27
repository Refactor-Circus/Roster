# Let host apps register tabs on organization and user screens

## Problem

Roster's Atrium organization screen has a fixed set of tabs (`OrganizationUiController::TABS`: members, teams, invitations, roles, sso, scim, mcp, activity, settings), and the user screen has no tabs at all. An app that uses Roster has no way to show its own data in context, for example an "Orders" tab when looking at a user or an organization.

## Desired outcome

Other packages and the host app can register extra tabs for the organization screen and the user screen (for example from a service provider). Each registered tab has a key, a label, an icon, an optional permission/gate check, and something that renders its body for the record being viewed. Registered tabs appear next to Roster's own tabs, only for viewers allowed to see them, and open via the existing `?tab=` query parameter.

## Acceptance hints

- A public registration API (facade/`Roster` method or a registry) for organization and user tabs.
- A registered tab shows in the tab bar and renders its content for the current organization/user.
- A tab whose check fails is hidden and asking for it with `?tab=` falls back like today.
- Keys can't collide with or override Roster's built-in tabs (or the rule is explicit).
- The user screen gains a tab bar (its current content becomes the default tab) once any tab is registered.
- Views stay within Atrium components and safelisted utilities (StylesTest).
- Documented in the README and the bundled Boost skill.

## Out of scope

- Tabs on team, role or other screens.
- HTTP API / MCP equivalents: tabs are a UI-only extension point.
- Reordering or hiding Roster's built-in tabs.
