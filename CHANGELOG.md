# Release Notes

## [Unreleased](https://github.com/Refactor-Circus/PennantPlus/commits/main)

### Changed

- Faster listing: `ListFeaturesAction` (`GET features`, `list-features-tool`) summarises every feature with two queries via the new `FeatureFlagManager::summaries(array $features)` instead of two per feature; `summary()` uses it. Feature directory discovery is scanned once per process and each Pennant store is told about the classes once.
- The model types that have stored values (`FeatureFlagManager::scopeTypes()`, the dashboard's scope filter, `GET scopes`) are cached for the new `pennantplus.scope_types_cache_seconds` (default 300, 0 disables); the update, delete and purge Actions forget the cache (`FeatureFlagManager::forgetScopeTypes()`).
- Setting a global value forgets the other scopes' values after the global value commits, in chunks of `FeatureFlagManager::FORGET_CHUNK` (1000) ids, instead of one unbounded delete inside the transaction. Should it fail part way, the global value stands; run the update again.
- The Atrium page asks `FeatureFlagAccess::allows()` once per page and passes `$manages` to its view, rather than once per table row; the `@pennantplusManages` directive is unchanged for published views.
- The configured scope models are built once per `pennantplus.scopes` value and looked up by class or type in one step.
- The `pennantplus` driver stores an unresolved global value without reading it a second time.

### Breaking

- Actions take their input as `final readonly` Data objects (on `RefactorCircus\Keystone\Data\Data`) instead of strings and arrays, and their validation `rules()` move onto those Data classes: `CheckFeatureData`, `DeleteFeatureValueData`, `ListFeatureValuesData`, `PurgeFeatureData`, `ShowFeatureData` and `UpdateFeatureValueData` in `Domains\Feature\Data`, `SearchScopeModelsData` in `Domains\Scope\Data`. Build one with `new` for values you vouch for, or with `XData::from($input)` to validate untrusted input. The scope is picked on the Data the same way as over the API: `scope`, or `scopeType` with `scopeId`. For example, `$action->execute($feature, $scope, $value)` becomes `$action->execute(new UpdateFeatureValueData($feature, $value, scope: $scope))`. `ListFeaturesAction` and `ListScopeTypesAction` take no input and no longer declare `rules()`.
- MCP tools answering with a resource wrap it under `data`: `show-feature-tool` and `set-feature-value-tool` answer `{"data": {...}}` (as the HTTP API does), and `list-feature-values-tool`'s `meta` also carries `per_page`.
- The starting events of the write Actions carry the input Data: `FeatureValueUpdatingActionEvent($data, $scope)` (was `($feature, $scope, $value)`), `FeatureValueDeletingActionEvent($data, $scope)` (was `($feature, $scope)`) and `FeaturePurgingActionEvent($data)` (was `($feature)`); read the feature as `$event->data->feature`.
- Guests are answered 401 instead of 403 when the `pennantplus.ability` check refuses them (Keystone's request base); signed-in users still get 403.
- With Atrium installed, PennantPlus needs Atrium 0.1.0 or later, the first release with the screen extensions registry: `composer.json` gains a `conflict` on `refactor-circus/atrium` below it. Atrium stays optional.
- The Feature flags page no longer renders its own history, and the package's navigation no longer links an audit log: history comes from refactor-circus/keen, which adds its History section to the page through the screen extensions registry. The `pennantplus/history` route and the `list-pennantplus-history-tool` MCP tool stay.

- Moved to the Refactor Circus organisation: the package is now `refactor-circus/pennantplus` with the PHP namespace `RefactorCircus\PennantPlus` (it was `jayi/pennantplus` and `JayI\PennantPlus`). Update `composer.json` requirements and `use` statements. Old class names are not kept as aliases, so stored values written under them - polymorphic `*_type` columns, audit subjects, Pennant feature names - need updating to the new names.

### Added

- An optional, publishable migration (`--tag=pennantplus-migrations`, not part of `--tag=pennantplus`, never auto-run) adding `(scope, name)`, `(updated_at)` and `(name, updated_at)` indexes to the managed store's Pennant table; recommended with the `pennantplus` driver.
- Every Action now fires a starting and a finished event, reads included: `FeaturesListing`/`FeaturesListed`, `FeatureShowing`/`FeatureShown`, `FeatureChecking`/`FeatureChecked`, `FeatureValuesListing`/`FeatureValuesListed` (in `Domains\Feature\Events`) and `ScopeTypesListing`/`ScopeTypesListed`, `ScopeModelsSearching`/`ScopeModelsSearched` (in `Domains\Scope\Events`), each suffixed `ActionEvent`. They are reads, so the audit log does not record them; the check and search pairs say so by implementing Keystone's `Contracts\ReadActionEvent`.
- `FeatureFlagManager::transaction()` runs feature value writes in one transaction on the managed Pennant store's connection (not the default connection), flushing Pennant's in-memory cache if it rolls back; the update, delete and purge Actions use it, so a failed write leaves every value as it was and fires no finished event.
- Every list and show event implements Keystone's `Contracts\ReadActionEvent`, as the check and search events already did.
- `tests/ArchTest.php` checks that Actions write inside a transaction and that any Eloquent model fires an event for every lifecycle hook.
- Every MCP tool that answers with structured content declares an `outputSchema()`; the API resources extend `RefactorCircus\Keystone\Http\Resources\JsonResource` and describe their fields with `schema()`.
- `list-feature-values-tool` and `GET values` accept `per_page` (1-100, default 25).
- An `audit.enabled` config key: false keeps PennantPlus out of the audit log, and its history off, even while refactor-circus/keen is installed.
- The package's section in Atrium's sidebar rail has its own icon (`flag`) and a fixed place in the rail.
- The Feature flags page declares itself on Atrium's screen extensions registry as the `pennantplus.features` list screen, so other packages add tabs (opened with `?tab=`) and sections in `header.actions`, `before`, `after` and the row slot `row.actions` without PennantPlus knowing them. An extension's `permission()` is a Gate ability, given the row in `row.actions`; each row is a `RefactorCircus\PennantPlus\Atrium\Data\FeatureValueRow`, an unsaved, read-only model carrying the `StoredFeatureValue` as `$record->value`. Nothing registered changes nothing, except a trailing actions cell in each value row for those who manage flags.
- A `WithoutAtrium` suite (`composer test:without-atrium`, run by `composer test`) and a CI job with Atrium uninstalled prove PennantPlus boots, serves its API and its MCP server without Atrium.

### Breaking

- PennantPlus now stands on `refactor-circus/keystone`, the suite's shared runtime, and requires it. The package's own copies of the shared pieces are gone in favour of Keystone's; update any imports:
  - `RefactorCircus\PennantPlus\Contracts\ActionStartingEvent` → `RefactorCircus\Keystone\Contracts\ActionStartingEvent`
  - `RefactorCircus\PennantPlus\Contracts\ActionFinishedEvent` → `RefactorCircus\Keystone\Contracts\ActionFinishedEvent`
  - `RefactorCircus\PennantPlus\Mcp\Tool` → `RefactorCircus\Keystone\Mcp\Tool`
  - `RefactorCircus\PennantPlus\Support\ServiceProvider` → `RefactorCircus\Keystone\Support\ServiceProvider`
  - `RefactorCircus\PennantPlus\Cortex\CortexIntegration` → `RefactorCircus\Keystone\Cortex\CortexIntegration` (built with `CortexIntegration::for($package)` rather than resolved from the container)

  `PennantPlusServiceProvider` extends Keystone's `PackageServiceProvider` and registers the package as `pennantplus`; `PennantPlusServer` extends `RefactorCircus\Keystone\Mcp\Server`; the base `Http\Request` and `Mcp\Request` extend Keystone's and keep the `pennantplus.ability` check. MCP calls now run inside Keystone's `mcp` surface, and changes a Cortex agent makes through a PennantPlus tool are marked with the `cortex` surface. Config keys, route names, tool names and behaviour are otherwise unchanged.

- The package is reorganised into domain modules (`src/Domains/Feature`, `src/Domains/Scope`), mirroring the mono application's layout. Classes move namespaces; there are no aliases for the old names, so update imports. Config keys, route names and paths, MCP tool names, publish tags, views, translations, the `pennantplus` driver name and the Atrium plugin are unchanged, and nothing PennantPlus stores is keyed by its own class names, so stored feature values are untouched. The management API routes now load from each domain (`routes/pennantplus.php` is gone). Old → new:
  - `RefactorCircus\PennantPlus\Actions\CheckFeatureAction` → `RefactorCircus\PennantPlus\Domains\Feature\Actions\CheckFeatureAction`
  - `RefactorCircus\PennantPlus\Actions\DeleteFeatureValueAction` → `RefactorCircus\PennantPlus\Domains\Feature\Actions\DeleteFeatureValueAction`
  - `RefactorCircus\PennantPlus\Actions\ListFeatureValuesAction` → `RefactorCircus\PennantPlus\Domains\Feature\Actions\ListFeatureValuesAction`
  - `RefactorCircus\PennantPlus\Actions\ListFeaturesAction` → `RefactorCircus\PennantPlus\Domains\Feature\Actions\ListFeaturesAction`
  - `RefactorCircus\PennantPlus\Actions\ListScopeTypesAction` → `RefactorCircus\PennantPlus\Domains\Scope\Actions\ListScopeTypesAction`
  - `RefactorCircus\PennantPlus\Actions\PurgeFeatureAction` → `RefactorCircus\PennantPlus\Domains\Feature\Actions\PurgeFeatureAction`
  - `RefactorCircus\PennantPlus\Actions\SearchScopeModelsAction` → `RefactorCircus\PennantPlus\Domains\Scope\Actions\SearchScopeModelsAction`
  - `RefactorCircus\PennantPlus\Actions\ShowFeatureAction` → `RefactorCircus\PennantPlus\Domains\Feature\Actions\ShowFeatureAction`
  - `RefactorCircus\PennantPlus\Actions\UpdateFeatureValueAction` → `RefactorCircus\PennantPlus\Domains\Feature\Actions\UpdateFeatureValueAction`
  - `RefactorCircus\PennantPlus\Drivers\GlobalAwareDatabaseDriver` → `RefactorCircus\PennantPlus\Domains\Feature\Support\GlobalAwareDatabaseDriver`
  - `RefactorCircus\PennantPlus\Events\Action\FeaturePurgedActionEvent` → `RefactorCircus\PennantPlus\Domains\Feature\Events\FeaturePurgedActionEvent`
  - `RefactorCircus\PennantPlus\Events\Action\FeaturePurgingActionEvent` → `RefactorCircus\PennantPlus\Domains\Feature\Events\FeaturePurgingActionEvent`
  - `RefactorCircus\PennantPlus\Events\Action\FeatureValueDeletedActionEvent` → `RefactorCircus\PennantPlus\Domains\Feature\Events\FeatureValueDeletedActionEvent`
  - `RefactorCircus\PennantPlus\Events\Action\FeatureValueDeletingActionEvent` → `RefactorCircus\PennantPlus\Domains\Feature\Events\FeatureValueDeletingActionEvent`
  - `RefactorCircus\PennantPlus\Events\Action\FeatureValueUpdatedActionEvent` → `RefactorCircus\PennantPlus\Domains\Feature\Events\FeatureValueUpdatedActionEvent`
  - `RefactorCircus\PennantPlus\Events\Action\FeatureValueUpdatingActionEvent` → `RefactorCircus\PennantPlus\Domains\Feature\Events\FeatureValueUpdatingActionEvent`
  - `RefactorCircus\PennantPlus\Exceptions\InvalidScopeModelException` → `RefactorCircus\PennantPlus\Domains\Scope\Exceptions\InvalidScopeModelException`
  - `RefactorCircus\PennantPlus\FeatureFlagManager` → `RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager`
  - `RefactorCircus\PennantPlus\FeatureGate` → `RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureGate`
  - `RefactorCircus\PennantPlus\Http\Controllers\FeatureController` → `RefactorCircus\PennantPlus\Domains\Feature\Http\Controllers\FeatureController`
  - `RefactorCircus\PennantPlus\Http\Controllers\FeatureValueController` → `RefactorCircus\PennantPlus\Domains\Feature\Http\Controllers\FeatureValueController`
  - `RefactorCircus\PennantPlus\Http\Controllers\ScopeController` → `RefactorCircus\PennantPlus\Domains\Scope\Http\Controllers\ScopeController`
  - `RefactorCircus\PennantPlus\Http\Middleware\EnsureFeatureActive` → `RefactorCircus\PennantPlus\Domains\Feature\Http\Middleware\EnsureFeatureActive`
  - `RefactorCircus\PennantPlus\Http\Requests\CheckFeatureRequest` → `RefactorCircus\PennantPlus\Domains\Feature\Http\Requests\CheckFeatureRequest`
  - `RefactorCircus\PennantPlus\Http\Requests\DeleteFeatureValueRequest` → `RefactorCircus\PennantPlus\Domains\Feature\Http\Requests\DeleteFeatureValueRequest`
  - `RefactorCircus\PennantPlus\Http\Requests\IndexFeatureValuesRequest` → `RefactorCircus\PennantPlus\Domains\Feature\Http\Requests\IndexFeatureValuesRequest`
  - `RefactorCircus\PennantPlus\Http\Requests\IndexFeaturesRequest` → `RefactorCircus\PennantPlus\Domains\Feature\Http\Requests\IndexFeaturesRequest`
  - `RefactorCircus\PennantPlus\Http\Requests\IndexScopeTypesRequest` → `RefactorCircus\PennantPlus\Domains\Scope\Http\Requests\IndexScopeTypesRequest`
  - `RefactorCircus\PennantPlus\Http\Requests\PurgeFeatureRequest` → `RefactorCircus\PennantPlus\Domains\Feature\Http\Requests\PurgeFeatureRequest`
  - `RefactorCircus\PennantPlus\Http\Requests\SearchScopeModelsRequest` → `RefactorCircus\PennantPlus\Domains\Scope\Http\Requests\SearchScopeModelsRequest`
  - `RefactorCircus\PennantPlus\Http\Requests\ShowFeatureRequest` → `RefactorCircus\PennantPlus\Domains\Feature\Http\Requests\ShowFeatureRequest`
  - `RefactorCircus\PennantPlus\Http\Requests\UpdateFeatureValueRequest` → `RefactorCircus\PennantPlus\Domains\Feature\Http\Requests\UpdateFeatureValueRequest`
  - `RefactorCircus\PennantPlus\Http\Resources\FeatureResource` → `RefactorCircus\PennantPlus\Domains\Feature\Http\Resources\FeatureResource`
  - `RefactorCircus\PennantPlus\Http\Resources\FeatureValueResource` → `RefactorCircus\PennantPlus\Domains\Feature\Http\Resources\FeatureValueResource`
  - `RefactorCircus\PennantPlus\LayeredFeature` → `RefactorCircus\PennantPlus\Domains\Feature\Support\LayeredFeature`
  - `RefactorCircus\PennantPlus\Mcp\Requests\CheckFeatureMcpRequest` → `RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests\CheckFeatureMcpRequest`
  - `RefactorCircus\PennantPlus\Mcp\Requests\ForgetFeatureValueMcpRequest` → `RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests\ForgetFeatureValueMcpRequest`
  - `RefactorCircus\PennantPlus\Mcp\Requests\ListFeatureValuesMcpRequest` → `RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests\ListFeatureValuesMcpRequest`
  - `RefactorCircus\PennantPlus\Mcp\Requests\ListFeaturesMcpRequest` → `RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests\ListFeaturesMcpRequest`
  - `RefactorCircus\PennantPlus\Mcp\Requests\ListScopeTypesMcpRequest` → `RefactorCircus\PennantPlus\Domains\Scope\Mcp\Requests\ListScopeTypesMcpRequest`
  - `RefactorCircus\PennantPlus\Mcp\Requests\PurgeFeatureMcpRequest` → `RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests\PurgeFeatureMcpRequest`
  - `RefactorCircus\PennantPlus\Mcp\Requests\SearchScopeModelsMcpRequest` → `RefactorCircus\PennantPlus\Domains\Scope\Mcp\Requests\SearchScopeModelsMcpRequest`
  - `RefactorCircus\PennantPlus\Mcp\Requests\SetFeatureValueMcpRequest` → `RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests\SetFeatureValueMcpRequest`
  - `RefactorCircus\PennantPlus\Mcp\Requests\ShowFeatureMcpRequest` → `RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests\ShowFeatureMcpRequest`
  - `RefactorCircus\PennantPlus\Mcp\Tools\CheckFeatureTool` → `RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\CheckFeatureTool`
  - `RefactorCircus\PennantPlus\Mcp\Tools\Concerns\DescribesScope` → `RefactorCircus\PennantPlus\Domains\Scope\Concerns\DescribesScope`
  - `RefactorCircus\PennantPlus\Mcp\Tools\ForgetFeatureValueTool` → `RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\ForgetFeatureValueTool`
  - `RefactorCircus\PennantPlus\Mcp\Tools\ListFeatureValuesTool` → `RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\ListFeatureValuesTool`
  - `RefactorCircus\PennantPlus\Mcp\Tools\ListFeaturesTool` → `RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\ListFeaturesTool`
  - `RefactorCircus\PennantPlus\Mcp\Tools\ListScopeTypesTool` → `RefactorCircus\PennantPlus\Domains\Scope\Mcp\Tools\ListScopeTypesTool`
  - `RefactorCircus\PennantPlus\Mcp\Tools\PurgeFeatureTool` → `RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\PurgeFeatureTool`
  - `RefactorCircus\PennantPlus\Mcp\Tools\SearchScopeModelsTool` → `RefactorCircus\PennantPlus\Domains\Scope\Mcp\Tools\SearchScopeModelsTool`
  - `RefactorCircus\PennantPlus\Mcp\Tools\SetFeatureValueTool` → `RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\SetFeatureValueTool`
  - `RefactorCircus\PennantPlus\Mcp\Tools\ShowFeatureTool` → `RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\ShowFeatureTool`
  - `RefactorCircus\PennantPlus\OffLayeredFeature` → `RefactorCircus\PennantPlus\Domains\Feature\Support\OffLayeredFeature`
  - `RefactorCircus\PennantPlus\OnLayeredFeature` → `RefactorCircus\PennantPlus\Domains\Feature\Support\OnLayeredFeature`
  - `RefactorCircus\PennantPlus\ScopeModel` → `RefactorCircus\PennantPlus\Domains\Scope\Data\ScopeModel`
  - `RefactorCircus\PennantPlus\StoredFeatureValue` → `RefactorCircus\PennantPlus\Domains\Feature\Data\StoredFeatureValue`
  - `RefactorCircus\PennantPlus\Support\ScopeRules` → `RefactorCircus\PennantPlus\Domains\Scope\Support\ScopeRules`
- Requires the domain-module release of `refactor-circus/atrium` (`RefactorCircus\Atrium\Domains\...`).

### Fixed

- The `pennantplus` driver no longer runs a query for every feature it checks.
  - It loads every value stored for a scope in one query, the first time that scope is checked, and answers later checks from memory. Checking many features for one user now costs two queries: the user's values and the global ones.
  - Before, each user check cost two or three queries. The global value was read again without going through Pennant's cache.
  - The driver implements `HasFlushableCache`. Pennant flushes it along with its own cache, at the start of each Octane request, task and tick and after each queued job.
  - Every write through the driver flushes it too. A value written straight to the table by another process shows up after the next flush, the same as with Pennant's own in-memory cache.
- The Feature flags page uses only Atrium's components and the utilities Atrium's stylesheet ships, so every class takes effect. PennantPlus no longer ships `resources/css/atrium.css` or adds styles through `Atrium::css()`.

### Added

- `GET pennantplus/history` (route `pennantplus.history.index`) and the `list-pennantplus-history-tool` MCP tool, from refactor-circus/keystone: PennantPlus's audit entries, newest first. Both answer "not found" until refactor-circus/keen is installed.
- `pennantplus` Pennant driver: stores a scope's value only when it differs from the global value.
- `OnLayeredFeature` and `OffLayeredFeature` base classes (on `LayeredFeature`): the global scope resolves to the class's default, every other scope follows the global value.
- `FeatureGate` and the `EnsureFeatureActive` middleware: global-only features, global-and-user checks for the rest, and a bypass callback.
- A management API (`pennantplus.routes`) and an MCP server (`PennantPlusServer`) to list, show, check, set, forget and purge feature values and find scopes, sharing actions and an optional `pennantplus.ability`.
- The Atrium **Feature flags** page, moved from `refactor-circus/atrium` (`RefactorCircus\PennantPlus\Atrium`). Setting a global value purges every other scope's value of that feature.
- Atrium's feature checks (`NavItem::feature()`, plugin `features()`, the `atrium.feature` middleware) are answered by the feature gate when Atrium is installed. Turn it off with `pennantplus.atrium.resolve_features`.
- The Atrium page's feature field and feature filter are searchable comboboxes over the discovered features (Atrium's `x-atrium::form.combobox`; the package's own combobox partial and `atriumPennantFeature` script are gone).
- `FeatureValueUpdatedActionEvent` implements refactor-circus/keystone's `Auditable`, so its audit entry has no subject and records the `feature`, `scope` and `value`.
- Cortex integration: when `refactor-circus/cortex` is installed, the MCP server registers with it as `pennantplus` and every tool joins its tool registry (tagged `pennantplus`), so agents can use them. Published instruction and tool description overrides are served to MCP clients and agents. Configured under `pennantplus.cortex`; Cortex stays optional.

### Changed

- The architecture test's transaction check counts Pennant's own write calls (`activate`, `deactivate`, `forget`, `purge`, ...), so a write Action without a transaction fails it.
- Requires PHP 8.5 (it was 8.4). Dependency constraints are raised to their latest releases, including Laravel 13.35, Testbench 11.3 and Pest 5.3; CI tests PHP 8.5 only.
- The Atrium **Feature flags** page follows Atrium's screen conventions: every action is an icon button with its label as tooltip, a value's active state is a status dot (`data-status="active|inactive"`), and the navigation item uses the Heroicons `flag` icon. Requires `refactor-circus/atrium` with the icon-button and status-dot components.
- Status and validation messages on the Feature flags page show through `x-atrium::flash`; the Atrium form requests flash `status` instead of `atrium.status`, and the status alert's test id is `flash-status` instead of `pennant-status`.
- The set-value form, each row's toggle and forget buttons, and the purge button are shown only to viewers `pennantplus.ability` allows, through one check (`RefactorCircus\PennantPlus\Atrium\FeatureFlagAccess`, `@pennantplusManages` in views) that the controller and form requests also refuse with. PennantPlus does not gate its own page behind a Pennant feature, so admins cannot lock themselves out of it.
