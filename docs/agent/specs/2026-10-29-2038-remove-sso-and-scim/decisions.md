# Decisions

Earlier D-ids are recorded in shape.md.

## D-008: Suite: proving the package runs without Atrium, and refusing an Atrium too old for the screen registry
- Choice: Prove it both ways: a WithoutAtrium suite inside `composer test`, and a CI lane with Atrium uninstalled. Every package that renders Atrium screens adds a composer `conflict` on refactor-circus/atrium versions older than the screen-extensions registry (Atrium PR #16).
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: user, cross-spec decision (2026-10-29)

## D-009: Suite: malformed integration config entries (screen maps, slots) at boot
- Choice: Throw InvalidArgumentException in local and testing. In production, report once, skip the entry and keep booting (same rule as Atrium D-022).
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: user, cross-spec decision (2026-10-29)

## D-010: Sequencing with Guardian
- Choice: Guardian uses a runtime handover gate plus a composer `conflict` once this removal is tagged. The upgrade note tells apps that installed Guardian early to run `guardian:upgrade-from-roster`.
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: user (2026-10-29)

## D-011: Q1 (D-003, open in shape.md): What does Roster show for a stored `scim` membership source when Guardian isn't installed?
- Choice: A: the raw stored value `scim` (existing seams behaviour for unregistered sources, no code)
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-012: Q2: Where does the password-login rule `NotSsoEnforced` go?
- Choice: A: Roster keeps a generic rule `Support\Rules\NoExternalSignIn` with message key `external_sign_in_required`
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-013: Q3: How do apps with published copies of migrations 000006/000007 and the two index migrations upgrade safely?
- Choice: A: rely on the CHANGELOG step (delete the two published copies, edit the index copies)
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-014: Q1. What does Roster show for a stored `scim` membership source when Guardian is not installed?
- Choice: A: the raw stored value `scim`
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-015: Q2. Where does the password-login rule `NotSsoEnforced` go?
- Choice: A: Roster keeps a generic `Support\Rules\NoExternalSignIn` rule with message key `external_sign_in_required`
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-016: Q3. How do apps with published copies of migrations 000006/000007 and of the two index migrations survive the removal of the `Tables::DEFAULTS` keys?
- Choice: A: rely on the bold CHANGELOG step (delete or edit the published copies)
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-017: Should Roster fail closed for SSO-enforced organizations when Guardian is absent?
- Choice: Accept fail-open and document it prominently in the CHANGELOG and README
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-018: Q3. How should apps with published copies of migrations 000006/000007 and the two index migrations be handled? Those copies call `Tables::name('sso_connections')` and similar, so they throw 'Undefined array key' once the keys leave `Tables::DEFAULTS`. They would also recreate empty `roster_sso_*` tables on `migrate:fresh`. The spec has no test for this regression, so the choice also decides whether anything can be tested.
- Choice: A: rely on the CHANGELOG step (delete the two published copies and edit the index copies), and add a DocsTest that the step is present and bold
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)
