# Spec review

## Round 1

Revised `spec.md` for the Round 1 findings. Open questions Q1 (raw `scim` source), Q2 (recommendation A, `NoExternalSignIn`), Q3 (recommendation A, CHANGELOG step) and the fail-closed question are unchanged. Fail-closed is recorded as Q4 and no guard was added.

- Criterion 2: added a positive control (other context keys present) to the `scim_token` test, and accepted that old Keen entries keep their `scim_token` key, with a render test.
- Criterion 3: the permission-sync test runs every command option, so prune/force flags cannot delete the rows. Why: silent grant loss would break D-001 and D-004.
- Criterion 4: added write, import and filter paths for a stored `scim` source.
- Criterion 5: named the observable (the `Surface` registry content) and added a route-table test over all names and URIs, covering the `roster.sso.`, `atrium.roster.sso` and `roster.scim.` prefixes.
- Criterion 6, 7, 9: tied the maps, tab bar and banners to observables or to the criterion 12 grep.
- Criterion 10: the upgrade test uses a fixture copied verbatim from the deleted migrations, runs rollback and refresh, and checks that a stray `migrations` row is harmless. The loose drop/rename sentence became the one strict grep.
- Criterion 11: added vendor-namespace greps (`Laravel\Socialite`, `SocialiteProviders`, `Firebase\JWT`), and noted that `composer validate --strict`, `composer update` and `composer analyse` are run manually or in the gate.
- Criterion 12: replaced the regex with a case-sensitive one that catches camelCase, PascalCase and upper-case names without matching "association". Stated the scanned set exactly and which files other criteria cover. The allow-list is empty under Q2's recommendation.
- Criterion 13: pinned the message text (it contains no sso/scim token, so it can stay) and named the seams registration call the fake uses.
- Criterion 14: bold warning that SSO-enforced organizations accept passwords without Guardian, bold step 2 "before running any migrate command", step 3 before `roster:sync-permissions`. The DocsTest now asserts inside the `### Breaking` slice, the ordered steps and the bold.
- Criterion 15: added `CLAUDE.md` and `AGENTS.md` updates, decided the roadmap gets a one-line note (Out of scope now matches), and added README/SKILL.md assertions. `ringside sync` is marked manual.
- Criterion 16: marked the no-weakening rule as verified by review, added a test-count and threshold requirement, and stated that the only expected browser-test edits are the two deletions.
- Risks: deploy ordering now points to the bold warning and Q4.
