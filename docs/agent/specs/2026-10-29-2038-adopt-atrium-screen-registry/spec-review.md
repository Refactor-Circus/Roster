# Spec review

## Round 1

Changed in `spec.md`, and why. Q1, the invitation page and banner, and the reserved-key question were left alone because their answers are still open.

- **Goal, Out of scope:** goal now says "every dashboard list and detail screen in the criterion 3 table" and points to "Not declared". Template download added to Out of scope. Why: the goal overstated scope.
- **Impex (crit 15, Out of scope):** stated that Impex-less boot is guarded only by the lane and the `TransfersUnavailable` mode, with no extra criterion. Why: avoids a hidden second optional-dependency requirement.
- **Crit 1, 8, 11:** grep done-steps became file-content scan tests; CHANGELOG qualifier dropped.
- **Crit 2:** order-independent reset proof, with one case under `tests/Authorization`.
- **Crit 3:** empty-state assertion is per row; `roster.organization` asserts exactly the built-in `tab-*` ids. Why: the old assertion contradicted criterion 6.
- **Crit 4:** null record keeps the global check; an unknown model class now denies. Eager-loading, query counting, null-organization and null-requester tests added. Why: N+1 and a permissive default.
- **Crit 5:** refused-viewer proof is a dataset over every detail screen.
- **Crit 6:** report observed with `Exceptions::fake()`; data loading observed by query counting by table.
- **Crit 8:** translation keys listed exactly, with a test.
- **Crit 9:** "unchanged" defined as HTML equal to the no-registration response; redirect named (user update).
- **Crit 10:** dropped the external PR #5 range.
- **Crit 12:** test also covers `require-dev` and the exact suggest strings.
- **Crit 14:** states the in-suite mode only proves the provider is unregistered; adds a local scan test for stray `RefactorCircus\Atrium` strings; names the CI job as the real proof.
- **Crit 15:** removes every dev package that requires Atrium (Keen checked), and asserts `class_exists` is false before Pest.
- **Crit 18:** DocsTest checks every screen key, built-in tab keys and the `allowsOn` mapping.
- **Crit 19:** upgrade note opens with the Atrium requirement and lists `atrium.roster.*` routes as unavailable without it.
- **New crit 21:** merge gate. Atrium PR #16, Cortex `register-roster-redirect-domains` and Keen `history-section-by-capability` are hard blockers in `meta.yml`/RingMaster and confirmed in the PR description; no data is lost because Roster stores none.

### Not changed (depend on open questions)

- Findings on criteria 13, 14 and 16 and the "pending Q1" wording, and the impersonation banner test with Atrium absent: need the Q1 answer.
- Reserved-key hole for a registered tab named like a built-in the viewer cannot open: the fix choice is an open question.
- Same-PR versus split, and the Atrium-version guard: open questions.
