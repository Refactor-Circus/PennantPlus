# PennantPlus

This repository is a Laravel package. Keep the package focused, idiomatic, and easy for Laravel developers to install, test, and maintain.

## Package Conventions

- Use Laravel-native package APIs and the existing service provider shape before adding abstractions.
- Keep package names, namespaces, Composer metadata, publish tags, documentation, and examples aligned with `refactor-circus/pennantplus` and the `RefactorCircus\PennantPlus` namespace. Code under `src/Atrium` extends Atrium classes and must only load when refactor-circus/atrium is installed; nothing outside it may reference Atrium. Cortex (refactor-circus/cortex) is wired only through refactor-circus/foundation's `CortexIntegration`, which references Cortex classes behind its `active()` check; nothing in this package references Cortex directly.
- Add only the files and dependencies needed for the package behavior being implemented.
- Prefer explicit Laravel package code over helper abstractions unless the extension point is real.
- Keep tests focused on observable package behavior through public APIs, service provider wiring, commands, routes, published resources, and documentation promises.

## Layout

The package follows the mono domain-module layout. Code lives in `src/Domains/{Domain}/` (namespace `RefactorCircus\PennantPlus\Domains\{Domain}`), each with its own `{Domain}ServiceProvider` registered by `Domains\DomainServiceProvider`, which `PennantPlusServiceProvider` registers. Domains create only the subdirectories they use.

- `Domains/Feature`: the layered feature base classes and the `pennantplus` driver (`Support/`), `FeatureGate` and `FeatureFlagManager` (`Services/`), `StoredFeatureValue` (`Data/`), and the feature and stored-value actions, events, HTTP API, middleware and MCP tools.
- `Domains/Scope`: `ScopeModel` (`Data/`), the scope validation rules and MCP argument schema, and the scope-type actions, HTTP API and MCP tools.
- Package-wide pieces stay at the top level: `PennantPlusServiceProvider`, the base `Http\Request` and `Mcp\Request` (each extends refactor-circus/foundation's and adds only the `pennantplus.ability` check), the MCP server and `Mcp\Tools\ListPennantPlusHistoryTool`.
- The Atrium integration spans both domains, so it keeps its own top-level directory: `src/Atrium` (the Feature flags screen).

## Frontend

Atrium owns every component and style of the suite. PennantPlus ships no stylesheet, no `Atrium::css()` call and no component namespace: `resources/views` uses only `x-atrium::*` components and the utilities Atrium's compiled stylesheet ships, with no `<style>` block or `style` attribute. `tests/Feature/Ui/StylesTest.php` checks this with `RefactorCircus\Atrium\Testing\AtriumStyles`. If a screen needs something Atrium lacks, add it to Atrium, not here. Requests flash `status` for `x-atrium::flash`. The scope picker's inline `atriumPennantScope` Alpine script stays here because it searches PennantPlus's own endpoint.

Atrium's shared screen helpers (`ScreenAccess`, `AuthorizesScreens`, `Plugin::featuresFromConfig()`) ask a package's model policies; feature flags have no models or policies, so the screen keeps `FeatureFlagAccess`, which asks the plugin's `pennantplus.ability` check.
- `config/pennantplus.php` stays one file; domains read from it.

## Foundation

PennantPlus stands on `refactor-circus/foundation`, the suite's shared runtime. Use its classes rather than adding package copies:

- `PennantPlusServiceProvider` extends `RefactorCircus\Foundation\Support\PackageServiceProvider`: `definition()` describes the package (`pennantplus`, its MCP server), `registerPackage()` runs in `register()` before the domain providers, and `boot()` uses `registerMcpServer()`, `registerCortex()` and `loadHistoryRoutes()`.
- Domain providers extend `RefactorCircus\Foundation\Support\ServiceProvider` and load their `routes.php` through `loadApiRoutesFrom()`, inside the shared `pennantplus.routes` group with the `pennantplus.` name prefix.
- Action events implement `RefactorCircus\Foundation\Contracts\ActionStartingEvent` / `ActionFinishedEvent`; MCP tools extend `RefactorCircus\Foundation\Mcp\Tool`; the server extends `RefactorCircus\Foundation\Mcp\Server`.
- Feature flags have no models, so there are no policies and no `authorization` default: every endpoint and tool checks the optional `pennantplus.ability` Gate ability through `FeatureFlagManager::allowsManagement()`.

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
