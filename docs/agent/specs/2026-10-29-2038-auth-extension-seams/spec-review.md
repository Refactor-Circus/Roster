
## Round 1

Revised `spec.md` against the Round 1 findings. Open questions were left open and are listed under Risks as "Open:".

Changed:
- Goal: stated the seam count (resources and catalogue as two registries, membership sources, audit context, sign-in requirement) plus `provisionedStatus()` as a plain accessor.
- Criterion 1: added a prefix dataset (valid and invalid, boundaries) with message assertions, and a three-segment name case.
- Criterion 2: said how `parentOf()` resolves a registered model and added a fake-model test (pass, fail, other organization). Added deny-on-missing-policy (reported) and deny-on-unregistered, late-registration memo invalidation, and `ScreenAccess` / `@rosterCan` with registered permissions. SSO/SCIM policy registration moves to their providers and `config/roster.php` `policies` keeps core models only. Whether a policy gap is also a registration-time error stays open.
- Criterion 3: added tests for the existing-install upgrade, the command and migration paths, and exception messages naming the value. Sync warns about unregistered system permissions. README documents that existing built-in roles need manual grants.
- Criterion 4: narrowed to `MemberResource` and member list data (grep: no Atrium file reads `memberships.source`, so no Atrium change). Removed the conditional validation bullet (grep: no rule accepts a membership source). Output schema `source` becomes a plain string. `join()` takes `MembershipSource|string` validated against `MembershipSources`, `ScimUsers` passes `'scim'`. Listed the four existing `->source` assertions that change, in three files.
- Criterion 5: `report($e)` and `Exceptions::fake()`, container-held contributor list, no stored memo, keys validated whether or not auditing is on, Keen-enabled test setup, counter-closure test. Placement relative to `AuditHooks` left open.
- Criterion 6: lazy class-string resolution, duplicates ignored, registration order, `record` defaults to null, extra tests (super-admin, class-string, duplicates). Throw behaviour and `ssoRequiredFor()` fate left open, tests to follow the answer.
- Criterion 7: invalid stored status falls back to `active`; added direct unit tests.
- Criteria 8-10: arch test extended to `ManagesMemberships`, `MemberResource` and `MembershipSource::Scim` callers, plus literal grep test; docs test checks snippet class names exist; criterion 10 is now checkable (named test diff only).
- Out of scope: added the SSO/SCIM MCP tools and the unchanged `roster.sso.*` / `roster.scim.*` names; Atrium bullet now states no Atrium file changes.
- Risks: rewritten as "Open:" items, with the "spec review decides" wording removed.

Why: each finding was a scope contradiction, an untestable clause or an unspecified failure behaviour. Where a finding's fix depended on a user decision, the spec states the requirement that holds either way and marks the choice open.


## Round 1 (revision against the second set of findings)

Revised `spec.md`. The open questions were left undecided and are all listed under Risks as "Open:": screen slots and tab bars, registering under the core `roster.` prefix and impersonation-blocked coverage, reporting an invalid `provisionedStatus()` value, who catches a throwing audit contributor, `->value` versus `sourceEnum()` in the four assertions, and which call site throws for a policy gap.

Changed:
- Folded the addendum (D-004 to D-009) into the body and turned the addendum into a change log. Goal now counts four Roster registries plus the `provisionedStatus()` accessor, and says audit context is not a Roster seam. Why: the body and addendum contradicted each other.
- Criterion 5 rewritten: no `Audit::context()`; the SCIM domain registers `scim_token` on `AuditHooks` at the same moment as `Support\Audit`, only when auditing, only for Roster events. Checked `../Keystone/src/Audit/AuditHooks.php`: `context()` already takes a closure from any package, so no Keystone change is needed; `contextFor()` has no try/catch, which is why the catcher is an open question. Tests added: existing SCIM test unchanged, non-Roster entry gets no `scim_token`, throwing contributor reported. Why: removes stale text and the hidden Keystone dependency.
- Criteria 1 and 2: policy gap now throws `InvalidArgumentException` (D-008), no `report()` wording, never converts to allow, memo stores nothing for a throwing check; `ScreenAccess` and `@rosterCan` test added. Late registration: `Resources` has its own `generation()`, the memo keys on it plus `Permissions::generation()`.
- Criterion 3: added the unregistered-system-permission warning test and a boot-order migration test through `migrate` with the domain providers registered.
- Criterion 4: `sourceEnum()` and its tests, duplicate registration test, consumer audit (arch/grep over `src/`), CHANGELOG naming `sourceEnum()` and the silent-false hazard, raw display of unregistered values noted.
- Criterion 6: `ssoRequiredFor()` removed; `externalSignInFor()` does not catch; super-admin check runs before any requirement; call-site listing; tests for propagation and the `NotSsoEnforced` result. Dropped "report() is called either way".
- Criterion 7: named the remap example, the null case and the fallback (`Statuses::user()` resolution of `active`).
- Criterion 8: reworded to "core references neither domain", exact file list, word-bounded patterns, no exceptions.
- Criterion 9: docs strings now `AuditHooks`, plus the built-in-role sentence; the resource and catalogue snippets are executed in a test.
- Criterion 10: rule stated by behaviour, changes listed by file and test name, no line numbers; includes the SsoWiringTest rewrite and the SCIM audit test.
- Design, Out of scope, Risks: Design bullets updated; Out of scope names `adopt-atrium-screen-registry` for Atrium work and points to the open screen-slot question; the hidden scope line in Risks removed; resolved risks deleted.

Not changed: whether to add screen slots/tabs here, since the user request and an open question hinge on it.
