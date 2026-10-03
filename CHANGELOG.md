# Release Notes

## [Unreleased](https://github.com/jayjfletcher/PennantPlus/compare/v0.1.0...main)

### Fixed

- The Atrium screens' utilities that Atrium's stylesheet lacks are generated into `resources/css/atrium.css` and added through Atrium's style hook, so they take effect (the Feature flags page's inline styles move there too).

### Added

- `pennantplus` Pennant driver: stores a scope's value only when it differs from the global value.
- `OnLayeredFeature` and `OffLayeredFeature` base classes (on `LayeredFeature`): the global scope resolves to the class's default, every other scope follows the global value.
- `FeatureGate` and the `EnsureFeatureActive` middleware: global-only features, global-and-user checks for the rest, and a bypass callback.
- A management API (`pennantplus.routes`) and an MCP server (`PennantPlusServer`, nine tools) to list, show, check, set, forget and purge feature values and find scopes, sharing actions and an optional `pennantplus.ability`.
- The Atrium **Feature flags** page, moved from `jayi/atrium` (`JayI\PennantPlus\Atrium`). Setting a global value purges every other scope's value of that feature.
- Atrium's feature checks (`NavItem::feature()`, plugin `features()`, the `atrium.feature` middleware) are answered by the feature gate when Atrium is installed. Turn it off with `pennantplus.atrium.resolve_features`.
- The Atrium page's feature field and feature filter are searchable comboboxes over the discovered features.
- Cortex integration: when `jayi/cortex` is installed, the MCP server registers with it as `pennantplus` and every tool joins its tool registry (tagged `pennantplus`), so agents can use them. Published instruction and tool description overrides are served to MCP clients and agents. Configured under `pennantplus.cortex`; Cortex stays optional.

### Changed

- The Atrium **Feature flags** page follows Atrium's screen conventions: every action is an icon button with its label as tooltip, a value's active state is a status dot (`data-status="active|inactive"`), and the navigation item uses the Heroicons `flag` icon. Requires `jayi/atrium` with the icon-button and status-dot components.
- The set-value form, each row's toggle and forget buttons, and the purge button are shown only to viewers `pennantplus.ability` allows, through one check (`JayI\PennantPlus\Atrium\FeatureFlagAccess`, `@pennantplusManages` in views) that the controller and form requests also refuse with. PennantPlus does not gate its own page behind a Pennant feature, so admins cannot lock themselves out of it.
