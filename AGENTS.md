# PennantPlus

This repository is a Laravel package. Keep the package focused, idiomatic, and easy for Laravel developers to install, test, and maintain.

## Package Conventions

- Use Laravel-native package APIs and the existing service provider shape before adding abstractions.
- Keep package names, namespaces, Composer metadata, publish tags, documentation, and examples aligned with `jayi/pennantplus` and the `JayI\PennantPlus` namespace. Code under `src/Atrium` extends Atrium classes and must only load when jayi/atrium is installed; nothing outside it may reference Atrium. Likewise, Cortex classes (jayi/cortex) are referenced only from `src/Cortex`, behind `CortexIntegration::active()`.
- Add only the files and dependencies needed for the package behavior being implemented.
- Prefer explicit Laravel package code over helper abstractions unless the extension point is real.
- Keep tests focused on observable package behavior through public APIs, service provider wiring, commands, routes, published resources, and documentation promises.

## Layout

The package follows the mono domain-module layout. Code lives in `src/Domains/{Domain}/` (namespace `JayI\PennantPlus\Domains\{Domain}`), each with its own `{Domain}ServiceProvider` registered by `Domains\DomainServiceProvider`, which `PennantPlusServiceProvider` registers. Domains create only the subdirectories they use.

- `Domains/Feature`: the layered feature base classes and the `pennantplus` driver (`Support/`), `FeatureGate` and `FeatureFlagManager` (`Services/`), `StoredFeatureValue` (`Data/`), and the feature and stored-value actions, events, HTTP API, middleware and MCP tools.
- `Domains/Scope`: `ScopeModel` (`Data/`), the scope validation rules and MCP argument schema, and the scope-type actions, HTTP API and MCP tools.
- Package-wide pieces stay at the top level: `PennantPlusServiceProvider`, `Contracts/` (action event contracts), the base `Http\Request`, the MCP server and base `Mcp\Request`/`Mcp\Tool`, and `Support\ServiceProvider` (loads a domain's `routes.php` inside the shared API route group).
- Integrations span both domains, so they keep their own top-level directories: `src/Atrium` (the Feature flags screen) and `src/Cortex`.
- `config/pennantplus.php` stays one file; domains read from it.

## Layout

The package follows the mono domain-module layout. Code lives in `src/Domains/{Domain}/` (namespace `JayI\PennantPlus\Domains\{Domain}`), each with its own `{Domain}ServiceProvider` registered by `Domains\DomainServiceProvider`, which `PennantPlusServiceProvider` registers. Domains create only the subdirectories they use.

- `Domains/Feature`: the layered feature base classes and the `pennantplus` driver (`Support/`), `FeatureGate` and `FeatureFlagManager` (`Services/`), `StoredFeatureValue` (`Data/`), and the feature and stored-value actions, events, HTTP API, middleware and MCP tools.
- `Domains/Scope`: `ScopeModel` (`Data/`), the scope validation rules and MCP argument schema, and the scope-type actions, HTTP API and MCP tools.
- Package-wide pieces stay at the top level: `PennantPlusServiceProvider`, `Contracts/` (action event contracts), the base `Http\Request`, the MCP server and base `Mcp\Request`/`Mcp\Tool`, and `Support\ServiceProvider` (loads a domain's `routes.php` inside the shared API route group).
- Integrations span both domains, so they keep their own top-level directories: `src/Atrium` (the Feature flags screen) and `src/Cortex`.
- `config/pennantplus.php` stays one file; domains read from it.

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
