# Release Notes

## [Unreleased](https://github.com/jayjfletcher/PennantPlus/compare/v0.1.0...main)

### Added

- An **Audit log** link in the package's sidebar group, opening its own audit log in Atrium (`/atrium/history/pennantplus`), shown while an audit log (jayi/keen) is installed and to those who may read the package's history.
- `Atrium\Features\ThemeSwitcherFeature`, the Pennant feature Atrium's theme switcher shows behind: on until turned off, globally or per user.

### Breaking

- PennantPlus now stands on `jayi/foundation`, the suite's shared runtime, and requires it. The package's own copies of the shared pieces are gone in favour of Foundation's; update any imports:
  - `JayI\PennantPlus\Contracts\ActionStartingEvent` → `JayI\Foundation\Contracts\ActionStartingEvent`
  - `JayI\PennantPlus\Contracts\ActionFinishedEvent` → `JayI\Foundation\Contracts\ActionFinishedEvent`
  - `JayI\PennantPlus\Mcp\Tool` → `JayI\Foundation\Mcp\Tool`
  - `JayI\PennantPlus\Support\ServiceProvider` → `JayI\Foundation\Support\ServiceProvider`
  - `JayI\PennantPlus\Cortex\CortexIntegration` → `JayI\Foundation\Cortex\CortexIntegration` (built with `CortexIntegration::for($package)` rather than resolved from the container)

  `PennantPlusServiceProvider` extends Foundation's `PackageServiceProvider` and registers the package as `pennantplus`; `PennantPlusServer` extends `JayI\Foundation\Mcp\Server`; the base `Http\Request` and `Mcp\Request` extend Foundation's and keep the `pennantplus.ability` check. MCP calls now run inside Foundation's `mcp` surface, and changes a Cortex agent makes through a PennantPlus tool are marked with the `cortex` surface. Config keys, route names, tool names and behaviour are otherwise unchanged.

