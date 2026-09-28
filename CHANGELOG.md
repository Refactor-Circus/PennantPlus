# Release Notes

## [Unreleased](https://github.com/jayjfletcher/PennantPlus/compare/v0.1.0...main)

### Added

- `pennantplus` Pennant driver: stores a scope's value only when it differs from the global value.
- `FeatureGate` and the `EnsureFeatureActive` middleware: global-only features, global-and-user checks for the rest, and a bypass callback.
- A management API (`pennantplus.routes`) and an MCP server (`PennantPlusServer`, nine tools) to list, show, check, set, forget and purge feature values and find scopes, sharing actions and an optional `pennantplus.ability`.
- The Atrium **Feature flags** page, moved from `jayi/atrium` (`JayI\PennantPlus\Atrium`). Setting a global value purges every other scope's value of that feature.
