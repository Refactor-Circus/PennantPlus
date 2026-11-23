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

## Group 2

OpenAPI export (AC 12-14).

### Files

- `composer.json`: `dedoc/scramble` `^0.13.55` in `require-dev`. v0.13.55 is the latest release and requires `illuminate/contracts ^10.0|^11.0|^12.0|^13.0`, so it supports Laravel 13 and no escalation was needed.
- `testbench.yaml`: `Dedoc\Scramble\ScrambleServiceProvider` added to `providers`.
- `workbench/app/Providers/WorkbenchServiceProvider.php`: `register()` sets `scramble.api_path` to `pennantplus`, `scramble.ui.title` to `PennantPlus` (Scramble takes `info.title` from `ui.title`, falling back to `app.name`), `scramble.info.version` to `2.0.0`, and `scramble.servers` to a fixed `['PennantPlus API' => 'http://localhost/pennantplus']`. The URL is absolute so that `url()` does not make it depend on `APP_URL`. The docblock now mentions Scramble.
- `openapi.json` (new): exported with `vendor/bin/testbench scramble:export --path=openapi.json`. OpenAPI 3.1.2, ten operations (one per AC 9 route), with paths relative to the `/pennantplus` server. Three exports were byte-identical (`cmp`). Scramble warns once (JR001, it cannot infer `FeatureResource`'s model). Some fields are loosely typed (`string`), and path parameters also appear as query parameters, because the requests' `rules()` validate them. Both are accepted by the spec's "Schema accuracy" risk as a follow-up.
- `tests/OpenApiTestCase.php` (new): an Orchestra test case with the providers `testbench.yaml` loads for the export: Pennant, MCP, PennantPlus, Scramble and `WorkbenchServiceProvider`.
- `tests/Pest.php`: `uses(OpenApiTestCase::class)->in('OpenApi')`.
- `phpunit.xml.dist` (outside the group's listed files): new `OpenApi` testsuite for `tests/OpenApi`. Without it, `vendor/bin/pest` never runs the staleness test, because the config lists only the Arch, Unit and Feature suites.

### Tests

- `tests/OpenApi/OpenApiTest.php` (new) checks five things:
  - `Artisan::call('scramble:export', ['--path' => $tmp])` succeeds, and the decoded export equals the decoded committed `openapi.json` (AC 14).
  - `openapi` is `3.x`, `info.title` is `PennantPlus` and `info.version` is `2.0.0`.
  - Every server URL ends in `/pennantplus`.
  - The operations are exactly the ten AC 9 routes, by method and path.
  - The document has no machine-specific values: no package root path, no `base64:` key, no `file://`, no `/Users`, `/home`, `/private`, `/tmp` or `/var/folders` path, and no Windows drive path.
- `tests/Feature/ComposerTest.php`: `dedoc/scramble` is in `require-dev` and not in `require`, and no file under `src` or `config` contains `Dedoc\`.
- Targeted run only, not the full suite (the gate runs `vendor/bin/pest`): `pest tests/OpenApi tests/Feature/ComposerTest.php` passed (17 tests, 38 assertions). Pint `--test` passes on every PHP file touched.

### Deviations

- None from the standards.
- One file outside this group's list was touched: `phpunit.xml.dist` (see Files).
- Not touched, outside this group's files: `phpunit.xml.dist` has no `Cortex` testsuite either, so `tests/Cortex/CortexTest.php` does not run under `vendor/bin/pest`. This gap predates this job.
- Retry check: on the retry, the work above was already committed and complete. Two fresh `scramble:export` runs were byte-identical to each other and to the committed `openapi.json` (`cmp`). The targeted `pest tests/OpenApi tests/Feature/ComposerTest.php` run passed (17 tests), and so did `phpstan analyse` (0 errors). No code changed.
- Local install: `composer.local.json` (gitignored) was regenerated with `bin/composer-local.php`, with keystone and cortex set to `dev-main` as in the main checkout, to install Scramble. Because Cortex dev-main no longer requires Atrium, the update also removed `refactor-circus/atrium` from this worktree's `vendor/`.