- The package is reorganised into domain modules (`src/Domains/Feature`, `src/Domains/Scope`), mirroring the mono application's layout. Classes move namespaces; there are no aliases for the old names, so update imports. Config keys, route names and paths, MCP tool names, publish tags, views, translations, the `pennantplus` driver name and the Atrium plugin are unchanged, and nothing PennantPlus stores is keyed by its own class names, so stored feature values are untouched. The management API routes now load from each domain (`routes/pennantplus.php` is gone). Old → new:
  - `JayI\PennantPlus\Actions\CheckFeatureAction` → `JayI\PennantPlus\Domains\Feature\Actions\CheckFeatureAction`
  - `JayI\PennantPlus\Actions\DeleteFeatureValueAction` → `JayI\PennantPlus\Domains\Feature\Actions\DeleteFeatureValueAction`
  - `JayI\PennantPlus\Actions\ListFeatureValuesAction` → `JayI\PennantPlus\Domains\Feature\Actions\ListFeatureValuesAction`
  - `JayI\PennantPlus\Actions\ListFeaturesAction` → `JayI\PennantPlus\Domains\Feature\Actions\ListFeaturesAction`
  - `JayI\PennantPlus\Actions\ListScopeTypesAction` → `JayI\PennantPlus\Domains\Scope\Actions\ListScopeTypesAction`
  - `JayI\PennantPlus\Actions\PurgeFeatureAction` → `JayI\PennantPlus\Domains\Feature\Actions\PurgeFeatureAction`
  - `JayI\PennantPlus\Actions\SearchScopeModelsAction` → `JayI\PennantPlus\Domains\Scope\Actions\SearchScopeModelsAction`
  - `JayI\PennantPlus\Actions\ShowFeatureAction` → `JayI\PennantPlus\Domains\Feature\Actions\ShowFeatureAction`
  - `JayI\PennantPlus\Actions\UpdateFeatureValueAction` → `JayI\PennantPlus\Domains\Feature\Actions\UpdateFeatureValueAction`
  - `JayI\PennantPlus\Drivers\GlobalAwareDatabaseDriver` → `JayI\PennantPlus\Domains\Feature\Support\GlobalAwareDatabaseDriver`
  - `JayI\PennantPlus\Events\Action\FeaturePurgedActionEvent` → `JayI\PennantPlus\Domains\Feature\Events\FeaturePurgedActionEvent`
  - `JayI\PennantPlus\Events\Action\FeaturePurgingActionEvent` → `JayI\PennantPlus\Domains\Feature\Events\FeaturePurgingActionEvent`
  - `JayI\PennantPlus\Events\Action\FeatureValueDeletedActionEvent` → `JayI\PennantPlus\Domains\Feature\Events\FeatureValueDeletedActionEvent`
  - `JayI\PennantPlus\Events\Action\FeatureValueDeletingActionEvent` → `JayI\PennantPlus\Domains\Feature\Events\FeatureValueDeletingActionEvent`
  - `JayI\PennantPlus\Events\Action\FeatureValueUpdatedActionEvent` → `JayI\PennantPlus\Domains\Feature\Events\FeatureValueUpdatedActionEvent`
  - `JayI\PennantPlus\Events\Action\FeatureValueUpdatingActionEvent` → `JayI\PennantPlus\Domains\Feature\Events\FeatureValueUpdatingActionEvent`
  - `JayI\PennantPlus\Exceptions\InvalidScopeModelException` → `JayI\PennantPlus\Domains\Scope\Exceptions\InvalidScopeModelException`
  - `JayI\PennantPlus\FeatureFlagManager` → `JayI\PennantPlus\Domains\Feature\Services\FeatureFlagManager`
  - `JayI\PennantPlus\FeatureGate` → `JayI\PennantPlus\Domains\Feature\Services\FeatureGate`
  - `JayI\PennantPlus\Http\Controllers\FeatureController` → `JayI\PennantPlus\Domains\Feature\Http\Controllers\FeatureController`
  - `JayI\PennantPlus\Http\Controllers\FeatureValueController` → `JayI\PennantPlus\Domains\Feature\Http\Controllers\FeatureValueController`
  - `JayI\PennantPlus\Http\Controllers\ScopeController` → `JayI\PennantPlus\Domains\Scope\Http\Controllers\ScopeController`
  - `JayI\PennantPlus\Http\Middleware\EnsureFeatureActive` → `JayI\PennantPlus\Domains\Feature\Http\Middleware\EnsureFeatureActive`
  - `JayI\PennantPlus\Http\Requests\CheckFeatureRequest` → `JayI\PennantPlus\Domains\Feature\Http\Requests\CheckFeatureRequest`
  - `JayI\PennantPlus\Http\Requests\DeleteFeatureValueRequest` → `JayI\PennantPlus\Domains\Feature\Http\Requests\DeleteFeatureValueRequest`
  - `JayI\PennantPlus\Http\Requests\IndexFeatureValuesRequest` → `JayI\PennantPlus\Domains\Feature\Http\Requests\IndexFeatureValuesRequest`
  - `JayI\PennantPlus\Http\Requests\IndexFeaturesRequest` → `JayI\PennantPlus\Domains\Feature\Http\Requests\IndexFeaturesRequest`
  - `JayI\PennantPlus\Http\Requests\IndexScopeTypesRequest` → `JayI\PennantPlus\Domains\Scope\Http\Requests\IndexScopeTypesRequest`
  - `JayI\PennantPlus\Http\Requests\PurgeFeatureRequest` → `JayI\PennantPlus\Domains\Feature\Http\Requests\PurgeFeatureRequest`
  - `JayI\PennantPlus\Http\Requests\SearchScopeModelsRequest` → `JayI\PennantPlus\Domains\Scope\Http\Requests\SearchScopeModelsRequest`
  - `JayI\PennantPlus\Http\Requests\ShowFeatureRequest` → `JayI\PennantPlus\Domains\Feature\Http\Requests\ShowFeatureRequest`
  - `JayI\PennantPlus\Http\Requests\UpdateFeatureValueRequest` → `JayI\PennantPlus\Domains\Feature\Http\Requests\UpdateFeatureValueRequest`
  - `JayI\PennantPlus\Http\Resources\FeatureResource` → `JayI\PennantPlus\Domains\Feature\Http\Resources\FeatureResource`
  - `JayI\PennantPlus\Http\Resources\FeatureValueResource` → `JayI\PennantPlus\Domains\Feature\Http\Resources\FeatureValueResource`
  - `JayI\PennantPlus\LayeredFeature` → `JayI\PennantPlus\Domains\Feature\Support\LayeredFeature`
  - `JayI\PennantPlus\Mcp\Requests\CheckFeatureMcpRequest` → `JayI\PennantPlus\Domains\Feature\Mcp\Requests\CheckFeatureMcpRequest`
  - `JayI\PennantPlus\Mcp\Requests\ForgetFeatureValueMcpRequest` → `JayI\PennantPlus\Domains\Feature\Mcp\Requests\ForgetFeatureValueMcpRequest`
  - `JayI\PennantPlus\Mcp\Requests\ListFeatureValuesMcpRequest` → `JayI\PennantPlus\Domains\Feature\Mcp\Requests\ListFeatureValuesMcpRequest`
  - `JayI\PennantPlus\Mcp\Requests\ListFeaturesMcpRequest` → `JayI\PennantPlus\Domains\Feature\Mcp\Requests\ListFeaturesMcpRequest`
  - `JayI\PennantPlus\Mcp\Requests\ListScopeTypesMcpRequest` → `JayI\PennantPlus\Domains\Scope\Mcp\Requests\ListScopeTypesMcpRequest`
  - `JayI\PennantPlus\Mcp\Requests\PurgeFeatureMcpRequest` → `JayI\PennantPlus\Domains\Feature\Mcp\Requests\PurgeFeatureMcpRequest`
  - `JayI\PennantPlus\Mcp\Requests\SearchScopeModelsMcpRequest` → `JayI\PennantPlus\Domains\Scope\Mcp\Requests\SearchScopeModelsMcpRequest`
  - `JayI\PennantPlus\Mcp\Requests\SetFeatureValueMcpRequest` → `JayI\PennantPlus\Domains\Feature\Mcp\Requests\SetFeatureValueMcpRequest`
  - `JayI\PennantPlus\Mcp\Requests\ShowFeatureMcpRequest` → `JayI\PennantPlus\Domains\Feature\Mcp\Requests\ShowFeatureMcpRequest`
  - `JayI\PennantPlus\Mcp\Tools\CheckFeatureTool` → `JayI\PennantPlus\Domains\Feature\Mcp\Tools\CheckFeatureTool`
  - `JayI\PennantPlus\Mcp\Tools\Concerns\DescribesScope` → `JayI\PennantPlus\Domains\Scope\Concerns\DescribesScope`
  - `JayI\PennantPlus\Mcp\Tools\ForgetFeatureValueTool` → `JayI\PennantPlus\Domains\Feature\Mcp\Tools\ForgetFeatureValueTool`
  - `JayI\PennantPlus\Mcp\Tools\ListFeatureValuesTool` → `JayI\PennantPlus\Domains\Feature\Mcp\Tools\ListFeatureValuesTool`
  - `JayI\PennantPlus\Mcp\Tools\ListFeaturesTool` → `JayI\PennantPlus\Domains\Feature\Mcp\Tools\ListFeaturesTool`
  - `JayI\PennantPlus\Mcp\Tools\ListScopeTypesTool` → `JayI\PennantPlus\Domains\Scope\Mcp\Tools\ListScopeTypesTool`
  - `JayI\PennantPlus\Mcp\Tools\PurgeFeatureTool` → `JayI\PennantPlus\Domains\Feature\Mcp\Tools\PurgeFeatureTool`
  - `JayI\PennantPlus\Mcp\Tools\SearchScopeModelsTool` → `JayI\PennantPlus\Domains\Scope\Mcp\Tools\SearchScopeModelsTool`
  - `JayI\PennantPlus\Mcp\Tools\SetFeatureValueTool` → `JayI\PennantPlus\Domains\Feature\Mcp\Tools\SetFeatureValueTool`
  - `JayI\PennantPlus\Mcp\Tools\ShowFeatureTool` → `JayI\PennantPlus\Domains\Feature\Mcp\Tools\ShowFeatureTool`
  - `JayI\PennantPlus\OffLayeredFeature` → `JayI\PennantPlus\Domains\Feature\Support\OffLayeredFeature`
  - `JayI\PennantPlus\OnLayeredFeature` → `JayI\PennantPlus\Domains\Feature\Support\OnLayeredFeature`
  - `JayI\PennantPlus\ScopeModel` → `JayI\PennantPlus\Domains\Scope\Data\ScopeModel`
  - `JayI\PennantPlus\StoredFeatureValue` → `JayI\PennantPlus\Domains\Feature\Data\StoredFeatureValue`
  - `JayI\PennantPlus\Support\ScopeRules` → `JayI\PennantPlus\Domains\Scope\Support\ScopeRules`
