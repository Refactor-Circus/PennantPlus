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
use JayI\PennantPlus\LayeredFeature;

class ReportsFeature extends LayeredFeature {}   // global default: on

class HubWritesFeature extends LayeredFeature    // global default: off
{
    protected function default(): bool
    {
        return false;
    }
}
```

`LayeredFeature::resolve()` returns `default()` for the global scope and the current global value for any other scope.

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

## Atrium dashboard

With `jayi/atrium` installed, Atrium discovers a **Feature flags** page (plugin key `pennant`). It lists stored values by feature and scope, sets a value for the global scope, a configured model or any string scope, forgets a value, and purges a feature. Setting a **global** value forgets every other scope's value of that feature (`pennantplus.purge_scopes_on_global_update`). It uses the same `store`, `ability`, `features` and `scopes` settings as the API.

Every write fires an action event pair from `JayI\PennantPlus\Events\Action`: `FeatureValueUpdating`/`FeatureValueUpdated`, `FeatureValueDeleting`/`FeatureValueDeleted`, and `FeaturePurging`/`FeaturePurged` (each suffixed `ActionEvent`).

## Testing

```bash
composer test
```

Local development against a sibling Atrium checkout uses a gitignored `composer.local.json` with a path repository to `../Atrium`: `COMPOSER=composer.local.json composer update`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.
