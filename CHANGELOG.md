# Release Notes

## [Unreleased](https://github.com/jayjfletcher/PennantPlus/compare/v0.1.0...main)

### Added

- `pennantplus` Pennant driver: stores a scope's value only when it differs from the global value.
- `OnLayeredFeature` and `OffLayeredFeature` base classes (on `LayeredFeature`): the global scope resolves to the class's default, every other scope follows the global value.
- `FeatureGate` and the `EnsureFeatureActive` middleware: global-only features, global-and-user checks for the rest, and a bypass callback.
- A management API (`pennantplus.routes`) and an MCP server (`PennantPlusServer`, nine tools) to list, show, check, set, forget and purge feature values and find scopes, sharing actions and an optional `pennantplus.ability`.
- The Atrium **Feature flags** page, moved from `jayi/atrium` (`JayI\PennantPlus\Atrium`). Setting a global value purges every other scope's value of that feature.
- The Atrium page's feature field and feature filter are searchable comboboxes over the discovered features.
- Cortex integration: when `jayi/cortex` is installed, the MCP server registers with it as `pennantplus` and every tool joins its tool registry (tagged `pennantplus`), so agents can use them. Published instruction and tool description overrides are served to MCP clients and agents. Configured under `pennantplus.cortex`; Cortex stays optional.
