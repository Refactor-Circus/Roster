## Round 1

Revised spec.md against the round 1 findings. Open questions were left untouched and not decided: unknown `?tab=` on the user screen, user default when all tabs are hidden, behaviour when host closures or views throw or are missing, key format and future built-in keys, resolver data overriding Roster variables, the `?tab=` response on deleted records, and whether the resolver/authorize closures are readable. Criteria that touch them say so and defer to the open questions.

Changed:
- Acceptance criteria rewritten and renumbered (18). The old 14 became a required-tests list (criterion 18) and "composer test" folded into the definition of done. Why: restated gates did not serve the goal and named no tests.
- Criterion 1: singleton bound in `register()`, registration from `boot()` read lazily at render, guidance against request-time registration. Why: timing and Octane state-leak findings.
- Criterion 2: listed observable assertions (defaults, view/component clearing, outside write throws `Error`). Why: testability.
- Criterion 3: reserved keys driven from the controller's constant with a unit test, message names the key, conditional built-ins (`activity`, `mcp`) stay reserved.
- Criteria 4 and 5 (new): `flush()` for tests, no runtime unregister; registration validates and stores with the UI disabled.
- Criteria 6 to 8: escaped label and icon, order with a hidden middle tab, counting-resolver technique and named test file, resolver runs only after the access check, default tab from visible built-in tabs only with the `settings` fallback.
- Criteria 9 and 10: access matrix on both screens, unknown permission hidden, visibility evaluated once per request, unknown key on the organization screen keeps today's fallback with a regression test.
- Criteria 11 to 13: concrete no-tab-bar assertion including the all-hidden case, `overview` holds all current content, existing user tests pass with a tab registered, overview data not queried when a registered tab is open, user redirects keep `tab=overview` while the bar shows.
- Criteria 14 and 15: shared partial made observable (same test ids and active marker); organization markup, ids and URLs unchanged, existing tests unmodified.
- Criterion 16: deleted pages render no registered tabs and run no resolver; the `?tab=` response stays an open question.
- Criterion 17: docs promises checked by a grep test over README and the Boost skill.
- Design: default tab and single visibility evaluation, resolver after access check, `flush()`, `overview` content and redirects.
- Out of scope: added badges, grouping, tab routes, label translation, resolver caching, runtime deregistration.
- Risks: added the mutable-singleton note.

Not changed, because the open questions depend on them: key-format regex and future built-in key policy, exception handling for host closures and views, resolver-variable collision rules, user-screen unknown-key behaviour, deleted-record `?tab=` response, closure readability.

### Round 1, second revision (findings after the addendum)

Revised spec.md. The open question (extracting a named overview-data seam, criterion 12) is left undecided and now listed under Risks and open questions; criterion 12's wording is unchanged.

