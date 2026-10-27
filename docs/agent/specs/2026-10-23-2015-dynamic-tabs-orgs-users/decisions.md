# Decisions

## D-001: Scope of the tab extension point

- **Question:** Is this extension point UI-only, or should registered tabs also have HTTP/MCP counterparts?
- **Choice:** UI-only (Atrium)
- **Rationale:** No note given; answered in RingMaster. Tabs render host-app content Roster doesn't own, so roster/action-surfaces parity (which covers Roster actions) does not apply.
- **Date:** 2026-10-24
- **Raised by:** ticket (answered in RingMaster)

## D-002: Screens that accept registered tabs

- **Question:** Which screens should accept registered tabs?
- **Choice:** Organizations and users only
- **Rationale:** No note given; answered in RingMaster. Matches the idea, which names only users and organizations; teams stay out of scope.
- **Date:** 2026-10-24
- **Raised by:** ticket (answered in RingMaster)

## D-003: Ticket draft

- **Question:** Create the GitHub issue as drafted in ticket-draft.md ("Let host apps register tabs on organization and user screens")?
- **Choice:** Create as drafted
- **Rationale:** No note given; answered in RingMaster.
- **Date:** 2026-10-24
- **Raised by:** ticket (answered in RingMaster)

## D-004: Standards for this spec

- **Question:** Use these standards for this spec? architecture/public-api, architecture/container-bindings, data/value-objects, architecture/naming, http/authorization, roster/atrium-views, roster/action-surfaces, roster/optional-surfaces, roster/testbench-environment, testing/test-layers, testing/arch-tests
- **Choice:** Use this list
- **Rationale:** No note given; answered in RingMaster.
- **Date:** 2026-10-24
- **Raised by:** shape (answered in RingMaster)

## D-005: Where host apps register tabs

- **Question:** Where do host apps register tabs?
- **Choice:** A dedicated Atrium\Tabs registry singleton (app(Tabs::class)->organization(...))
- **Rationale:** No note given; answered in RingMaster. Keeps the Roster facade to runtime lookups.
- **Date:** 2026-10-24
- **Raised by:** shape (answered in RingMaster)

## D-006: How a registered tab declares who may see it

- **Question:** How does a registered tab declare who may see it?
- **Choice:** Roster permission string checked in the record's scope (like built-in tabs) or a closure
- **Rationale:** No note given; answered in RingMaster.
- **Date:** 2026-10-24
- **Raised by:** shape (answered in RingMaster)

## D-007: Requesting a hidden registered tab

- **Question:** Asking for a registered tab the viewer can't see with ?tab= should...
- **Choice:** Answer 403 like built-in tabs
- **Rationale:** No note given; answered in RingMaster. Matches OrganizationUiController's handling of built-in tabs.
- **Date:** 2026-10-24
- **Raised by:** shape (answered in RingMaster)

## D-008: User screen tab bar look

- **Question:** Any mockups for the user screen's new tab bar, or reuse the organization screen's tab bar look?
- **Choice:** Reuse the organization tab bar
- **Rationale:** No note given; answered in RingMaster.
- **Date:** 2026-10-24
- **Raised by:** shape (answered in RingMaster)

## D-009: Tab key format and future built-in keys

- **Question:** Should tab keys be constrained, and how are future built-in keys handled for existing host apps?
- **Choice:** Validate keys against a safe pattern (lowercase, digits, dash, underscore, dot) and document that new built-in keys may be added in minor releases (recommend vendor.key)
- **Rationale:** No note given; answered in RingMaster. Keys go into `?tab=`, `data-testid` and URLs; a `vendor.key` prefix keeps hosts clear of later built-ins.
- **Date:** 2026-10-24
- **Raised by:** spec-review (answered in RingMaster)

## D-010: Resolver data versus Roster's view variables

- **Question:** Can a resolver's data override Roster's view variables (organization, user, tab, tabs)?
- **Choice:** Roster's keys always win; host data spread beneath them
- **Rationale:** No note given; answered in RingMaster. Keeps the shared tab bar intact whatever a resolver returns.
- **Date:** 2026-10-24
- **Raised by:** spec-review (answered in RingMaster)

## D-011: ?tab= on a deleted organization or user

- **Question:** When ?tab= names a registered tab on a deleted organization or user, what is the response?
- **Choice:** Render the deleted page and ignore ?tab=
- **Rationale:** No note given; answered in RingMaster. Matches today's `show()`, which returns the deleted view before any tab logic.
- **Date:** 2026-10-24
- **Raised by:** spec-review (answered in RingMaster)

## D-012: Visibility of TabDefinition's closures

- **Question:** Should TabDefinition's resolver and authorize closures be readable from outside, or only used through resolveData() and allows()?
- **Choice:** Private; reachable only via resolveData() and allows()
- **Rationale:** No note given; answered in RingMaster. Matches Atrium's WidgetDefinition.
- **Date:** 2026-10-24
- **Raised by:** spec-review (answered in RingMaster)

## D-013: ?tab= with an unknown key

