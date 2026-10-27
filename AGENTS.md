# PennantPlus

This repository is a Laravel package. Keep the package focused, idiomatic, and easy for Laravel developers to install, test, and maintain.

## Package Conventions

- Use Laravel-native package APIs and the existing service provider shape before adding abstractions.
- Keep package names, namespaces, Composer metadata, publish tags, documentation, and examples aligned with `refactor-circus/pennantplus` and the `RefactorCircus\PennantPlus` namespace. Code under `src/Atrium` extends Atrium classes and must only load when refactor-circus/atrium is installed; nothing outside it may reference Atrium. Cortex (refactor-circus/cortex) is wired only through refactor-circus/keystone's `CortexIntegration`, which references Cortex classes behind its `active()` check; nothing in this package references Cortex directly.
- Add only the files and dependencies needed for the package behavior being implemented.
- Prefer explicit Laravel package code over helper abstractions unless the extension point is real.
- Keep tests focused on observable package behavior through public APIs, service provider wiring, commands, routes, published resources, and documentation promises.

## Layout

The package follows the mono domain-module layout. Code lives in `src/Domains/{Domain}/` (namespace `RefactorCircus\PennantPlus\Domains\{Domain}`), each with its own `{Domain}ServiceProvider` registered by `Domains\DomainServiceProvider`, which `PennantPlusServiceProvider` registers. Domains create only the subdirectories they use.

