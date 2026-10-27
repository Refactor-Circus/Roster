# Standards for User Approval

The following standards apply to this work.

---

## backend/actions


All behavior flows through `src/Actions/*Action.php`. No exceptions — every HTTP endpoint and MCP tool delegates to an action.

```php
final class CreatePromptAction
{
    public static function rules(): array { ... } // even when empty

    public function execute(array $data): Prompt { ... }
}
```

- `final` class, static `rules()`, instance `execute()`
- `rules()` is the single source of validation. HTTP and MCP Request classes both return `SomeAction::rules()` — never inline rules. Two surfaces (API + MCP) must never drift.
- Controllers/tools stay thin: they call `$request->persist()` / `$request->handle()`, which resolves the action via `app()`
- Dependencies (e.g. `PublicationCache`) via constructor promotion
- New endpoint = new action first, then HTTP + MCP request wrappers

---

## backend/action-transactions


Every mutating `execute()` wraps its writes in `DB::transaction`:

```php
public function execute(array $data): Prompt
{
    return DB::transaction(function () use ($data): Prompt {
        // all writes here
    });
}
```

- Any write — even a single one — goes inside the transaction. Some older single-write actions skip this; bring them in line when touched, don't copy them.

Mutations return the model with relations explicitly loaded:

```php
return $prompt->load('publishedVersion');
return $agent->refresh()->load(['prompt', 'pinnedVersion', 'subAgents']);
```

- HTTP and MCP serialize the result through the same Resource — relations must be eager-loaded so both surfaces return complete, identical payloads with no lazy-load surprises.
- After updates, `refresh()` first, then `load()`.

---

## backend/domain-guards


Business-rule failures in actions throw `ValidationException::withMessages` — not custom exceptions:

```php
if ($prompt->agents()->exists()) {
    throw ValidationException::withMessages([
        'prompt' => 'The prompt is attached to one or more agents and cannot be deleted.',
    ]);
}
```

- Key the message by the relevant input field (`prompt`, `sub_agents`, `prompt_version`)
- Why: every surface renders it for free — HTTP gets structured 422, MCP gets tool error, and the Blade dashboard gets them back as ordinary validation errors
- No custom domain exception classes; no abort()/HttpException in actions
- Examples: delete-in-use, circular sub-agent refs, version pinned without prompt

---

## backend/http-requests


Controllers are routing glue — every method is one line:

```php
public function store(StorePromptRequest $request): JsonResponse
{
    return $request->persist();
}
```

The FormRequest is the complete HTTP use case — validation, authorization, action call, response:

```php
final class StorePromptRequest extends Request
{
    public function rules(): array
    {
        return CreatePromptAction::rules();
    }

    public function persist(): JsonResponse
    {
        $prompt = app(CreatePromptAction::class)->execute($this->validated());

        return (new PromptResource($prompt))->response()->setStatusCode(201);
    }
}
```

- Extend `RefactorCircus\Roster\Http\Request` (abstract persist() enforces the shape)
- One request class per operation; controllers stay identical across models and mirror the MCP request layer 1:1
- `rules()` always delegates to the action's static rules — never inline
- Status codes: 201 create, 200 default, 204 (Response) for deletes
- Never put action calls or response building in controllers

---

## backend/http-resources


All payloads — HTTP and MCP — serialize through `src/Http/Resources`:

```php
/**
 * @mixin Prompt
 */
final class PromptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'published_version' => new PromptVersionResource($this->whenLoaded('publishedVersion')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
```

- Slug is the public identity (host users: their route key); omit internal ids unless there's a concrete need (not a hard ban, but default to leaving them out)
- Nested relations always via `whenLoaded()` — pairs with actions eager-loading what the resource needs
- Timestamps: `?->toIso8601String()`
- `@mixin ModelClass` docblock for static analysis
- `final`, no conditionals on request context

---

## backend/routes


All API routes live in `routes/roster.php` inside one group:

```php
Route::prefix($prefix)->middleware($middleware)->name('roster.')->group(...);
```

- Prefix and middleware come from `roster.routes.*` config — never hardcode
- Every route is named under `roster.` (`roster.prompts.versions.publish`)
- Model binding by slug for Roster-owned records (`{team:slug}`); host users bind by their own route key (`{user}`) — see slug-references
- Version segments constrained: `->whereNumber('version')`
- Domain operations (publish, run) are `POST` with a verb suffix on the resource path — never overload PATCH with mode flags:

