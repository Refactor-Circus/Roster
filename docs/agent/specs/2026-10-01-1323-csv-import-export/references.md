# References for CSV Import / Export

## Impex (`../Impex`)
- **Flows:** `docs/02-flows.md`, `src/Flows/Flow.php`, `src/Flows/FlowRegistry.php`, `docs/12-extending.md` (registering flows from a package)
- **Batches:** `src/Contracts/BatchSource.php`, `src/Runtime/BatchRunner.php`, `Impex::batchItems()`, `Models/Batch` (progress counters)
- **Signals:** `docs/05-signals-timers.md` (`signal()->timeoutAfter()->default()->wait()`, `Impex::signal()`)
- **Resumable work:** `src/Flows/ResumableAction.php`, `docs/06-scale.md` (streaming a file with a byte-offset cursor)
- **Testing:** `docs/15-testing.md`, `src/Testing/Flows.php`

## This package
- **Optional dependency pattern:** `src/Sso/Sso.php::available()`
- **Domain rule:** `src/Scim/ScimUsers.php` (claim only accounts on the organization's domains; invite otherwise)
- **Audit attribution:** `src/Audit/Surface.php::using()`, `src/Audit/AuditRecorder.php`
