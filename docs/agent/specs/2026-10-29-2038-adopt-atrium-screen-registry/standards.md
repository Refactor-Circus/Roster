# Standards for Adopt Atrium screen registry

| Standard | File | Source | Why it applies |
| --- | --- | --- | --- |
| architecture/dependencies | vendor/refactor-circus/standards/standards/architecture/dependencies.md | refactor-circus/standards@dev-main | Atrium moves to suggest; Cortex and Keen UI removed; Atrium code only under src/Atrium |
| architecture/public-api | vendor/refactor-circus/standards/standards/architecture/public-api.md | refactor-circus/standards@dev-main | Deleting Tabs/TabDefinition/TabVisibility and RedirectDomains is a breaking change with upgrade notes; new ScreenAccess::allowsOn() |
| architecture/domain-modules | vendor/refactor-circus/standards/standards/architecture/domain-modules.md | refactor-circus/standards@dev-main | Where screen declarations sit (src/Atrium, package-wide code) |
| architecture/container-bindings | vendor/refactor-circus/standards/standards/architecture/container-bindings.md | refactor-circus/standards@dev-main | Tabs singleton and TabVisibility scoped bindings removed |
| architecture/service-providers | vendor/refactor-circus/standards/standards/architecture/service-providers.md | refactor-circus/standards@dev-main | RosterServiceProvider must boot without Atrium |
| data/value-objects | vendor/refactor-circus/standards/standards/data/value-objects.md | refactor-circus/standards@dev-main | ScreenAccess stays a static-only stateless helper |
| http/authorization | docs/agent/standards/http/authorization.md | local | Access closures go through ScreenAccess / Authorizer, the same check as HTTP and MCP |
| roster/atrium-views | docs/agent/standards/roster/atrium-views.md | local | Tab bars and slots through Atrium components only; StylesTest stays green |
| roster/action-surfaces | docs/agent/standards/roster/action-surfaces.md | local | Parity scan over src/Atrium controllers after RedirectDomainUiController is removed |
| roster/optional-surfaces | docs/agent/standards/roster/optional-surfaces.md | local | The dashboard is an optional surface that now needs a suggested package |
| roster/testbench-environment | docs/agent/standards/roster/testbench-environment.md | local | New AtriumUnavailable mode test case and provider order |
| testing/arch-tests | vendor/refactor-circus/standards/standards/testing/arch-tests.md | refactor-circus/standards@dev-main | New rules: Atrium only used in src/Atrium; removed classes |
| testing/test-layers | vendor/refactor-circus/standards/standards/testing/test-layers.md | refactor-circus/standards@dev-main | Screen feature tests and the re-expressed PR #5 suites; browser suite via composer test:browser |
