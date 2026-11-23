# Build

## Group 1

Remove the Atrium integration (AC 1-11).

### Files

- Deleted: `src/Atrium/**` (`PennantPlugin`, `FeatureFlagAccess`, `Data/FeatureValueRow`, `Http/Controllers/FeatureFlagController`, `Http/Requests/*`, `Http/Requests/Concerns/AuthorizesFeatureFlags`), `resources/views/atrium/index.blade.php` (and with it `resources/views`).
- Deleted tests: `tests/Feature/AtriumFeatureResolverTest.php`, `AtriumPluginTest.php`, `AtriumScreenControlsTest.php`, `AtriumThemeSwitcherTest.php`, `NavigationSectionTest.php`, `tests/Feature/Ui/*`, `tests/Unit/FeatureValueRowTest.php`.
- `src/PennantPlusServiceProvider.php`: removed `registerAtriumFeatureResolver()`, `registerAtriumBladeConditional()`, the Atrium, `Blade`, `Repository`, `Request` and `FeatureGate` imports, `loadViewsFrom()` and the `pennantplus-views` publish.
- `lang/en/pennantplus.php`: pruned to `global`, `other`, `scope_missing`, `feature_disabled`.
- `config/pennantplus.php`: removed the `atrium` section; the `store`, `ability`, `scope_types_cache_seconds` and `audit` banners name only the API endpoints and MCP tools.
- `composer.json`: `refactor-circus/atrium` gone from `require-dev`, `suggest` and `conflict` (the empty `conflict` block is removed); `extra.atrium` removed; description reworded. The Atrium VCS repository stays (D-006).
- `.github/workflows/tests.yml`: the `without-atrium` job removes `refactor-circus/cortex` only (step renamed "Uninstall Cortex and Atrium").
- `testbench.yaml`: Atrium provider and `atrium-assets` removed; `workbench.start` is `/pennantplus/features`.
- `workbench/app/Providers/WorkbenchServiceProvider.php`: `atrium.plugins`, the `PennantPlugin` import, `viewAtrium` (and the then-unused `Gate` import) removed; docblock describes the JSON API and MCP demo.
- `tests/TestCase.php`: no `AtriumServiceProvider`, `ResetsScreenExtensions` or `atrium.*` config.
- `tests/CortexTestCase.php`: sets `cortex.ui` to `['enabled' => false]`, so Cortex skips its Atrium plugin and screen registrations (both guarded by that switch) and boots without Atrium's provider.
- `tests/WithoutAtrium/WithoutAtriumTest.php`: no `FeatureFlagAccess`, `PennantPlugin` or Blade directive; keeps the autoloader, provider boot, route, JSON API, MCP and no-Atrium-class checks.
- `tests/Unit/DocumentationTest.php`: the two Atrium-screen cases and the `pennantPlusMinimumAtrium()`/`pennantPlusDoc()` helpers removed; the audit-trail scan stays.
- `tests/Feature/PerformanceTest.php`: the Atrium-page test removed.

### Tests

- `tests/ArchTest.php`: new `names no atrium` rule (`RefactorCircus\PennantPlus` does not use `RefactorCircus\Atrium`); the `FeatureValueRow` exclusion, its stand-in rule and the `src/Atrium/Http/Requests` glob are gone.
- `tests/Feature/NoAtriumTest.php` (new): case-insensitive `atrium` scan of `src`, `config`, `lang`, `database`; no `src/Atrium`; no `pennantplusManages` directive (`if`, `else`, `end`, `unless`); no `atrium.pennant.*` route; no `atrium` config key; every lang key is referenced from `src` and the keys are exactly the four kept.
- `tests/Feature/PublishTest.php` (new): `pennantplus` publishes exactly the config file and the lang directory; `pennantplus-config` and `pennantplus-lang` publish one each; no `pennantplus-views` group, no `resources/views`, no `pennantplus` view namespace.
- `tests/Feature/Http/RouteListTest.php` (new): the `pennantplus.*` routes are exactly the ten AC 9 names with their verbs and URIs.
- `tests/Feature/ComposerTest.php`: no `refactor-circus/atrium` in `require`, `require-dev`, `suggest` or `conflict`; no `extra.atrium`; no Atrium in the description; the `without-atrium` job removes `refactor-circus/cortex`, never `refactor-circus/atrium`, only packages `require-dev` lists, and every dev package that requires Atrium.
- Not run here (the gate runs `vendor/bin/pest` and `composer test:without-atrium`). Pint `--test` passes on `src`, `config`, `lang`, `tests` and `workbench`; `composer validate` passes.

### Deviations

- None from the standards.
- Not touched, outside this group's files: the docblock of `tests/WithoutAtrium/bootstrap.php` still says `tests/TestCase.php` names Atrium, which is no longer true. The suite still works; the wording can be fixed in a later round.
