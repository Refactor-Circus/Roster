# Registered tabs on organization and user screens — Shaping Notes

## Scope

New feature, Atrium UI only. Host apps and other packages register extra tabs for the Atrium organization screen (`atrium.roster.organizations.show`) and user screen (`atrium.roster.users.show`), typically from a service provider. A registered tab has a key, a label, an icon, an optional access check, and a renderer (Blade view or component, plus a data resolver) that gets the record being viewed.

- Registered tabs appear after Roster's built-in tabs, only for viewers whose check passes, and open via `?tab=<key>`.
- A tab whose check fails is hidden. Asking for it with `?tab=` answers 403, like built-in tabs (D-007).
- Keys can't collide with or override Roster's built-in tabs (`members`, `teams`, `invitations`, `roles`, `sso`, `scim`, `mcp`, `activity`, `settings`; the user screen's default tab key). Registering a taken key fails loudly.
- The user screen has no tabs today. Once any user tab is registered, it gains a tab bar whose first tab is the current page content.
- Views use Atrium components and safelisted utilities only (StylesTest).
- Documented in README and the bundled Boost skill.

Out of scope: team/role/other screens (D-002), HTTP API and MCP equivalents (D-001), and reordering or hiding built-in tabs.

## Decisions

- D-001: UI-only (Atrium). Registered tabs render host content Roster doesn't own, so they get no HTTP/MCP counterparts. The roster/action-surfaces parity rule covers Roster actions, and this adds no action.
- D-002: Organizations and users only.
- D-003: Ticket created as drafted, GitHub #4.
- D-004: Standards list below confirmed.
- D-005: Registration through a dedicated `Atrium\Tabs` registry singleton (`app(Tabs::class)->organization(...)` / `->user(...)`), not the Roster facade or the plugin.
- D-006: A tab's access check is a Roster permission string checked in the record's scope (like built-in tabs, via `ScreenAccess::allows`) or a closure.
- D-007: `?tab=` for a registered tab the viewer can't see answers 403.
- D-008: The user screen's tab bar reuses the organization screen's tab bar look.

## Context

- **Organization screen**: `src/Atrium/Http/Controllers/OrganizationUiController.php:66` keeps a fixed `TABS` map (tab => permission), filters it with `offers()` + `ScreenAccess::allows()`, and authorizes a requested `?tab=`. Icons live in `resources/views/ui/organizations/show.blade.php:16`, and the tab bar (`x-atrium::button` per tab, `data-testid="tab-{name}"`) is at lines 17–24. Tab bodies are an `@if/@elseif` chain on `$tab`.
- **User screen**: `UserUiController::show()` renders one long `roster::ui.users.show` page of `x-atrium::card`s with no tab bar.
- **Builder pattern to borrow**: Atrium's `WidgetDefinition` (`Atrium/src/Domains/Widgets/Data/WidgetDefinition.php`): `make(key)`, fluent `label/icon/view/component/resolve/authorize`, `public private(set)` state, private closures. A tab definition could mirror it, which also fits data/value-objects and architecture/public-api.
- **Optional-package gating**: `RedirectDomains::available()` and `offers()` show how tabs are conditionally offered today.
- **Product**: the roadmap is complete; this is a host-app extension point and doesn't conflict with the mission (Roster works inside the host app's own model and UI).
- **Visuals**: none provided. The tab bar reuses the organization screen's existing look.

## Standards

Confirmed (D-004):

- architecture/public-api: new documented extension point; registration entry point, facade docblock, final classes.
- architecture/container-bindings: the tab registry is code state, so it's bound as a singleton in `register()`.
- data/value-objects: the tab definition is a value object with `public private(set)` state and fluent setters.
- architecture/naming: naming of the new classes.
- http/authorization: the tab check goes through Roster's permission check (`ScreenAccess::allows`), the same as built-in tabs.
- roster/atrium-views: tab views use Atrium components and safelisted utilities only.
- roster/action-surfaces: confirms no parity gap, since no action is added.
- roster/optional-surfaces: tabs only exist while `roster.ui.enabled`.
- roster/testbench-environment and testing/test-layers: feature tests for wiring, authorization and rendering.
- testing/arch-tests: new classes stay under the arch rules.
