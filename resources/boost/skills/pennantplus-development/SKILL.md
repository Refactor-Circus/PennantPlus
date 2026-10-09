---
name: pennantplus-development
description: >
  Configure and apply the PennantPlus package in Laravel applications: a
  Pennant driver that layers per-user values over one global value, a
  FeatureGate (and `feature:` middleware) for routes and MCP tools, and
  feature flag management through a REST API, an MCP server, and an Atrium
  dashboard page.
license: MIT
metadata:
  author: Jay Fletcher
---

# PennantPlus

Use this skill when a Laravel application uses `jayi/pennantplus` to layer Pennant feature flags (one global value, per-user overrides), gate routes or MCP tools on them, or manage stored flag values through the API, MCP tools, or the Atrium page.

## Primary Goal

- apply the `jayi/pennantplus` package's public API in the smallest correct way

## Workflow

### 1. Install and point Pennant at the driver

```bash
composer require jayi/pennantplus
php artisan vendor:publish --tag="pennantplus-config"
```

```php
// config/pennant.php — same options as the `database` driver
'database' => ['driver' => 'pennantplus', 'connection' => null, 'table' => 'features'],
```

A scope (user) with no stored value is resolved and compared with the global (null-scope) value; when they match nothing is written, so the scope keeps following the global value. Only a value that differs from global (an explicit `activate()`/`deactivate()` for that scope, or a resolver rule that treats it differently) is stored.

The driver loads each scope's stored values in one query the first time the scope is checked, then answers from memory. Checking many features for one user therefore costs two queries. `Feature::flushCache()` (run by Pennant per Octane request and per queued job) and every write through the driver clear it. A row written straight to the table shows up only after that flush.

### 2. Write features whose users follow the global value

```php
use JayI\PennantPlus\Domains\Feature\Support\OffLayeredFeature;
use JayI\PennantPlus\Domains\Feature\Support\OnLayeredFeature;

class ReportsFeature extends OnLayeredFeature {}           // global default: on

class ExternalWritesFeature extends OffLayeredFeature {}   // global default: off
```

Both extend `LayeredFeature`, whose `resolve()` returns the default for the global scope and the current global value for any other scope.

Keep the global default `false` only for kill switches that must stay dark until flipped. Never fold another flag into a feature's `resolve()`: a stored user value would snapshot both and go stale.

### 3. Gate entry surfaces with the FeatureGate

- `JayI\PennantPlus\Domains\Feature\Services\FeatureGate::allows($feature, $user)`: features matching `pennantplus.gate.global_only` (default `*SupportFeature`) are checked globally only; every other feature must be active globally **and** for the user (globally only without a user).
- Register a bypass once, e.g. in a service provider: `app(FeatureGate::class)->bypassUsing(fn (Authenticatable $user): bool => $user->hasRole('developer'));`
- Routes: alias `feature` to `JayI\PennantPlus\Domains\Feature\Http\Middleware\EnsureFeatureActive` and use `->middleware('feature:'.ReportsFeature::class)`; it aborts 403 when the gate refuses.
- MCP tools: return `app(FeatureGate::class)->allows($feature, auth()->user())` from `shouldRegister()`.

### 4. Secure the management surface before exposing it

The API, MCP server and dashboard change flags for **every** user. Routes ship on `api` middleware only and both MCP transports are off:

```php
// config/pennantplus.php
'ability' => 'manageFeatures', // optional Gate ability checked by every endpoint, tool and the page
'routes' => ['enabled' => true, 'prefix' => 'pennantplus', 'middleware' => ['api', 'auth:sanctum']],
'mcp' => [
    'web' => ['enabled' => true, 'route' => 'mcp/pennantplus', 'middleware' => ['auth:sanctum']],
    'local' => ['enabled' => true, 'handle' => 'pennantplus'], // php artisan mcp:start pennantplus
],
```

Or register `JayI\PennantPlus\Mcp\PennantPlusServer` yourself in `routes/ai.php` behind your own auth group.

### 5. Manage flags via the API (or the matching MCP tools)

A scope is picked with `scope` (as Pennant stores it: `__laravel_null`, `App\Models\User|5`, or any string) or with `scope_type` (`global`, `other`, or a configured model type) plus `scope_id`. Without either, the global scope.

