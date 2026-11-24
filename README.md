<p align="center">
    <img src="art/icon.png" width="160" alt="PennantPlus icon">
</p>

# PennantPlus

Layered [Laravel Pennant](https://laravel.com/docs/pennant) feature flags: one global value per feature with per-user overrides, a gate for entry surfaces (routes, MCP tools), and a JSON API and an MCP server for managing stored values. The API is described by a committed OpenAPI document and has a generated TypeScript client.

PennantPlus also ships its [Atrium v2](https://github.com/Refactor-Circus/Atrium) front-end module: the optional feature flags screen, built on the JSON API and the client, which Atrium v2 loads when it is installed (see [Atrium module](#atrium-module)).

## Installation

```bash
composer require refactor-circus/pennantplus
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

The driver reads stored values one scope at a time:

- The first check against a scope loads every value stored for it in one query. Later checks against that scope are answered from memory.
- Checking many features for one user costs two queries: the user's values and the global ones. A gate in front of many routes or MCP tools stays cheap.
- Pennant flushes the loaded values with its own cache: `Feature::flushCache()`, which runs at the start of each Octane request, task and tick and after each queued job.
- Every write through the driver flushes them too.

### Recommended indexes

Pennant's `features` table only has a unique index on `(name, scope)`, which cannot serve the driver's per-scope read (`where scope = ?`) or the newest-first listing of stored values (`GET values`). PennantPlus owns no tables, so it ships the indexes as an optional migration that is never run unless you publish it:

```bash
php artisan vendor:publish --tag="pennantplus-migrations"
php artisan migrate
```

It adds `(scope, name)`, `(updated_at)` and `(name, updated_at)` to the managed store's table (`pennantplus.store`, else Pennant's default store), named after the table (`features_scope_name_index`, ...). It is recommended for any store using the `pennantplus` driver. Publishing everything with `--tag=pennantplus` leaves it out.

Pair it with features whose user value follows the global one:

```php
use RefactorCircus\PennantPlus\Domains\Feature\Support\OffLayeredFeature;
use RefactorCircus\PennantPlus\Domains\Feature\Support\OnLayeredFeature;

class ReportsFeature extends OnLayeredFeature {}           // global default: on

class ExternalWritesFeature extends OffLayeredFeature {}   // global default: off
```

Both extend `LayeredFeature`, whose `resolve()` returns the default for the global scope and the current global value for any other scope.

## The feature gate

`RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureGate::allows($feature, $user)`:

- features matching `pennantplus.gate.global_only` (default `*SupportFeature`) are checked against the global scope only;
- every other feature must be active globally **and** for the user (globally only when there is no user);
- users accepted by the bypass callback pass every feature.

```php
app(FeatureGate::class)->bypassUsing(fn (Authenticatable $user): bool => $user->isDeveloper());
```

Use it on routes through the bundled middleware:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias(['feature' => \RefactorCircus\PennantPlus\Domains\Feature\Http\Middleware\EnsureFeatureActive::class]);
})

Route::get('/reports', ReportController::class)->middleware('feature:'.ReportsFeature::class);
```

The middleware aborts with a 403 when the gate refuses.

## Management API

Enabled by default under `pennantplus.routes` (prefix `pennantplus`, `api` middleware — add auth before exposing it). Every endpoint and MCP tool also checks the optional `pennantplus.ability` Gate ability. It uses the managed store (`pennantplus.store`), the discovered `features` and the configured `scopes`.

A scope is picked with `scope` (as Pennant stores it: `__laravel_null`, `App\Models\User|5`, or any string) or with `scope_type` (`global`, `other`, or a configured model type) plus `scope_id`; without either, the global scope.

| Method | Path | |
|---|---|---|
| GET | `features` | Every defined, discoverable or stored feature with its stored global value and override count |
| GET | `features/{feature}` | One feature, plus its resolved global value |
| GET | `features/{feature}/check` | How the feature resolves for a scope, and whether the FeatureGate lets it through |
| DELETE | `features/{feature}` | Purge every stored value |
| GET | `values` | Stored values, filtered by `feature`, `scope`, `scope_id`; paginated (`page`, `per_page` up to 100, default 25) |
| PUT | `values` | Store `{feature, scope…, value}`; a global value purges every other scope's value |
| DELETE | `values` | Forget one scope's value |
| GET | `scopes` | Scope types |
| GET | `scopes/{type}/models?q=` | Find models of a configured scope type |

Feature names that are class names contain backslashes: URL-encode them.

Setting a **global** value forgets every other scope's value of that feature (`pennantplus.purge_scopes_on_global_update`). Those values are deleted once the global value is committed, a thousand rows per query, so a heavily overridden feature never holds one long transaction. The list of model types that have values (`GET scopes`) is cached for `pennantplus.scope_types_cache_seconds` (300 by default, 0 to read it every time); writes through PennantPlus forget it at once.

Guests are answered 401 and signed-in users the ability refuses 403.

`GET history` serves PennantPlus's audit entries, newest first, through refactor-circus/keystone (route name `pennantplus.history.index`). It answers 404 until `refactor-circus/keen` is installed, or while `pennantplus.audit.enabled` is false.

## OpenAPI and TypeScript client

`openapi.json`, at the package root, is the OpenAPI 3.1 document of the JSON API above (`info.version` `2.0.0`, paths relative to the `/pennantplus` prefix). It is generated with [Scramble](https://scramble.dedoc.co), a development dependency only, so your app installs nothing extra.

`client/` holds `@refactor-circus/pennantplus-client`, a typed fetch client built on [openapi-fetch](https://openapi-ts.dev/openapi-fetch/) whose types are generated from that document. It is not published to a registry: install it as a git dependency on the v2 tag, and its `prepare` script builds it on install. pnpm installs the `client/` folder directly:

```bash
pnpm add "github:Refactor-Circus/PennantPlus#v2.0.0&path:/client"
```

```ts
import { createPennantPlusClient } from '@refactor-circus/pennantplus-client';

const pennant = createPennantPlusClient({ baseUrl: '/pennantplus', credentials: 'include' });

const { data: features } = await pennant.GET('/features');

const { data: values, error } = await pennant.GET('/values', {
    params: { query: { feature: 'new-checkout', per_page: 50 } },
});
```

`createPennantPlusClient()` takes any openapi-fetch option (`baseUrl`, `headers`, `credentials`, a custom `fetch`) and returns an openapi-fetch client typed by the document. The `paths`, `components` and `operations` types are exported too.

`openapi.json`, `client/openapi.json` and `client/src/schema.d.ts` are generated, never edited by hand. After changing the API, regenerate all three from the package root:

```bash
npm --prefix client ci
composer client:generate
```

The test suite fails while the committed document differs from a fresh export, or the client's copy differs from it, and CI fails on a stale client.

## MCP server

`RefactorCircus\PennantPlus\Mcp\PennantPlusServer` exposes the same operations as ten tools behind ToolSearch: `list-features-tool`, `show-feature-tool`, `check-feature-tool`, `purge-feature-tool`, `list-feature-values-tool`, `set-feature-value-tool` (value JSON-encoded), `forget-feature-value-tool`, `list-scope-types-tool`, `search-scope-models-tool`, and `list-pennantplus-history-tool` (the audit history, offered only while refactor-circus/keen is installed and `pennantplus.audit.enabled` is on). Every tool that answers with structured content declares its output schema; tools answering with one record or a list put it under `data`, as the HTTP API does. Enable its transports under `pennantplus.mcp`, or register it yourself behind your own auth.

## Events

Every write fires an action event pair from `RefactorCircus\PennantPlus\Domains\Feature\Events`: `FeatureValueUpdating`/`FeatureValueUpdated`, `FeatureValueDeleting`/`FeatureValueDeleted`, and `FeaturePurging`/`FeaturePurged` (each suffixed `ActionEvent`). Reads fire a pair too - `FeaturesListing`/`FeaturesListed`, `FeatureShowing`/`FeatureShown`, `FeatureChecking`/`FeatureChecked`, `FeatureValuesListing`/`FeatureValuesListed`, and in `Domains\Scope\Events` `ScopeTypesListing`/`ScopeTypesListed` and `ScopeModelsSearching`/`ScopeModelsSearched` - which the audit log does not record. Every read pair implements Keystone's `ReadActionEvent` marker, which is how the audit log knows they only read. Starting events carry the action's input Data (`FeatureValueUpdating` and `FeatureValueDeleting` also the scope as Pennant serializes it). Each write runs in one database transaction on the Pennant store's connection; finished events fire only once it commits, and a failed write fires none.

With refactor-circus/keen installed, Keen records the write events. `FeatureValueUpdatedActionEvent` implements refactor-circus/keystone's `Auditable`: its entry has no subject and records `feature`, `scope` and `value` as context, while the forget and purge events' `feature` and `scope` properties are recorded as they are.

## Cortex

When [`refactor-circus/cortex`](https://github.com/Refactor-Circus/cortex) is installed, PennantPlus connects its MCP server to it. Nothing needs registering in your app.

- **Agents can manage flags.** Every PennantPlus MCP tool joins Cortex's tool registry under its own name (`list-features-tool`, `check-feature-tool`, `set-feature-value-tool`, ...), so an agent can inspect and change feature flags. They are tagged with the server name (`pennantplus`), so they filter together in the Cortex dashboard.
- **Agent changes are marked.** Changes an agent makes through one of the tools record `cortex` as their surface, so an audit log tells agents and people apart.
- **Instructions and descriptions can change without a deploy.** The server is registered with Cortex as `pennantplus`, so its instructions get Cortex's versioned, publishable overrides, and so does each tool's description. Published overrides are served both to MCP clients and to agents.

```php
// config/pennantplus.php
'cortex' => [
    'enabled' => true,          // false leaves Cortex alone
    'server' => 'pennantplus',  // the server's name in Cortex
    'tools' => null,            // null for every tool, or a list of names
    // 'tags' => ['pennantplus'], // the Cortex tags, default the server name
],
```

About how it works:

- **Optional:** Cortex is not a dependency. Without it nothing Cortex-related loads.
- **Lazy:** registration happens the first time Cortex's registries are used.
- **Your config wins:** a name already registered with Cortex, for example in your own `config/cortex.php`, is left alone.
- **Tool list from the server:** the tools come from `PennantPlusServer::TOOLS`, which is also the catalog the server serves, so a tool added to PennantPlus reaches Cortex without any change in your app.

## Shared runtime

PennantPlus stands on [`refactor-circus/keystone`](https://github.com/Refactor-Circus/Keystone), the shared runtime of the Refactor Circus package suite, which Composer installs with it. Its action events implement `RefactorCircus\Keystone\Contracts\ActionStartingEvent` and `ActionFinishedEvent`, so one listener can follow every package in the suite.

The Actions take their input as Data objects (`RefactorCircus\Keystone\Data\Data`) that carry their own validation rules. Build one with `new` for values your code vouches for, or with `from()` to validate untrusted input:

```php
use Laravel\Pennant\Feature;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\UpdateFeatureValueAction;
use RefactorCircus\PennantPlus\Domains\Feature\Data\UpdateFeatureValueData;

app(UpdateFeatureValueAction::class)->execute(
    new UpdateFeatureValueData('new-checkout', true, scope: Feature::serializeScope($user)),
);

// Validated, picking the scope by type and key as the API does:
app(UpdateFeatureValueAction::class)->execute(UpdateFeatureValueData::from([
    'feature' => 'new-checkout',
    'scope_type' => 'global',
    'value' => false,
]));
```

## Atrium module

When [Atrium v2](https://github.com/Refactor-Circus/Atrium) is installed, it shows the feature flags screen at `/pennantplus/features`. PennantPlus ships that screen as an Atrium module: JavaScript and CSS only, prebuilt and committed in `dist/atrium/` (`atrium.js`, its chunks, `atrium.css` and `build.json`) and declared under `extra.atrium` in `composer.json`. Atrium v2 discovers it from Composer's installed packages (`php artisan atrium:discover`, which `package:discover` runs) and loads it while the `pennantplus` integration is active. No PHP code in PennantPlus refers to Atrium, and Composer does not require it; without Atrium the files are inert.

The module's sources (Vue, TypeScript and Vitest tests) live in `atrium/`, which is left out of the dist archive, and use the generated client's types (`client/src/schema.d.ts`). After changing them, rebuild and commit `dist/atrium/`:

```bash
cd atrium
pnpm install
pnpm typecheck && pnpm test && pnpm build
```

The PHP suite fails when `dist/atrium/build.json` is older than the sources, and the CI `ui` job fails when a rebuild changes `dist/atrium/`.

## Testing

```bash
composer test
```

It runs PHPStan, Pint, 100% type coverage, the Pest suite (which includes the `openapi.json` and client staleness checks) and a suite with no UI package installed. After changing the API, run `composer client:generate` (see above) and commit the regenerated files.

Local development against sibling Refactor Circus checkouts uses a gitignored `composer.local.json` with path repositories: `composer local:install`, or `composer local:update`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.
