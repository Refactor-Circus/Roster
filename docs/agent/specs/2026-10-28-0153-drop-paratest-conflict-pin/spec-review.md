# Spec review

## Round 1

Changes to spec.md (and shape.md):

- Stated that `composer.lock` is untracked (gitignored, `.gitignore` line 6), removing the "if tracked" wording. Why: the criterion had an unverifiable branch. The `^5.3.4` pest floor (which requires paratest `^7.27.4`) is the guard, with `composer show` and `composer why` as local evidence. The lock must not be committed.
- Merged the two `conflict` criteria into one checkable by `grep -c '"conflict"' composer.json`, and dropped the drifting line numbers from the design (and shape.md).
- Made the test criterion concrete: `composer test` exits 0, parallel run via test:unit, same test count as before, browser suite excluded.
- Replaced the vague "no other change" criterion with `git diff --stat` touching only `composer.json` with exactly the two edits.
- Added a failure path: on resolution failure, plugin bumps, or new-version test failures, stop and report; no widened scope or code workarounds.
- Reworded the lockfile-churn risk note to match.

Open questions were empty and left untouched.