Changed:
- Folded D-009 to D-015 into the criteria (2, 3, 7, 9, 10, 11, 16) and removed the "open question below" phrases; the Addendum is now a changelog pointing at the criteria. Why: top-down readers saw unresolved scope.
- Criterion 11 holds one rule for the user screen: bar and overview wrapper exist iff at least one user tab is visible; `?tab=overview`, empty and unknown keys render the unchanged page otherwise. Criterion 13 applies the same condition to the redirect target (evaluated when the redirect is built) and asserts the URL with and without the bar. The user suite runs as a dataset over "no tab" and "one visible tab".
- Criterion 10: `?tab=` read as a string only; non-string values fall back to the default tab (200, no resolver), tested on both screens.
- Criterion 6: an unknown icon must not break the screen (check Atrium's icon component, fall back to `squares-2x2` at render if needed) with a test; ordering asserted against exact test ids plus registered tabs after built-ins. I could not inspect Atrium's icon component in this run, so that behaviour check is left to implementation.
- Criterion 7 and Design: the partial's variable set is stated (`organization`/`user`, `tab`, `tabs`, `registeredTab`, `errors`), Roster's keys last; the D-010 test covers `registeredTab` and `errors`.
- Criterion 3 and Design: `OrganizationUiController::TABS` is a private const (grep), so moving it breaks no consumer; the controller's constant is removed and reads `Tabs::ORGANIZATION_TABS`, with a test that the built-in bar matches the constant. Conditional built-ins stay reserved.
- Criterion 4: UI test case flushes `Tabs` in `beforeEach`/`afterEach`; a unit test pins flush. Criterion 1: registration belongs in `boot()`, not `register()`.
- Criterion 9: `report()` once per request per tab key, tested with `Exceptions::fake()`, with 200 and absence from the bar asserted; README documents cheap, non-throwing `authorize` closures.
- Criterion 14: the active marker is named (`variant="primary"` and `aria-current="page"` on `x-atrium::icon-button`), asserted on response HTML.
- Criterion 16: deleted record with `?tab=` answers 200 deleted page; counting closures assert none run.
- Criterion 17: the doc test greps exact strings (`Tabs::class`, reserved keys plus `overview`, `boot()`, `vendor.key`, `403`, `authorize`) in README and Boost skill, not prose; D-009 and D-015 promises added to the README list.
- Goal, Design and Out of scope: named the shared-partial extraction as in scope; added config/auto-discovered registration, sandboxing host code and deleted-page tabs to Out of scope.

### Round 1, third revision (scope, risk and testability findings)

Revised spec.md. The six open questions were not decided; each is now listed under Risks and open questions.

Changed:
- Goal: names the user-screen tab bar with `overview` wrapper and the `tab=overview` redirect change as in scope (the redirect follows from the wrapper). Criteria 12 and 17 stay in scope for now; whether they stay is an open question.
- Out of scope: added facade/plugin registration (D-005), other routes and the dashboard nav, audit/Keen events, Cortex/MCP redirect domains, Octane re-registration.
- Criterion 3: all-digit keys become int array keys, so keys are cast with `(string)` on read-back; test with `123` and `007`.
- Criterion 6: verified Atrium's `Icons::svg()` throws `InvalidArgumentException` for an unknown name and `Icons::has()` exists; removed the conditional wording and ruled out try/catch around rendering. Whether Roster must fall back to `squares-2x2` is left open.
- Criterion 7: resolver data goes to components as one `data` prop; reserved view names documented; test with `slot`, `attributes` and a dashed key. Added authorization-first rule: the screen's own 403/404 runs before any closure or resolver, with a zero-call test on both screens.
- Criterion 9: visibility results and reported keys never live on `Tabs` or `TabDefinition`; two-request test with different viewers; `allows()` gets the request whatever its method. The location of the state is left open.
- Criterion 12: the control request must read the same table and the test fails otherwise.
- Criterion 13: dataset over every user route that redirects back; POST test with a throwing closure.
- Criterion 14: dropped the untestable variant claim; one fixture feeds both screens.
- Criterion 15: states that the browser suite is part of done.
- Criterion 17: the string checks apply only inside a `Registered tabs` section.
- Risks: unguarded tabs, missing view only failing on open, `vendor.key` collision at boot, published views.

Not changed, because the open questions depend on them: whether registration validates view/component existence, how published-view consumers get registered tabs, and the icon fallback mechanism.

### Round 1, fourth revision (scope, risk and testability findings)

Revised spec.md. Left alone, as instructed: whether `Tabs::ORGANIZATION_TABS` is public API and its shape (keys only or key => permission), and the default visibility of a tab with neither `permission()` nor `authorize()`. Criterion 3/15 wording on the constant is unchanged.

Changed:
- Goal: stated as one outcome (hosts register tabs, shown only to permitted viewers). Enablers listed separately: shared tab-bar partial and the user `overview` wrapper only. Removed the stale `tab=overview` redirect clause; the Goal now says redirects are unchanged (D-020, criterion 13). Why: it contradicted D-020 and an implementer reading only the Goal would build the rejected behaviour.
- Out of scope: "Lazy loading" reworded to browser lazy/async loading, with criterion 12 named as the only server-side skipping. Added ordering controls and registered tabs as a screen default. Why: removes an apparent contradiction and a missing boundary.
- Criterion 3: duplicate-key exception names key and screen; `Tabs` stores a clone of the definition so later fluent mutation cannot bypass validation, with a test; numeric-key check uses `assertViewHas` with a strict string comparison. Why: risk and testability findings. Risks section now notes the diagnosable message.
- Criterion 6: unknown-icon fallback observable is the response HTML equal to a `squares-2x2` tab's icon markup.
- Criterion 9: closures run lazily (zero calls when no tab is registered on the screen, after screen authorization); memo is keyed by screen, record key and tab key; tests reset scoped instances with `app()->forgetScopedInstances()` because Testbench does not reset them between requests; the report test counts closure calls as well as reports. Why: otherwise the memo test could pass vacuously and could leak across screens.
- Criterion 12: names the overview-only table (SSO identities via `ListSsoIdentitiesAction`, through `Tables::name('sso_identities')`).
- Criterion 13: adds a failing-validation post.
- Criterion 14: `aria-current` is the only active-state signal; added a characterization test of the organization bar (exact testids in order, `?tab=` hrefs) run before and after extraction.
- Criterion 15 and criterion 18: looked up composer.json; `composer test` runs `--exclude-testsuite=Browser`, so `composer test:browser` is part of done and the conditional is gone.
- Criterion 17: section boundary defined (heading to next heading of equal or higher level); other README promises are manual review; CHANGELOG note names the Atrium version providing `Icons::has()`.

Not changed: the optional suggestion to tighten the key pattern to require a leading letter (it would drop the numeric-key criterion). It changes D-009, so it is left as a later decision; the scope-growth observation about the numeric-key, Octane, dataset and doc tests is noted but those tests stay. Criterion 3 was not split into separate items, only extended.

## Last questions (2026-10-24)

Answered in RingMaster as D-023 and D-024 and applied to spec.md; the user then approved the spec.

- **Q-023: Should `Tabs::ORGANIZATION_TABS` be public API, and does it hold only the reserved keys or the key => permission map the controller uses?**
  - Options: public constant of keys only, controller keeps a private permission map keyed by it with a test that the key sets match; public constant mapping key => permission; `@internal` constant plus a documented `Tabs::reserved()` method.
  - Critic recommendation: public constant of keys only, private permission map, key-set test.
  - Evidence: criterion 3 has the controller read built-in tabs from `Tabs::ORGANIZATION_TABS`, but the old private `OrganizationUiController::TABS` was a tab => permission map (shape.md Context).
- **Q-024: What is the default visibility of a registered tab that declares neither `permission()` nor `authorize()`?**
  - Options: visible to anyone who can open the screen (current spec; README warns); default-deny, a tab must call `permission()`, `authorize()` or an explicit `public()`; throw at registration if none is set.
  - Critic recommendation: default-deny.
  - Evidence: criterion 9 and Risks; a forgotten check exposes host data to every viewer of the screen, and the API is new.
