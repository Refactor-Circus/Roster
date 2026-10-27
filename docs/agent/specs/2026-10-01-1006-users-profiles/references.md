# References for Users + Profiles

## Similar Implementations

### Impex Actions
- **Location:** `../Impex/src/Actions/` (e.g. `CancelRunAction.php`), `../Impex/src/Events/Action/`
- **Relevance:** canonical Action shape.
- **Key patterns:** final class, static `rules()`, `execute()`, *ing/*ed events, `DB::transaction`.

### Cortex HTTP layer
- **Location:** `../Cortex/src/Http/Request.php`, `../Cortex/src/Http/Requests/`, `../Cortex/src/Http/Resources/`, `../Cortex/routes/cortex.php`
- **Relevance:** FormRequest `persist()` pattern, one-line controllers.
- **Key patterns:** abstract per-model request bases, resources with a `data` envelope.

### Impex MCP layer
- **Location:** `../Impex/src/Mcp/{Request.php,ImpexServer.php,Tools/,Requests/}`, `ImpexServiceProvider::registerMcpServer()`
- **Relevance:** MCP request base, server registration behind toggles.
- **Key patterns:** `persist()` catches not-found, `structuredCollection()`, `Mcp::web`/`Mcp::local`.

### Atrium plugin
- **Location:** `../Atrium/src/Plugins/Plugin.php`, `../Atrium/src/Contracts/Plugin.php`, `../Atrium/resources/views/components/`, `../Impex/src/Atrium/ImpexPlugin.php`, `../Impex/src/Http/Ui/RunUiController.php`
- **Relevance:** dashboard screens for users.
- **Key patterns:** `navigation()`, `routes()` under `atrium.<key>.`, `widgets()`, `search()`; UI controllers validate with `Action::rules()`; Blade `x-atrium::*` components.

### Parity + UI tests
- **Location:** `../Impex/tests/ArchTest.php`, `../Impex/tests/Pest.php` (`parityGaps()`), `../Impex/tests/Feature/Ui/ImpexPluginTest.php`
- **Relevance:** surface-parity enforcement, plugin wiring tests.
