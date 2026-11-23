VERDICT: PASS

Compat review, round 1. No findings at or above minor.

The breaks in this diff are all removals the spec orders, and CHANGELOG v2.0.0 lists each one under Breaking: the Atrium plugin and routes, the feature resolver and its config key, the Blade directive, FeatureFlagAccess, FeatureValueRow, the views and the pennantplus-views tag, and the pruned lang keys.

The JSON API, MCP tools, events, driver and gate are untouched. The config keeps its defaults (D-002) and adds no keys. The pennantplus, pennantplus-config and pennantplus-lang publish tags are kept. dedoc/scramble is require-dev only, and the Keystone constraint is unchanged.