- `Domains/Feature`: the layered feature base classes and the `pennantplus` driver (`Support/`), `FeatureGate` and `FeatureFlagManager` (`Services/`), `StoredFeatureValue` and the Actions' Data classes (`Data/`), and the feature and stored-value actions, events, HTTP API, middleware and MCP tools.
- `Domains/Scope`: `ScopeModel` and `SearchScopeModelsData` (`Data/`), the scope validation rules and MCP argument schema, and the scope-type actions, HTTP API and MCP tools.
- Package-wide pieces stay at the top level: `PennantPlusServiceProvider`, the base `Http\Request` and `Mcp\Request` (each extends refactor-circus/keystone's and adds only the `pennantplus.ability` check), the MCP server and `Mcp\Tools\ListPennantPlusHistoryTool`.
- The Atrium integration spans both domains, so it keeps its own top-level directory: `src/Atrium` (the Feature flags screen).

## Frontend

Atrium owns every component and style of the suite. PennantPlus ships no stylesheet, no `Atrium::css()` call and no component namespace: `resources/views` uses only `x-atrium::*` components and the utilities Atrium's compiled stylesheet ships, with no `<style>` block or `style` attribute. `tests/Feature/Ui/StylesTest.php` checks this with `RefactorCircus\Atrium\Testing\AtriumStyles`. If a screen needs something Atrium lacks, add it to Atrium, not here. Requests flash `status` for `x-atrium::flash`. The scope picker's inline `atriumPennantScope` Alpine script stays here because it searches PennantPlus's own endpoint.

Atrium's shared screen helpers (`ScreenAccess`, `AuthorizesScreens`, `Plugin::featuresFromConfig()`) ask a package's model policies; feature flags have no models or policies, so the screen keeps `FeatureFlagAccess`, which asks the plugin's `pennantplus.ability` check.
- `config/pennantplus.php` stays one file; domains read from it.

## Keystone

PennantPlus stands on `refactor-circus/keystone`, the suite's shared runtime. Use its classes rather than adding package copies:

- `PennantPlusServiceProvider` extends `RefactorCircus\Keystone\Support\PackageServiceProvider`: `definition()` describes the package (`pennantplus`, its MCP server), `registerPackage()` runs in `register()` before the domain providers, and `boot()` uses `registerMcpServer()`, `registerCortex()` and `loadHistoryRoutes()`.
- Domain providers extend `RefactorCircus\Keystone\Support\ServiceProvider` and load their `routes.php` through `loadApiRoutesFrom()`, inside the shared `pennantplus.routes` group with the `pennantplus.` name prefix.
- Action events implement `RefactorCircus\Keystone\Contracts\ActionStartingEvent` / `ActionFinishedEvent`; MCP tools extend `RefactorCircus\Keystone\Mcp\Tool`; the server extends `RefactorCircus\Keystone\Mcp\Server`.
- Feature flags have no models, so there are no policies and no `authorization` default: every endpoint and tool checks the optional `pennantplus.ability` Gate ability through `FeatureFlagManager::allowsManagement()`. Keystone's request base answers guests 401, signed-in users the check refuses 403. `boot()` also calls `registerNotFoundResponses()`, so a missing scope model answers the API with a plain JSON 404.
- Action input is a `final readonly` `{Action}Data` in the domain's `Data/` (on `RefactorCircus\Keystone\Data\Data`), never an array or loose strings: fields are promoted constructor properties (camelCase of the input keys), and `rules()` lives on the Data (`UpdateFeatureValueData::rules(encoded: true)` for a JSON-encoded value, built with `fromEncoded()`). A scope is picked on the Data as over the API - `scope`, or `scopeType` with `scopeId` - and the Action resolves it with `FeatureFlagManager::scopeFromInput()`. HTTP, MCP and Atrium requests validate once: their `rules()` return `XData::rules()` and `persist()`/`handle()` build with `XData::fromValidated($this->validated() / $validated)` - never `XData::from()` in a request (ArchTest). The Atrium controller uses `XData::from($request->all())`, which validates; code that vouches for its values uses `new XData(...)`. List Data expose `pagination()` (Keystone `Pagination`), shared by their rules and their tool's input schema. `tests/ArchTest.php` rejects array input on `execute()` and requires a starting and a finished event from every Action (every read pair also implements Keystone's `Contracts\ReadActionEvent`, and the audit log skips them). Starting events carry the input Data (the value and delete events also the resolved serialized scope).
- Every write runs inside `FeatureFlagManager::transaction()` - a transaction on the managed Pennant store's connection that flushes Pennant's in-memory cache on rollback - with the starting event before it and the finished event after; finished events wait for the commit. The one exception is the bulk forget after a global update (`FeatureFlagManager::forgetScopedValues()`), which deletes in id chunks after the global value commits so it never holds a long transaction. PennantPlus owns no tables: indexes on Pennant's `features` table ship only as the optional `pennantplus-migrations` publish (`database/migrations`, never loaded with `loadMigrationsFrom`). Process-wide memos on `FeatureFlagManager` (feature classes, scope models) are keyed on their config value; the scope-type list is cached and forgotten by every write Action (`forgetScopeTypes()`). The one exception is the bulk forget after a global update (`FeatureFlagManager::forgetScopedValues()`), which deletes in id chunks after the global value commits so it never holds a long transaction. PennantPlus owns no tables: indexes on Pennant's `features` table ship only as the optional `pennantplus-migrations` publish (`database/migrations`, never loaded with `loadMigrationsFrom`). Process-wide memos on `FeatureFlagManager` (feature classes, scope models) are keyed on their config value; the scope-type list is cached and forgotten by every write Action (`forgetScopeTypes()`). `tests/ArchTest.php` checks Actions for writes outside a transaction (`Arch::writesOutsideTransactions`), that every `*Action.php` lives in a domain `Actions/`, and that any Eloquent model under `src` fires an event for every lifecycle hook (`Arch::modelEventGaps`; PennantPlus has none today).
- API resources (`Domains/Feature/Http/Resources`) extend `RefactorCircus\Keystone\Http\Resources\JsonResource` and declare `schema(JsonSchema $schema)` next to `toArray()` (always-present fields `required()`, conditional ones not, `nullable()`; a flag value is any JSON value, `FeatureResource::VALUE_TYPES`). MCP requests answer with `(new XResource($m))->toStructuredResponse()` / `XResource::collection($page)->toStructuredResponse()`; the plain-array answers (`check-feature-tool`, the scope tools) use `Response::structured()` / `structuredCollection()`. Each such tool declares `outputSchema()` - `XResource::structuredSchema($schema)` / `collectionSchema($schema, ListXData::pagination())`, or a hand-written schema for the plain arrays. `mcpTool()` in tests is Keystone's `Testing\McpTools::call()`, which checks every structured answer against the tool's schema, and `tests/Feature/Mcp/OutputSchemaTest.php` requires the declaration.
- PennantPlus has no audit code: refactor-circus/keen records its action events while `pennantplus.audit.enabled` is on (Keystone's `Package::audits()`). Never add audit tables or writers here. Keystone's request base answers guests 401, signed-in users the check refuses 403. `boot()` also calls `registerNotFoundResponses()`, so a missing scope model answers the API with a plain JSON 404.

## Quick Commands

- Full validation: `composer test`
- Formatting check: `composer lint:check`
- Static analysis: `composer analyse`
- Pest tests: `composer test:unit`
- Workbench build: `composer build`
- Workbench server: `composer serve`

## Local Skills

- `package-scaffold`: use when adding package capabilities or wiring them through the service provider, including commands, migrations, routes, config, views, translations, assets, middleware, publish tags, workbench files, and console-only behavior.
- `package-testing`: use when adding or changing package tests with Pest 4/5 and Orchestra Testbench.
- `package-release`: use when preparing changelog, release notes, tags, or GitHub release workflow changes.
- `package-compatibility`: use when reviewing code, dependencies, or CI against the PHP and Laravel support matrix.
- `package-generate-skill`: use when updating the bundled Boost skill from the package implementation, README, and examples.

<!-- ring-master:start -->
## Ring Master

- Specs, product docs and local standards live in `docs/agent/`. Shared standards come from `vendor/` through `docs/agent/standards.index.yml`; read the matching standards before changing code.
- Workflow: `/ring-master-shape-spec` → `/ring-master-write-spec` → `/ring-master-create-tasks` → `/ring-master-implement` → `/ring-master-verify-spec`.
- Done means `composer test` passes. When work must break a standard, add a row to `docs/agent/standards-feedback.md`.
<!-- ring-master:end -->
