<p align="center">
    <img src="art/icon.png" width="160" alt="PennantPlus icon">
</p>

# PennantPlus

Layered [Laravel Pennant](https://laravel.com/docs/pennant) feature flags: one global value per feature with per-user overrides, a gate for entry surfaces (routes, MCP tools), and an optional [Atrium](https://github.com/Refactor-Circus/Atrium) page for managing stored values.

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

Pennant's `features` table only has a unique index on `(name, scope)`, which cannot serve the driver's per-scope read (`where scope = ?`) or the dashboard's newest-first listing. PennantPlus owns no tables, so it ships the indexes as an optional migration that is never run unless you publish it:

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

Enabled by default under `pennantplus.routes` (prefix `pennantplus`, `api` middleware — add auth before exposing it). Every endpoint, MCP tool and the Atrium page also checks the optional `pennantplus.ability` Gate ability.

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

Guests are answered 401 and signed-in users the ability refuses 403.

`GET history` serves PennantPlus's audit entries, newest first, through refactor-circus/keystone (route name `pennantplus.history.index`). It answers 404 until `refactor-circus/keen` is installed, or while `pennantplus.audit.enabled` is false.

## MCP server

`RefactorCircus\PennantPlus\Mcp\PennantPlusServer` exposes the same operations as ten tools behind ToolSearch: `list-features-tool`, `show-feature-tool`, `check-feature-tool`, `purge-feature-tool`, `list-feature-values-tool`, `set-feature-value-tool` (value JSON-encoded), `forget-feature-value-tool`, `list-scope-types-tool`, `search-scope-models-tool`, and `list-pennantplus-history-tool` (the audit history, offered only while refactor-circus/keen is installed and `pennantplus.audit.enabled` is on). Every tool that answers with structured content declares its output schema; tools answering with one record or a list put it under `data`, as the HTTP API does. Enable its transports under `pennantplus.mcp`, or register it yourself behind your own auth.

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

## Atrium dashboard

With `refactor-circus/atrium` installed, Atrium discovers a **Feature flags** page (plugin key `pennant`). It lists stored values by feature and scope, sets a value for the global scope, a configured model or any string scope, forgets a value, and purges a feature. Setting a **global** value forgets every other scope's value of that feature (`pennantplus.purge_scopes_on_global_update`); those values are deleted once the global value is committed, a thousand rows per query, so a heavily overridden feature never holds one long transaction. The scope filter's list of model types that have values is cached for `pennantplus.scope_types_cache_seconds` (300 by default, 0 to read it every time); writes through PennantPlus forget it at once. It uses the same `store`, `ability`, `features` and `scopes` settings as the API.

The page follows Atrium's screen conventions: actions are icon buttons (the label is the tooltip and accessible name), a value's state is a status dot (`success` active, `neutral` inactive; read it from `data-status`), and the navigation item uses the Heroicons `flag` icon. The navigation item, the set-value form, each row's toggle and forget buttons, and the purge button are shown only when `pennantplus.ability` allows the viewer - asked through `RefactorCircus\PennantPlus\Atrium\FeatureFlagAccess::allows()`, the same check the page's controller and form requests refuse with. Your own published views can use it as `@pennantplusManages ... @endpennantplusManages`.

The page is built only from Atrium's components and the utilities Atrium's stylesheet ships; PennantPlus has no stylesheet of its own. The feature field and filter are Atrium's `x-atrium::form.combobox` over the discovered features (free text still allowed), status and validation messages show through `x-atrium::flash` (requests flash `status`), and the scope picker searches PennantPlus's own scope endpoint.

The page shows no history of its own, and its navigation links none. With refactor-circus/keen installed, Keen adds its History section to the page through the screen extensions registry (see below); PennantPlus registers nothing for it. `FeatureValueUpdatedActionEvent` implements refactor-circus/keystone's `Auditable`: its entry has no subject and records `feature`, `scope` and `value` as context, while the forget and purge events' `feature` and `scope` properties are recorded as they are.

PennantPlus deliberately has no Pennant feature switch of its own for the Feature flags page: gating the feature-flag manager behind a feature flag could lock admins out of turning it back on. Restrict it with `pennantplus.ability`, or hide it with `atrium.disabled`.

PennantPlus also answers Atrium's feature checks - `NavItem::feature()`, a plugin's `features()`, and the `atrium.feature` middleware - through the feature gate, so `global_only` and the bypass callback apply in the dashboard exactly as they do on routes and MCP tools. To have Pennant decide only global visibility and leave per-user visibility to permissions (`NavItem::can()`), set `pennantplus.gate.global_only` to `['*']`. Set `pennantplus.atrium.resolve_features` to `false` to leave Atrium's feature resolver alone.

Every write fires an action event pair from `RefactorCircus\PennantPlus\Domains\Feature\Events`: `FeatureValueUpdating`/`FeatureValueUpdated`, `FeatureValueDeleting`/`FeatureValueDeleted`, and `FeaturePurging`/`FeaturePurged` (each suffixed `ActionEvent`). Reads fire a pair too - `FeaturesListing`/`FeaturesListed`, `FeatureShowing`/`FeatureShown`, `FeatureChecking`/`FeatureChecked`, `FeatureValuesListing`/`FeatureValuesListed`, and in `Domains\Scope\Events` `ScopeTypesListing`/`ScopeTypesListed` and `ScopeModelsSearching`/`ScopeModelsSearched` - which the audit log does not record. Every read pair implements Keystone's `ReadActionEvent` marker, which is how the audit log knows they only read. Starting events carry the action's input Data (`FeatureValueUpdating` and `FeatureValueDeleting` also the scope as Pennant serializes it). Each write runs in one database transaction on the Pennant store's connection; finished events fire only once it commits, and a failed write fires none.

### Screen extensions

The Feature flags page declares itself on Atrium's screen extensions registry as the `pennantplus.features` list screen, so other packages add tabs and sections to it without PennantPlus knowing they exist. This needs Atrium 0.2.0 or later, the first release with the registry: Composer refuses an older Atrium through PennantPlus's `conflict` entry. Atrium itself stays optional.

- **Slots.** `header.actions` (beside the page title), `before` (above the page), `after` (below it) and the row slot `row.actions`, rendered once per stored value in a trailing cell of the values table. Keen's History section lands in `after` by default.
- **Tabs.** The page has no built-in tabs, so it is the `overview` tab. A registered tab opens with `?tab={key}` and replaces the page; only `header.actions` stays. An unknown `?tab=` opens `overview`.
- **The page's own check comes first.** A viewer `pennantplus.ability` refuses gets a 403 before any extension's `authorize()` or resolver runs.
- **Permissions are Gate abilities.** Feature flags have no policies, so an extension's `permission()` is asked as `Gate::forUser($request->user())->allows($permission)`, with the row as the Gate argument in `row.actions`. A request without a user is asked as a guest, and an ability the Gate does not define is denied.
- **Rows are `FeatureValueRow`s.** A stored value is not a model, so a `row.actions` section receives a `RefactorCircus\PennantPlus\Atrium\Data\FeatureValueRow` as `$record`: an unsaved, read-only model keyed by the feature and the scope, whose `$record->value` is the `StoredFeatureValue` (`feature`, `scope`, `value`, `updatedAt`). Saving, reloading or deleting it throws.
- **The raw scope is exposed.** `$record->value->scope` is the scope exactly as Pennant stores it (`__laravel_null`, `App\Models\User|5`, or any string), which names the scope model by class and key. Do not show it to viewers who may not know that model exists.
- **Do not throw.** This page administers production flags. A section that throws renders nothing and is reported, but a registered tab whose panel throws fails the page with a 500, so keep registered closures and views from throwing.

```php
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\PennantPlus\Atrium\Data\FeatureValueRow;

// In a service provider's boot():
Atrium::screens()->section('pennantplus.features', 'row.actions', SectionDefinition::make('acme.flag-owner')
    ->view('acme::flag-owner') // reads $record->value->feature
    ->permission('acme.flag-owner'));

Gate::define('acme.flag-owner', fn (User $user, FeatureValueRow $row): bool => $user->owns($row->value->feature));
```

### Atrium's theme switcher

Atrium shows its theme switcher behind its own Pennant feature, `RefactorCircus\Atrium\Domains\Themes\Features\ThemeSwitcherFeature` (`atrium.themes.switcher_feature`). With PennantPlus installed, Atrium's feature checks go through the feature gate, so the feature is on until you set its global value and is checked per user too: you can hide the switcher for everyone or only for some people. With the `pennantplus` driver, users who follow the global value store nothing. It appears on the feature flags screen once the dashboard has first asked for it. While it is off, everyone sees Atrium's default theme.

## Testing

```bash
composer test
```

Local development against a sibling Atrium checkout uses a gitignored `composer.local.json` with a path repository to `../Atrium`: `COMPOSER=composer.local.json composer update`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.
