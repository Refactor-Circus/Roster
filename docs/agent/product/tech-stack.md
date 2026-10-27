# Tech Stack

Follows the same stack as the sibling packages `refactor-circus/atrium`, `refactor-circus/impex`, and `refactor-circus/cortex`.

## Frontend

- Atrium dashboard plugin (`refactor-circus/atrium`) for admin screens
- Blade + Alpine.js 3, Tailwind CSS 4 (Atrium conventions)

## Backend

- PHP ^8.5
- Laravel ^13.15 (`laravel/framework`)
- Laravel MCP (`laravel/mcp`) for MCP tool surface
- Action classes as the single API, exposed via HTTP API, MCP, and Atrium

## Database

- Any Laravel-supported database (SQLite, MySQL, PostgreSQL) via package migrations

## Other

- Testing: Pest 5 (+ Laravel, type-coverage plugins; browser plugin + Playwright where UI tested), Orchestra Testbench 11, workbench app
- Quality: Larastan, Laravel Pint, Laravel PAO
- Ecosystem: `refactor-circus/impex` (import/export, post-launch), `refactor-circus/cortex` (dev integration)
