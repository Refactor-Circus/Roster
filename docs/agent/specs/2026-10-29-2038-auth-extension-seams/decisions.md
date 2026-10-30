# Decisions

D-001 to D-003 are recorded in `shape.md`.

## D-004: Remove Roster::ssoRequiredFor() now, or keep it as a deprecated wrapper?
- Choice: Remove it now, with a CHANGELOG upgrade note
- Rationale: Accepted the recommendation. A wrapper would keep an SSO type in Roster core.
- Date: 2026-10-29
- Raised by: spec review (scope, risk, testability)

## D-005: Does app code still get an enum from memberships.source after it becomes a string cast?
- Choice: A string cast plus an enum helper on MembershipModel, `sourceEnum(): ?MembershipSource`, which returns the built-in enum case or null for registered sources
- Rationale: The user chose to keep enum access for built-ins.
- Date: 2026-10-29
- Raised by: spec review (risk, testability)

## D-006: Where do other packages add audit context to Roster entries?
- Choice: Directly on Keystone's `AuditHooks`, filtered to `roster` package entries. Roster adds no audit API (`Audit::context()` is dropped from this spec). Roster's in-tree SCIM domain moves its `scim_token` context onto AuditHooks the same way. The `impersonator` and `transfer` contexts stay in `Support\Audit`.
- Rationale: Accepted the recommendation. Roster's CLAUDE.md forbids audit APIs in Roster, and Keystone is the only shared requirement (Atrium D-017/D-018).
- Date: 2026-10-29
- Raised by: spec review (scope)

## D-007: What happens when a registered SignInRequirement throws?
- Choice: Fail closed: rethrow, so the login fails and the exception is reported
- Rationale: Accepted the recommendation. This is a security control.
- Date: 2026-10-29
- Raised by: spec review (risk)

## D-008: What happens when a registered resource's model has no Gate policy?
- Choice: Throw `InvalidArgumentException` naming the resource the first time a permission resolves to it
- Rationale: Accepted the recommendation. Policies register in boot(), so the check can't happen when the resource is registered.
- Date: 2026-10-29
- Raised by: spec review (risk)

## D-009: How is a throwing audit-context contributor reported?
- Choice: Laravel `report($e)`. The entry is written without that key.
- Rationale: Accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review (testability)

## D-010: May other packages register under the roster. prefix?
- Choice: No. `roster.*` is reserved for Roster's own domains (including the in-tree `roster.sso` and `roster.scim` until they move to Guardian), and anyone else registering it throws. The impersonation-blocked permission list stays core-only, as documented.
- Rationale: Accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review recheck (risk)

## D-011: Invalid stored provisioned_status
- Choice: Fall back to active and call `report()`
- Rationale: Accepted the recommendation. A silent fallback to active is a security risk.
- Date: 2026-10-29
- Raised by: spec review recheck (risk)

## D-012: Who catches a throwing SCIM audit-context closure?
- Choice: The SCIM domain wraps its own closure (try/catch, `report($e)`, return null). Testable in Roster with no Keystone change.
- Rationale: Accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review recheck (testability)

## D-013: Where does the missing-policy exception surface?
- Choice: In `Authorizer::resolvePolicy()`, on first resolution, so `check()`, ScreenAccess and `@rosterCan` all surface it
- Rationale: Accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review recheck (testability)

## D-014: Screen slots and tab bars in this spec?
- Choice: Out of scope here. They belong to `adopt-atrium-screen-registry` (Atrium D-013).
- Rationale: Already settled by Atrium D-013.
- Date: 2026-10-29
- Raised by: spec review recheck (scope)

## D-015: Rewrite in place
- Choice: Rewrite the Goal, criteria, Design and Risks in place; keep the addendum as a changelog
- Rationale: Same choice the user made for the Atrium spec (Atrium D-020).
- Date: 2026-10-29
- Raised by: spec review recheck (scope)

## D-016: SCIM audit closure return value on error
- Choice: Return `[]`, not null. Keystone `AuditHooks::contextFor()` spreads each result, so null would throw a TypeError. The closure also returns `[]` unless the event belongs to the `roster` package, because AuditHooks has no package filter (Keystone/src/Audit/AuditHooks.php:106-111, :184-193).
- Rationale: The user accepted it. This keeps the intent of D-012.
- Date: 2026-10-29
- Raised by: the in-place rewrite

## D-017: How do Roster's own domains register roster.* despite the reservation?
- Choice: An `@internal` method on `Resources` and `PermissionCatalogue` that accepts only `roster.*`. The public `resource()` and `permission()` reject that prefix.
- Rationale: The user accepted it.
- Date: 2026-10-29
- Raised by: the in-place rewrite

## D-018: Null provisioned_status
- Choice: Fall back to active without reporting. Only invalid stored values are reported (D-011).
- Rationale: The user accepted it.
- Date: 2026-10-29
- Raised by: the in-place rewrite

## D-019: Approval
- Choice: Approved for tasks and the factory
- Date: 2026-10-29
- Raised by: spec review (approval)

## D-020: config/roster.php policies vs unchangeable ArchTest and PoliciesTest
- Choice: Remove the five SSO/SCIM entries from `config/roster.php` `policies`. Change the ArchTest `modelsWithoutPolicies()` input and the PoliciesTest comparison to the config policies plus the Gate-registered policies after boot. Both test changes join criterion 10's allowed list.
- Rationale: Accepted the recommendation.
- Date: 2026-10-29
- Raised by: build (group 1)

## D-021: Scope of the missing-policy exception
- Choice: Throw only for package (public) resources. Roster's own `roster.*` resources keep the documented fallback to `Authorizer::permits()` when the app removes their policy.
- Rationale: Accepted the recommendation. No behaviour change for Roster's own domains.
- Date: 2026-10-29
- Raised by: build (group 1 deviation)