```php
Route::post('prompts/{prompt:slug}/versions/{version}/publish', ...);
Route::post('agents/{agent:slug}/run', ...);
```

- Explicit route definitions per action — no `Route::resource()`

---

## backend/mcp-model-binding


Slug→model resolution lives in an abstract per-model request base — the MCP analog of route-model binding:

```php
abstract class PromptMcpRequest extends Request
{
    private ?Prompt $prompt = null;

    protected function prompt(): Prompt
    {
        return $this->prompt ??= Prompt::query()
            ->where('slug', $this->get('slug'))
            ->firstOrFail();
    }
}
```

- Always create the per-model base for any slug→model lookup, even for a single tool — consistency over YAGNI
- Memoize with `??=` — rules and handle may both need the model
- Use `firstOrFail()`; base `persist()` catches ModelNotFoundException and returns `Response::error('Not found.')` — no manual existence checks
- Concrete requests add `'slug' => ['required', 'string']` to rules alongside the action's rules

---

## backend/mcp-responses


All structured MCP responses wrap payloads in a `data` envelope:

```php
// lists
return $this->structuredCollection(PromptResource::collection($prompts)->resolve());
// → { "data": [ ... ] }

// single items — same envelope
return Response::structured(['data' => (new PromptResource($prompt))->resolve()]);
```