- **Question:** What should ?tab= do for a key that is neither built-in nor registered, on the organization and user screens?
- **Choice:** Fall back to the default tab (today's organization behaviour)
- **Rationale:** No note given; answered in RingMaster. Matches OrganizationUiController, which falls back to the first allowed tab.
- **Date:** 2026-10-24
- **Raised by:** spec-review (answered in RingMaster)

## D-014: User screen with registered but hidden tabs

- **Question:** On the user screen, when tabs are registered but none is visible to this viewer, is there a tab bar?
- **Choice:** No tab bar; page unchanged
- **Rationale:** No note given; answered in RingMaster.
- **Date:** 2026-10-24
- **Raised by:** spec-review (answered in RingMaster)

## D-015: Host closure, resolver or view errors

- **Question:** What happens when a host's authorize closure, resolver or view throws or is missing?
- **Choice:** Treat an authorize exception as denied (tab hidden) and report it; resolver/view errors propagate
- **Rationale:** No note given; answered in RingMaster. One faulty authorize closure can't break Roster's own screens, since visibility runs on every show().
- **Date:** 2026-10-24
- **Raised by:** spec-review (answered in RingMaster)

## D-016: Verifying the user overview isn't loaded on a registered tab

- **Question:** How should tests verify the user screen's overview data is not loaded when a registered tab is open (criterion 12)?
- **Choice:** Assert via DB::listen that the overview's tables are not read
- **Rationale:** No note given; answered in RingMaster. Needs no new loader seam in the controller.
- **Date:** 2026-10-24
- **Raised by:** spec-review (answered in RingMaster)

## D-017: Unknown icon name on a registered tab

- **Question:** How should an unknown icon name on a registered tab be handled (criterion 6)?
- **Choice:** Roster checks the name against Atrium's icon set at render and falls back to squares-2x2, tested directly
- **Rationale:** No note given; answered in RingMaster. `Atrium\Support\Icons::svg()` throws for an unknown name and `Icons::has()` exists, so the fallback keeps the screen rendering.
- **Date:** 2026-10-24
- **Raised by:** spec-review (answered in RingMaster)

## D-018: Where per-request tab state lives

- **Question:** Where does the per-request state for tab visibility and once-per-request exception reporting live?
- **Choice:** A scoped container binding (Octane-safe) holding the memo, with a two-request test
- **Rationale:** No note given; answered in RingMaster. `Tabs` is an app-lifetime singleton, so a memo on it would leak visibility across viewers; a `scoped()` binding is reset per request.
- **Date:** 2026-10-24
- **Raised by:** spec-review (answered in RingMaster)

## D-019: Documentation test scope

- **Question:** Should the documentation-grep test (criterion 17) stay as written, or be trimmed?
- **Choice:** Reduce the doc test to a short string list (Tabs::class, vendor.key, boot()) inside a "Registered tabs" README/skill section
- **Rationale:** No note given; answered in RingMaster. Strings like `403` and `authorize` already appear elsewhere in the README, so the longer list could pass vacuously.
- **Date:** 2026-10-24
- **Raised by:** spec-review (answered in RingMaster)

## D-020: User screen redirects

- **Question:** Should the user screen's redirects add tab=overview when a visible user tab exists (criterion 13)?
- **Choice:** No, leave redirects unchanged; overview is already the default when tab is absent
- **Rationale:** No note given; answered in RingMaster. Also keeps host `authorize` closures off every POST redirect.
- **Date:** 2026-10-24
- **Raised by:** spec-review (answered in RingMaster)

## D-021: Checking that a registered tab's view or component exists

- **Question:** Should Roster check that a registered tab's view or component exists?
- **Choice:** No check; a missing view or component fails when the tab renders (D-015 as written)
- **Rationale:** No note given; answered in RingMaster. Registration happens in host `boot()` and `loadViewsFrom` can run later, so a registration-time check gives false errors.
- **Date:** 2026-10-24
- **Raised by:** spec-review (answered in RingMaster)

## D-022: Consumers who published Roster's views

- **Question:** How do consumers who published Roster's views (roster-views tag) get registered tabs?
- **Choice:** Document that they must re-publish or merge the views; overridden views don't get registered tabs
- **Rationale:** No note given; answered in RingMaster. README and a CHANGELOG upgrade note carry it.
- **Date:** 2026-10-24
- **Raised by:** spec-review (answered in RingMaster)

## D-023: Tabs::ORGANIZATION_TABS as public API

- **Question:** Should `Tabs::ORGANIZATION_TABS` be public API, and does it hold only the reserved keys or the key => permission map the controller uses?
- **Choice:** Public constant of keys only; the controller keeps a private permission map keyed by it, with a test that the key sets match
- **Rationale:** No note given; answered in RingMaster.
- **Date:** 2026-10-24
- **Raised by:** spec-review (answered in RingMaster)

## D-024: Default visibility of a registered tab

- **Question:** What is the default visibility of a registered tab that declares neither permission() nor authorize()?
- **Choice:** Default-deny: a tab must call permission(), authorize() or an explicit public() to be shown
- **Rationale:** No note given; answered in RingMaster. A forgotten check would otherwise expose host data to every viewer of the screen.
- **Date:** 2026-10-24
- **Raised by:** spec-review (answered in RingMaster)
