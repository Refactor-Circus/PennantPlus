---
name: pennantplus-development
description: >
  Configure and apply the PennantPlus package in Laravel applications: a
  Pennant driver that layers per-user values over one global value, a
  FeatureGate (and `feature:` middleware) for routes and MCP tools, and
  feature flag management through a JSON API, an MCP server, and a generated
  TypeScript client.
license: MIT
metadata:
  author: Jay Fletcher
---

# PennantPlus

Use this skill when a Laravel application uses `refactor-circus/pennantplus` to layer Pennant feature flags (one global value, per-user overrides), gate routes or MCP tools on them, or manage stored flag values through the JSON API, the MCP tools, or the TypeScript client.

PennantPlus ships no UI. Atrium v2 provides the optional feature flags screen on top of the JSON API.

## Primary Goal

- apply the `refactor-circus/pennantplus` package's public API in the smallest correct way

## Workflow

### 1. Install and point Pennant at the driver

```bash
composer require refactor-circus/pennantplus
php artisan vendor:publish --tag="pennantplus-config"
```

```php
// config/pennant.php — same options as the `database` driver
'database' => ['driver' => 'pennantplus', 'connection' => null, 'table' => 'features'],
```

A scope (user) with no stored value is resolved and compared with the global (null-scope) value; when they match nothing is written, so the scope keeps following the global value. Only a value that differs from global (an explicit `activate()`/`deactivate()` for that scope, or a resolver rule that treats it differently) is stored.

The driver loads each scope's stored values in one query the first time the scope is checked, then answers from memory. Checking many features for one user therefore costs two queries. `Feature::flushCache()` (run by Pennant per Octane request and per queued job) and every write through the driver clear it. A row written straight to the table shows up only after that flush.

Recommend the optional indexes (never auto-run; PennantPlus owns no tables): `php artisan vendor:publish --tag="pennantplus-migrations" && php artisan migrate` adds `(scope, name)`, `(updated_at)` and `(name, updated_at)` to the managed store's table.

### 2. Write features whose users follow the global value

```php
use RefactorCircus\PennantPlus\Domains\Feature\Support\OffLayeredFeature;
use RefactorCircus\PennantPlus\Domains\Feature\Support\OnLayeredFeature;

class ReportsFeature extends OnLayeredFeature {}           // global default: on

class ExternalWritesFeature extends OffLayeredFeature {}   // global default: off
```

Both extend `LayeredFeature`, whose `resolve()` returns the default for the global scope and the current global value for any other scope.

Keep the global default `false` only for kill switches that must stay dark until flipped. Never fold another flag into a feature's `resolve()`: a stored user value would snapshot both and go stale.

### 3. Gate entry surfaces with the FeatureGate

- `RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureGate::allows($feature, $user)`: features matching `pennantplus.gate.global_only` (default `*SupportFeature`) are checked globally only; every other feature must be active globally **and** for the user (globally only without a user).
- Register a bypass once, e.g. in a service provider: `app(FeatureGate::class)->bypassUsing(fn (Authenticatable $user): bool => $user->hasRole('developer'));`
- Routes: alias `feature` to `RefactorCircus\PennantPlus\Domains\Feature\Http\Middleware\EnsureFeatureActive` and use `->middleware('feature:'.ReportsFeature::class)`; it aborts 403 when the gate refuses.
- MCP tools: return `app(FeatureGate::class)->allows($feature, auth()->user())` from `shouldRegister()`.

### 4. Secure the management surface before exposing it

The API and the MCP server change flags for **every** user. Routes ship on `api` middleware only and both MCP transports are off:

```php
// config/pennantplus.php
'ability' => 'manageFeatures', // optional Gate ability checked by every endpoint and tool
'routes' => ['enabled' => true, 'prefix' => 'pennantplus', 'middleware' => ['api', 'auth:sanctum']],
'mcp' => [
    'web' => ['enabled' => true, 'route' => 'mcp/pennantplus', 'middleware' => ['auth:sanctum']],
    'local' => ['enabled' => true, 'handle' => 'pennantplus'], // php artisan mcp:start pennantplus
],
```

Or register `RefactorCircus\PennantPlus\Mcp\PennantPlusServer` yourself in `routes/ai.php` behind your own auth group. Do not gate the management surface behind a Pennant feature: that could lock admins out of turning it back on. Restrict it with `pennantplus.ability` instead.

### 5. Manage flags via the API (or the matching MCP tools)

A scope is picked with `scope` (as Pennant stores it: `__laravel_null`, `App\Models\User|5`, or any string) or with `scope_type` (`global`, `other`, or a configured model type) plus `scope_id`. Without either, the global scope.

- `GET /pennantplus/features` — every defined, discoverable or stored feature: `name`, `defined`, `global` (stored), `global_stored`, `overrides`. MCP `list-features-tool`.
- `GET /pennantplus/features/{feature}` — the same plus the resolved global `value`. MCP `show-feature-tool` (under `data`, as over HTTP).
- `GET /pennantplus/features/{feature}/check?scope_type=&scope_id=` — `global`, the scope's `value`, `active`, and `gate` (FeatureGate result; null for non-user scopes). MCP `check-feature-tool`.
- `DELETE /pennantplus/features/{feature}` — purge every stored value. MCP `purge-feature-tool`.
- `GET /pennantplus/values?feature=&scope=&scope_id=&page=&per_page=` — stored values, newest first (scope filter: `global`, `other`, or a model type). MCP `list-feature-values-tool`.
- `PUT /pennantplus/values` `{feature, scope|scope_type+scope_id, value}` — store a value (any JSON). Setting the **global** value forgets every other scope's value of that feature (`purge_scopes_on_global_update`), in id chunks after the global value commits. MCP `set-feature-value-tool` takes `value` JSON-encoded (`"true"`, `"false"`, `"{...}"`) and answers the stored value under `data`.
- `DELETE /pennantplus/values` `{feature, scope|scope_type+scope_id}` — forget one scope's value so it follows global again. MCP `forget-feature-value-tool`.
- `GET /pennantplus/scopes` — scope types (cached for `scope_types_cache_seconds`; writes forget the cache). MCP `list-scope-types-tool`.
- `GET /pennantplus/scopes/{type}/models?q=` — find models of a configured type, with the serialized `scope` to reuse. MCP `search-scope-models-tool`.
- `GET /pennantplus/history?subject_type=&subject_id=&action=&cursor=&per_page=` — the package's audit entries, newest first; 404 until refactor-circus/keen is installed (or while `pennantplus.audit.enabled` is false). MCP `list-pennantplus-history-tool`.