- `GET /pennantplus/features` — every defined, discoverable or stored feature: `name`, `defined`, `global` (stored), `global_stored`, `overrides`. MCP `list-features-tool`.
- `GET /pennantplus/features/{feature}` — the same plus the resolved global `value`. MCP `show-feature-tool`.
- `GET /pennantplus/features/{feature}/check?scope_type=&scope_id=` — `global`, the scope's `value`, `active`, and `gate` (FeatureGate result; null for non-user scopes). MCP `check-feature-tool`.
- `DELETE /pennantplus/features/{feature}` — purge every stored value. MCP `purge-feature-tool`.
- `GET /pennantplus/values?feature=&scope=&scope_id=&page=` — stored values, newest first (scope filter: `global`, `other`, or a model type). MCP `list-feature-values-tool`.
- `PUT /pennantplus/values` `{feature, scope|scope_type+scope_id, value}` — store a value (any JSON). Setting the **global** value forgets every other scope's value of that feature (`purge_scopes_on_global_update`). MCP `set-feature-value-tool` takes `value` JSON-encoded (`"true"`, `"false"`, `"{...}"`).
- `DELETE /pennantplus/values` `{feature, scope|scope_type+scope_id}` — forget one scope's value so it follows global again. MCP `forget-feature-value-tool`.
- `GET /pennantplus/scopes` — scope types. MCP `list-scope-types-tool`.
- `GET /pennantplus/scopes/{type}/models?q=` — find models of a configured type, with the serialized `scope` to reuse. MCP `search-scope-models-tool`.
- `GET /pennantplus/history?subject_type=&subject_id=&action=&cursor=&per_page=` — the package's audit entries, newest first; 404 until jayi/keen is installed. MCP `list-pennantplus-history-tool`.

Class-based feature names contain backslashes; URL-encode them in paths.

### 6. Configure discovery and scope models

```php
'features' => [app_path('Features'), app_path('Domains/*/Features')], // globbed, so features list before first use
'scopes' => [App\Models\User::class => ['label' => 'Users', 'search' => ['name', 'email'], 'title' => 'name']],
```

### 7. The Atrium page

With `jayi/atrium` installed, Atrium discovers a **Feature flags** page (plugin key `pennant`, routes `atrium.pennant.*`). Hide it with `atrium.disabled => ['pennant']`.

The page uses Atrium icon buttons and status dots (`data-status="active|inactive"`, assert on that and on `data-testid`, not on button text) and a `flag` nav icon. Its navigation item and every control (set value, toggle, forget, purge) show only when `JayI\PennantPlus\Atrium\FeatureFlagAccess::allows()` passes - the same `pennantplus.ability` check the controller and form requests refuse with; published views can use `@pennantplusManages ... @endpennantplusManages`. Do not gate the page behind a Pennant feature: that could lock admins out of turning it back on.

The page uses only `x-atrium::*` components and Atrium's safelisted utilities (no package stylesheet): `x-atrium::form.combobox` for the feature fields (`data-testid="pennant-feature"` / `"pennant-filter-feature"`, free text allowed), `x-atrium::flash` for status (`data-testid="flash-status"`), and `x-atrium::audit-trail source="pennantplus"`, which shows the package-wide history once jayi/keen is installed. A stored value's audit entry has no subject and carries `feature`, `scope` and `value` context (`FeatureValueUpdatedActionEvent` implements `JayI\Foundation\Audit\Contracts\Auditable`).

PennantPlus also registers Atrium's feature resolver, so `NavItem::feature()`, a plugin's `features()`, and the `atrium.feature` middleware ask `FeatureGate::allows($feature, $request->user())`. `pennantplus.gate.global_only => ['*']` makes Pennant decide only global visibility, leaving per-user visibility to Atrium's `can()` permissions. `pennantplus.atrium.resolve_features => false` opts out.

### 8. Cortex

With `jayi/cortex` installed, the MCP server registers with Cortex as `pennantplus` and every tool joins its tool registry tagged with the server name, so Cortex agents can use them; published instruction and tool description overrides are served to MCP clients and agents. Nothing to register in the app. Configure under `pennantplus.cortex`: `enabled` (false leaves Cortex alone), `server` (the name in Cortex), `tools` (null for all, or a list of tool names). Names already registered in the app's `config/cortex.php` are left alone.

### 9. React to changes

Every write fires an action event pair from `JayI\PennantPlus\Domains\Feature\Events`: `FeatureValueUpdating`/`FeatureValueUpdated`, `FeatureValueDeleting`/`FeatureValueDeleted`, `FeaturePurging`/`FeaturePurged` (suffixed `ActionEvent`). Listen to `JayI\Foundation\Contracts\ActionFinishedEvent` (from jayi/foundation) for all of them; finished events fire after commit.

## Rules, References, and Templates

Read before executing:

- `config/pennantplus.php` — store, ability, purge switch, discovery, scopes, gate patterns, routes, MCP transports
- `README.md` — driver, gate, API and MCP reference

## Anti-patterns

- do not expose the routes or enable the MCP web transport without auth middleware
- do not change a global value outside the API/MCP/page (e.g. tinker) and expect user values to follow — only those surfaces purge the stored user values
- do not hand-write `resolve()` returning a hard-coded default for user scopes; extend `OnLayeredFeature` or `OffLayeredFeature` so users follow global flips
- do not check `*SupportFeature` (global-only) flags per user
