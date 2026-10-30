# Decisions

Earlier D-ids are recorded in shape.md.

## D-025: Suite: proving the package runs without Atrium, and refusing an Atrium too old for the screen registry
- Choice: Prove it both ways: a WithoutAtrium suite inside `composer test`, and a CI lane with Atrium uninstalled. Every package that renders Atrium screens adds a composer `conflict` on refactor-circus/atrium versions older than the screen-extensions registry (Atrium PR #16).
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: user, cross-spec decision (2026-10-29)

## D-026: Suite: malformed integration config entries (screen maps, slots) at boot
- Choice: Throw InvalidArgumentException in local and testing. In production, report once, skip the entry and keep booting (same rule as Atrium D-022).
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: user, cross-spec decision (2026-10-29)

## D-027: How do the invitation page and the impersonation banner work without Atrium (spec Q1)? Both live outside src/Atrium and use x-atrium components.
- Choice: Option B: a Roster-owned RefactorCircus\Roster\Atrium\AtriumServiceProvider, registered by RosterServiceProvider only when class_exists('RefactorCircus\Atrium\Atrium'). It points the invitation page and banner at Atrium views, declares roster.invitation and registers @rosterCan. Without Atrium, plain views render.
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-028: Should the optional-Atrium work (suggest move, AtriumUnavailable mode, CI lane, arch rules) ship in the same PR as the screen-registry adoption, or be split into a follow-up spec?
- Choice: One spec and PR, as written.
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-029: Q1: How do the invitation page and the impersonation banner work without Atrium? (decisions.md does not exist, so this is still open and criteria 14 and 16 depend on it.)
- Choice: Option B: a Roster-owned seam registered only when Atrium exists
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-030: Should Roster guard against an installed Atrium that predates the screen registry (Atrium PR #16)?
- Choice: Add a Composer `conflict` or minimum version for `refactor-circus/atrium` so older versions are refused
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-031: How should a registered tab whose key equals a built-in the viewer cannot open be treated on `roster.organization`?
- Choice: Reserve all built-in keys for every viewer, so the tab is skipped and reported and never shown
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-032: How do the invitation page and impersonation banner work without Atrium (Q1 in the spec)? The answer fixes what criteria 13, 14 and 16 can test.
- Choice: Option B: a Roster-owned `Roster\Atrium\AtriumServiceProvider`, registered only when the Atrium class exists. It points the invitation page and banner at Atrium views, declares `roster.invitation` and registers `@rosterCan`. Without it, plain views render
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)