- Requires the domain-module release of `jayi/atrium` (`JayI\Atrium\Domains\...`).

### Fixed

- The Feature flags page uses only Atrium's components and the utilities Atrium's stylesheet ships, so every class takes effect. PennantPlus no longer ships `resources/css/atrium.css` or adds styles through `Atrium::css()`.

### Added

- `GET pennantplus/history` (route `pennantplus.history.index`) and the `list-pennantplus-history-tool` MCP tool, from jayi/foundation: PennantPlus's audit entries, newest first. Both answer "not found" until jayi/keen is installed.
- `pennantplus` Pennant driver: stores a scope's value only when it differs from the global value.
- `OnLayeredFeature` and `OffLayeredFeature` base classes (on `LayeredFeature`): the global scope resolves to the class's default, every other scope follows the global value.
- `FeatureGate` and the `EnsureFeatureActive` middleware: global-only features, global-and-user checks for the rest, and a bypass callback.
- A management API (`pennantplus.routes`) and an MCP server (`PennantPlusServer`) to list, show, check, set, forget and purge feature values and find scopes, sharing actions and an optional `pennantplus.ability`.
- The Atrium **Feature flags** page, moved from `jayi/atrium` (`JayI\PennantPlus\Atrium`). Setting a global value purges every other scope's value of that feature.
- Atrium's feature checks (`NavItem::feature()`, plugin `features()`, the `atrium.feature` middleware) are answered by the feature gate when Atrium is installed. Turn it off with `pennantplus.atrium.resolve_features`.
- The Atrium page's feature field and feature filter are searchable comboboxes over the discovered features (Atrium's `x-atrium::form.combobox`; the package's own combobox partial and `atriumPennantFeature` script are gone).
- The Feature flags page shows PennantPlus's history through `<x-atrium::audit-trail source="pennantplus" />` once an audit log (jayi/keen) is installed, package-wide even when one feature is filtered.
- `FeatureValueUpdatedActionEvent` implements jayi/foundation's `Auditable`, so its audit entry has no subject and records the `feature`, `scope` and `value`.
- Cortex integration: when `jayi/cortex` is installed, the MCP server registers with it as `pennantplus` and every tool joins its tool registry (tagged `pennantplus`), so agents can use them. Published instruction and tool description overrides are served to MCP clients and agents. Configured under `pennantplus.cortex`; Cortex stays optional.

### Changed

- The Atrium **Feature flags** page follows Atrium's screen conventions: every action is an icon button with its label as tooltip, a value's active state is a status dot (`data-status="active|inactive"`), and the navigation item uses the Heroicons `flag` icon. Requires `jayi/atrium` with the icon-button and status-dot components.
- Status and validation messages on the Feature flags page show through `x-atrium::flash`; the Atrium form requests flash `status` instead of `atrium.status`, and the status alert's test id is `flash-status` instead of `pennant-status`.
- The set-value form, each row's toggle and forget buttons, and the purge button are shown only to viewers `pennantplus.ability` allows, through one check (`JayI\PennantPlus\Atrium\FeatureFlagAccess`, `@pennantplusManages` in views) that the controller and form requests also refuse with. PennantPlus does not gate its own page behind a Pennant feature, so admins cannot lock themselves out of it.