Class-based feature names contain backslashes; URL-encode them in paths. Guests get 401, signed-in users the ability refuses 403. Every MCP tool with structured content declares an `outputSchema()`.

From code, call the Actions with Data objects (`RefactorCircus\Keystone\Data\Data`) - never arrays:

```php
app(UpdateFeatureValueAction::class)->execute(new UpdateFeatureValueData('new-checkout', true, scope: Feature::serializeScope($user)));
app(DeleteFeatureValueAction::class)->execute(new DeleteFeatureValueData('new-checkout', scopeType: 'global'));
app(ListFeatureValuesAction::class)->execute(ListFeatureValuesData::from($request->all())); // from() validates untrusted input
```

The Data classes live in `RefactorCircus\PennantPlus\Domains\Feature\Data` (`CheckFeatureData`, `ShowFeatureData`, `PurgeFeatureData`, `ListFeatureValuesData`, `UpdateFeatureValueData`, `DeleteFeatureValueData`) and `Domains\Scope\Data\SearchScopeModelsData`.

### 6. Call the API from TypeScript

`openapi.json` at the package root is the OpenAPI 3.1 document of the JSON API (paths relative to the `/pennantplus` prefix). `client/` is `@refactor-circus/pennantplus-client`, an `openapi-fetch` client typed from it. It is not on a registry: install it as a git dependency on the v2 tag, which builds it on install (pnpm: `pnpm add "github:Refactor-Circus/PennantPlus#v2.0.0&path:/client"`).

```ts
import { createPennantPlusClient } from '@refactor-circus/pennantplus-client';

const pennant = createPennantPlusClient({ baseUrl: '/pennantplus', credentials: 'include' });

const { data, error } = await pennant.GET('/values', { params: { query: { feature: 'new-checkout' } } });
```

`createPennantPlusClient(options)` takes any openapi-fetch option and returns `Client<paths>`; the `paths`, `components` and `operations` types are exported too. `openapi.json`, `client/openapi.json` and `client/src/schema.d.ts` are generated: after an API change, run `npm --prefix client ci` then `composer client:generate` and commit the result. Tests and CI fail on a stale document or client.

### 7. Configure discovery and scope models

```php
'features' => [app_path('Features'), app_path('Domains/*/Features')], // globbed, so features list before first use
'scopes' => [App\Models\User::class => ['label' => 'Users', 'search' => ['name', 'email'], 'title' => 'name']],
```

### 8. Cortex

With `refactor-circus/cortex` installed, the MCP server registers with Cortex as `pennantplus` and every tool joins its tool registry tagged with the server name, so Cortex agents can use them; published instruction and tool description overrides are served to MCP clients and agents. Nothing to register in the app. Configure under `pennantplus.cortex`: `enabled` (false leaves Cortex alone), `server` (the name in Cortex), `tools` (null for all, or a list of tool names). Names already registered in the app's `config/cortex.php` are left alone.

### 9. React to changes

Every write fires an action event pair from `RefactorCircus\PennantPlus\Domains\Feature\Events`: `FeatureValueUpdating`/`FeatureValueUpdated`, `FeatureValueDeleting`/`FeatureValueDeleted`, `FeaturePurging`/`FeaturePurged` (suffixed `ActionEvent`). Reads fire pairs too (`FeaturesListing`, `FeatureShowing`, `FeatureChecking`, `FeatureValuesListing`, and in `Domains\Scope\Events` `ScopeTypesListing`, `ScopeModelsSearching`, with their `-ed`/`Shown` partners); the audit log skips them. Listen to `RefactorCircus\Keystone\Contracts\ActionFinishedEvent` (from refactor-circus/keystone) for all of them; writes run in one transaction on the Pennant store's connection (`FeatureFlagManager::transaction()`) and finished events fire after commit. Starting events carry the input Data (`$event->data`), plus the serialized `$event->scope` on the value events. A stored value's audit entry (refactor-circus/keen) has no subject and carries `feature`, `scope` and `value` context (`FeatureValueUpdatedActionEvent` implements `RefactorCircus\Keystone\Audit\Contracts\Auditable`).

## Rules, References, and Templates

Read before executing:

- `config/pennantplus.php` — store, ability, purge switch, discovery, scopes, gate patterns, routes, MCP transports
- `README.md` — driver, gate, API, OpenAPI and client, and MCP reference
- `openapi.json` — the exact request and response shapes of the JSON API

## Anti-patterns

- do not expose the routes or enable the MCP web transport without auth middleware
- do not change a global value outside the API or the MCP tools (e.g. tinker) and expect user values to follow — only those surfaces purge the stored user values
- do not hand-write `resolve()` returning a hard-coded default for user scopes; extend `OnLayeredFeature` or `OffLayeredFeature` so users follow global flips
- do not check `*SupportFeature` (global-only) flags per user
- do not edit `openapi.json` or the client's `schema.d.ts` by hand; regenerate them with `composer client:generate`