- Serialize through the same `Http/Resources` classes as the HTTP API — never hand-build arrays
- Envelope is mandatory for lists: `Response::structured([])` throws, so an empty list must ship as `{ "data": [] }` (that's why `structuredCollection()` exists)
- Some older single-item responses return the bare resource with no envelope — legacy; wrap in `data` when touched, don't copy
- Errors: `Response::error('...')` — base request maps ModelNotFoundException → `Not found.`

---

## backend/mcp-schemas


Every schema field gets a `->description()` — it's the model-facing documentation:

```php
'slug' => $schema->string()->description('Unique identifier (letters, numbers, dashes, underscores).')->required(),
'prompt_version' => $schema->integer()->description('Pin a specific prompt version. Omit to follow the published version.')->min(1),
```

- Descriptions state defaults and behavior ("Defaults to true.", "Replaces the whole list.") — the model can't read the code
- Any schema fragment used by 2+ tools goes into a `Mcp/Tools/Concerns` trait (e.g. `DescribesAgentPayload` for create/update agent fields) so tools never drift
- Tool descriptions: `#[Description('...')]` attribute — keep it accurate; it is the model's only documentation of the tool

---

## backend/mcp-tools


Tools are declarative shells. All logic lives in `src/Mcp/Requests/*McpRequest.php`:

```php
#[Description('Create a Roster prompt. ...')]
final class CreatePromptTool extends Tool
{
    public function handle(CreatePromptMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array { ... }
}
```

- Extend `Laravel\Mcp\Server\Tool`; Cortex description overrides are wired in feature 4 (surface hardening)
- Request class: `rules()` returns `SomeAction::rules()` (plus slug lookup rules), `handle(array $validated)` resolves the action — deliberate mirror of the HTTP FormRequest `persist()` pattern: same mental model and test shape on both surfaces
- Base `Mcp\Request::persist()` centralizes authorize → validate → not-found handling; never re-implement in a tool
- Never put queries or action calls in `Tool::handle()` — always delegate
- `schema()` and `rules()` describe the same fields — update both or they drift (schema is what the model sees; rules are what's enforced)

---

## backend/config-docs


`config/roster.php` is user-facing documentation. Every section gets a banner comment:

```php
/*
|--------------------------------------------------------------------------
| HTTP API Routes
|--------------------------------------------------------------------------
|
| The prefix and middleware applied to the Roster management API routes.
| Add authentication middleware (e.g. auth:sanctum) before exposing
| these routes in production - they manage and execute agents.
|
*/
```

Banner must state:
- What the section controls
- Security implications — anything exposing routes names the auth middleware to add before production (mandatory)
- Available modes/values and what each does (e.g. the `cache` store and fresh/stale windows)

New config key without a documented section = incomplete change.

---

## backend/feature-toggles


Optional surfaces (dashboard UI, MCP web/local transports) register conditionally in the service provider, driven entirely by config:

```php
if ($config->get('roster.ui.enabled') !== true) {
    return;
}

if ($config->get('roster.mcp.web.enabled') === true) { ... }
```

- Compare against literal `true` — fail closed: `'1'`, `'yes'`, or typo'd config never accidentally exposes an endpoint
- Route, handle, and middleware for each surface come from config — nothing hardcoded in the provider (the dashboard's path, middleware and gate come from Atrium's config)
- Registration lives in a private `register…()` method per surface, called from `boot()`
- New optional surface = same shape: `roster.<surface>.enabled` flag, strict check, config-driven wiring

---

## backend/publish-tags


Every publishable group carries the umbrella tag plus its own specific tag:

```php
$this->publishes([
    __DIR__.'/../config/roster.php' => config_path('roster.php'),
], ['roster', 'roster-config']);
```

- Tags: `roster` (everything) + `roster-config`, `roster-views`, `roster-lang`, `roster-migrations`
- Migrations use `publishesMigrations()` (re-dates files on publish)
- All `publishes()` + `commands()` registrations sit behind the `runningInConsole()` guard — keep new ones there
- New publishable group = same dual-tag shape, named `roster-<thing>`

---

## backend/container-bindings


Binding lifetime in `RosterServiceProvider::register()` is a deliberate choice per service:

```php
$this->app->singleton(Support\Users::class); // config-derived state
$this->app->singleton(Roster::class);
```

- `singleton` for services holding code/config-level state — safe for the process lifetime
- `scoped` for services that memoize database state — a singleton would serve stale data across requests in long-running workers (Octane, queues)
- Rule when adding a binding: memoizes DB/request state → `scoped`; pure code/config state → `singleton`
- Actions are never bound — resolved fresh via `app(Action::class)` each call

---

## backend/slug-references


Action inputs reference records by slug + integer version number — never by internal id:

```php
'prompt' => ['sometimes', 'nullable', 'string', Rule::exists('roster_prompts', 'slug')],
'prompt_version' => ['sometimes', 'nullable', 'integer', 'min:1'],
'sub_agents.*' => ['string', 'distinct', Rule::exists('roster_agents', 'slug')],
```

- Internal ids stay internal: FK columns (`prompt_id`, `prompt_version_id`) are resolved inside the action — see `Actions/Concerns/ResolvesAgentReferences`
- Why: slugs are stable human/agent-facing identifiers (MCP callers work in slugs), and they survive export/import across environments; ids don't
- Cast resolved keys to string (`(string) $model->getKey()`)
- Adding a cross-record input? Accept slug, validate with `Rule::exists(..., 'slug')`, resolve to FK in the action or a shared concern

## Roster exception: host users

Users belong to the host app and have no slug. Reference them by the user model's route key (`getRouteKeyName()`, usually `id`), validated with `Rule::exists(<users table>, <route key>)`. Roster-owned records (teams, roles, …) still use slugs.

---

## database/migrations


Package migrations use a fixed date + sequence number, one file per feature's tables:

```
2026_01_01_000001_create_roster_prompt_tables.php
2026_01_01_000002_create_roster_agent_tables.php
2026_01_01_000003_create_roster_tool_description_tables.php
```

- Fixed `2026_01_01` date + incrementing sequence — package migrations must run in a stable, reviewable order in any host app regardless of authoring time; next migration takes `000004`
- One migration creates all tables for a feature (parent + versions + pivots)
- `ulid('id')->primary()`; FKs via `foreignUlid(...)->constrained(...)->cascadeOnDelete()`
- Compound uniques where the domain demands (`unique(['prompt_id', 'version'])`)
- `down()` drops tables in reverse dependency order
- Anonymous class migrations, `declare(strict_types=1)`

---

## database/models


```php
/**
 * @property string $id
 * @property string $slug
 * @property Carbon|null $created_at
 */
final class Prompt extends Model
{
    /** @use HasFactory<PromptFactory> */
    use HasFactory;
    use HasUlids;

    protected $table = 'roster_prompts';

    protected $fillable = ['name', 'slug', 'description'];

    /** @return HasMany<PromptVersion, $this> */
    public function versions(): HasMany { ... }

    protected static function newFactory(): PromptFactory { ... }
}
```

- `final`; explicit `$table` with `roster_` prefix (package tables live in host apps)
- `HasUlids` on every model — ULIDs avoid id collisions on export/import between environments and don't leak row counts; pairs with slug as public identity
- `@property` docblock for every column; relations carry generics (`HasMany<PromptVersion, $this>`)
- Explicit `$fillable` (no `$guarded = []`), explicit FK names in relations
- `newFactory()` points at the package factory namespace

---

## testing/test-layers


Behavior is tested once, at the action; surfaces test only their own wiring.

| Layer | Location | Invokes via | Covers |
|---|---|---|---|
| Actions | `tests/Feature/*ActionsTest.php` | `app(Action::class)->execute()` | Business rules, edge cases, guards |
| HTTP | `tests/Feature/Http/` | `$this->getJson(route('roster.…'))` | Status codes, validation mapping, JSON paths |
| MCP | `tests/Feature/Mcp/` | `RosterServer::tool(Tool::class, $args)` | Envelopes, errors, HTTP parity |

- Don't duplicate business cases per surface — a delete-in-use rule is one ActionsTest case, not three
- HTTP tests always use `route('roster.…')` names, never literal URLs
- Setup via model factories; fixtures (fake tools, MCP servers, policies) live in `tests/Fixtures`

---

## testing/mcp-http-parity


Every MCP tool that returns a resource gets a parity test: its structured content must equal the HTTP payload for the same record.

```php
it('creates a prompt with parity to the http payload', function () {
    $mcp = RosterServer::tool(CreatePromptTool::class, [
        'name' => 'Support', 'slug' => 'support', 'content' => 'You are helpful.',
    ])->assertOk();

    $http = $this->getJson(route('roster.prompts.show', 'support'))->json('data');

    $mcp->assertStructuredContent($http);
});
```

- HTTP is the canonical shape; MCP must match — this is the executable check on the shared-Resource contract
- Required for every mutation tool; list tools additionally test the empty case (`data: []` must not error)
- Invoke tools via `RosterServer::tool(ToolClass::class, $args)` — never construct requests by hand

---

## testing/arch-tests


Global code constraints are executable — they live in `tests/ArchTest.php`, not in review checklists:

```php
arch()->preset()->php();
arch()->preset()->security();

arch('it will not use dd(), ddd(), env(), or exit()')
    ->expect(['dd', 'ddd', 'env', 'exit'])
    ->each->not->toBeUsed();

arch('the package source declares strict types')
    ->expect('RefactorCircus\Roster')
    ->toUseStrictTypes();
```

- Every PHP file: `declare(strict_types=1)` — enforced
- Banned everywhere: `dd`, `ddd`, `env` (use `config()`), `exit`
- New global constraint? Add an arch expectation here — that's the enforcement point

---

## testing/testcase-environment


All tests extend `RefactorCircus\Roster\Tests\TestCase` (Orchestra Testbench), bound in `Pest.php` via `uses(TestCase::class)->in(__DIR__)`.

Pinned environment — don't undo these:

- `database.default => testing` with `foreign_key_constraints => true` — FK violations must fail in tests
- Providers: `McpServiceProvider`, `AtriumServiceProvider`, then `RosterServiceProvider`
- Package migrations loaded from `database/migrations`; workbench `users` table from Testbench's default migrations

Per-test config: call `config()->set(...)` at the start of the test (or a dedicated TestCase subclass) — never mutate the shared `TestCase` for one feature.

---

## backend/runtime-exceptions


Runtime failures get dedicated exception classes in `src/Exceptions` — unlike action-layer guards, which throw field-keyed `ValidationException`:

```php
final class PromptNotPublishedException extends RuntimeException
{
    public static function forPrompt(Prompt $prompt): self
    {
        return new self("Prompt [{$prompt->slug}] has no published version.");
    }
}
```

- Why: action guards reject user input (there's a field to key the 422 on). Runtime failures are system state discovered mid-execution — no input field exists; typed exceptions let callers (RunAgent action, host apps embedding AgentFactory) catch specific conditions
- Shape: `final`, extends `RuntimeException`, static `forX()` named constructor, slug (not id) in the message, message wraps identifiers in `[…]`
