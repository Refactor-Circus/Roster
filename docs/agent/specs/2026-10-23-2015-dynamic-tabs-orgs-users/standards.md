# Standards for Registered tabs on organization and user screens

| Standard | File | Source | Why it applies |
| --- | --- | --- | --- |
| architecture/public-api | vendor/refactor-circus/standards/standards/architecture/public-api.md | refactor-circus/standards@dev-main | New documented extension point (`Atrium\Tabs`, `Atrium\TabDefinition`) |
| architecture/container-bindings | vendor/refactor-circus/standards/standards/architecture/container-bindings.md | refactor-circus/standards@dev-main | `Tabs` holds code state, bound as a singleton in `register()` |
| data/value-objects | vendor/refactor-circus/standards/standards/data/value-objects.md | refactor-circus/standards@dev-main | `TabDefinition` is a value object with `public private(set)` state and fluent setters |
| architecture/naming | vendor/refactor-circus/standards/standards/architecture/naming.md | refactor-circus/standards@dev-main | Names of the new classes, views and test ids |
| http/authorization | docs/agent/standards/http/authorization.md | local | Tab permission goes through `ScreenAccess::allows` / the Authorizer, like built-in tabs |
| roster/atrium-views | docs/agent/standards/roster/atrium-views.md | local | Tab bar partial and user screen views use Atrium components and safelisted utilities only |
| roster/action-surfaces | docs/agent/standards/roster/action-surfaces.md | local | Confirms no parity gap: no action is added (D-001) |
| roster/optional-surfaces | docs/agent/standards/roster/optional-surfaces.md | local | Registered tabs only render while `roster.ui.enabled` |
| roster/testbench-environment | docs/agent/standards/roster/testbench-environment.md | local | Feature tests use the pinned Testbench setup and UI TestCase |
| testing/test-layers | vendor/refactor-circus/standards/standards/testing/test-layers.md | refactor-circus/standards@dev-main | Unit tests for the registry/definition, feature tests for screens |
| testing/arch-tests | vendor/refactor-circus/standards/standards/testing/arch-tests.md | refactor-circus/standards@dev-main | New classes stay under the arch rules (final, strict types) |
