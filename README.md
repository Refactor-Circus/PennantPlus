# PennantPlus

Layered [Laravel Pennant](https://laravel.com/docs/pennant) feature flags: one global value per feature with per-user overrides, a gate for entry surfaces (routes, MCP tools), and an optional [Atrium](https://github.com/jayjfletcher/Atrium) page for managing stored values.

## Installation

```bash
composer require jayi/pennantplus
php artisan vendor:publish --tag="pennantplus-config"
```

## The `pennantplus` driver

Point your Pennant store at the `pennantplus` driver. It takes the same `connection` and `table` options as `database`:

```php
// config/pennant.php
'database' => [
    'driver' => 'pennantplus',
    'connection' => null,
    'table' => 'features',
],
```

For a non-null scope with no stored value, the driver resolves the feature and compares it with the global (null-scope) value. When they match nothing is written, so the scope keeps following the global value. Only a value that differs from global is stored. Explicit `activate()` / `deactivate()` calls store as usual.

Pair it with features whose user value follows the global one:

```php
use JayI\PennantPlus\OffLayeredFeature;
use JayI\PennantPlus\OnLayeredFeature;

class ReportsFeature extends OnLayeredFeature {}           // global default: on

class ExternalWritesFeature extends OffLayeredFeature {}   // global default: off
```

Both extend `LayeredFeature`, whose `resolve()` returns the default for the global scope and the current global value for any other scope.

## The feature gate

`JayI\PennantPlus\FeatureGate::allows($feature, $user)`:

- features matching `pennantplus.gate.global_only` (default `*SupportFeature`) are checked against the global scope only;
- every other feature must be active globally **and** for the user (globally only when there is no user);
- users accepted by the bypass callback pass every feature.

```php
app(FeatureGate::class)->bypassUsing(fn (Authenticatable $user): bool => $user->isDeveloper());
```

Use it on routes through the bundled middleware:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias(['feature' => \JayI\PennantPlus\Http\Middleware\EnsureFeatureActive::class]);
})

Route::get('/reports', ReportController::class)->middleware('feature:'.ReportsFeature::class);
```

The middleware aborts with a 403 when the gate refuses.

## Management API

Enabled by default under `pennantplus.routes` (prefix `pennantplus`, `api` middleware — add auth before exposing it). Every endpoint, MCP tool and the Atrium page also checks the optional `pennantplus.ability` Gate ability.

A scope is picked with `scope` (as Pennant stores it: `__laravel_null`, `App\Models\User|5`, or any string) or with `scope_type` (`global`, `other`, or a configured model type) plus `scope_id`; without either, the global scope.

| Method | Path | |
|---|---|---|
| GET | `features` | Every defined, discoverable or stored feature with its stored global value and override count |
| GET | `features/{feature}` | One feature, plus its resolved global value |
| GET | `features/{feature}/check` | How the feature resolves for a scope, and whether the FeatureGate lets it through |
| DELETE | `features/{feature}` | Purge every stored value |
| GET | `values` | Stored values, filtered by `feature`, `scope`, `scope_id`; paginated |
| PUT | `values` | Store `{feature, scope…, value}`; a global value purges every other scope's value |
| DELETE | `values` | Forget one scope's value |
| GET | `scopes` | Scope types |
| GET | `scopes/{type}/models?q=` | Find models of a configured scope type |

Feature names that are class names contain backslashes: URL-encode them.

## MCP server

`JayI\PennantPlus\Mcp\PennantPlusServer` exposes the same operations as nine tools behind ToolSearch: `list-features-tool`, `show-feature-tool`, `check-feature-tool`, `purge-feature-tool`, `list-feature-values-tool`, `set-feature-value-tool` (value JSON-encoded), `forget-feature-value-tool`, `list-scope-types-tool`, `search-scope-models-tool`. Enable its transports under `pennantplus.mcp`, or register it yourself behind your own auth.

## Cortex

When [`jayi/cortex`](https://github.com/jayjfletcher/cortex) is installed, PennantPlus connects its MCP server to it. Nothing needs registering in your app.

- **Agents can manage flags.** Every PennantPlus MCP tool joins Cortex's tool registry under its own name (`list-features-tool`, `check-feature-tool`, `set-feature-value-tool`, ...), so an agent can inspect and change feature flags. They are tagged with the server name (`pennantplus`), so they filter together in the Cortex dashboard.
- **Instructions and descriptions can change without a deploy.** The server is registered with Cortex as `pennantplus`, so its instructions get Cortex's versioned, publishable overrides, and so does each tool's description. Published overrides are served both to MCP clients and to agents.

```php
// config/pennantplus.php
'cortex' => [
    'enabled' => true,          // false leaves Cortex alone
    'server' => 'pennantplus',  // the server's name in Cortex
    'tools' => null,            // null for every tool, or a list of names
],
```

About how it works:

- **Optional:** Cortex is not a dependency. Without it nothing Cortex-related loads.
- **Lazy:** registration happens the first time Cortex's registries are used.
- **Your config wins:** a name already registered with Cortex, for example in your own `config/cortex.php`, is left alone.
- **Tool list from the server:** the tools come from `PennantPlusServer::TOOLS`, which is also the catalog the server serves, so a tool added to PennantPlus reaches Cortex without any change in your app.

## Atrium dashboard

With `jayi/atrium` installed, Atrium discovers a **Feature flags** page (plugin key `pennant`). It lists stored values by feature and scope, sets a value for the global scope, a configured model or any string scope, forgets a value, and purges a feature. Setting a **global** value forgets every other scope's value of that feature (`pennantplus.purge_scopes_on_global_update`). It uses the same `store`, `ability`, `features` and `scopes` settings as the API.

The page follows Atrium's screen conventions: actions are icon buttons (the label is the tooltip and accessible name), a value's state is a status dot (`success` active, `neutral` inactive; read it from `data-status`), and the navigation item uses the Heroicons `flag` icon. The navigation item, the set-value form, each row's toggle and forget buttons, and the purge button are shown only when `pennantplus.ability` allows the viewer - asked through `JayI\PennantPlus\Atrium\FeatureFlagAccess::allows()`, the same check the page's controller and form requests refuse with. Your own published views can use it as `@pennantplusManages ... @endpennantplusManages`.

PennantPlus deliberately has no Pennant feature switch of its own for the Feature flags page: gating the feature-flag manager behind a feature flag could lock admins out of turning it back on. Restrict it with `pennantplus.ability`, or hide it with `atrium.disabled`.

PennantPlus also answers Atrium's feature checks - `NavItem::feature()`, a plugin's `features()`, and the `atrium.feature` middleware - through the feature gate, so `global_only` and the bypass callback apply in the dashboard exactly as they do on routes and MCP tools. To have Pennant decide only global visibility and leave per-user visibility to permissions (`NavItem::can()`), set `pennantplus.gate.global_only` to `['*']`. Set `pennantplus.atrium.resolve_features` to `false` to leave Atrium's feature resolver alone.

Every write fires an action event pair from `JayI\PennantPlus\Events\Action`: `FeatureValueUpdating`/`FeatureValueUpdated`, `FeatureValueDeleting`/`FeatureValueDeleted`, and `FeaturePurging`/`FeaturePurged` (each suffixed `ActionEvent`).

## Testing

```bash
composer test
```

Local development against a sibling Atrium checkout uses a gitignored `composer.local.json` with a path repository to `../Atrium`: `COMPOSER=composer.local.json composer update`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.
