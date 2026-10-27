# References for Surface Hardening

## Similar Implementations

### Impex Cortex integration
- **Location:** `../Impex/src/Cortex/CortexIntegration.php`, `../Impex/src/Mcp/Tool.php`, `../Impex/src/Mcp/ImpexServer.php` (`createContext`), `../Impex/src/ImpexServiceProvider.php` (first line of `boot`), `../Impex/config/impex.php` (cortex section)
- **Relevance:** copied near-verbatim; Roster gains the same `Mcp\Tool` base class.
- **Tests:** `../Impex/tests/CortexTestCase.php`, `../Impex/tests/Cortex/CortexTest.php`

### Atrium browser tests
- **Location:** `../Atrium/tests/BrowserTestCase.php`, `../Atrium/tests/Browser/*`, `../Atrium/package.json`
- **Relevance:** copying Atrium's public assets so Alpine boots; `visit()` assertions.
