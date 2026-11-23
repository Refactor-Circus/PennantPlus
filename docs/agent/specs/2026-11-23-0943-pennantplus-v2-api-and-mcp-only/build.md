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

## Group 3

TypeScript client (AC 15-18).

### Files

- `client/package.json` (new): `@refactor-circus/pennantplus-client` `2.0.0`, `private`, `type: module`; `main`, `types` and `exports["."]` (`types`, `import`) under `./dist/`; `files` is `dist` and `src`; scripts `generate`, `build`, `prepare` as in AC 15. Dependencies: `openapi-fetch ^0.17.0`; devDependencies: `openapi-typescript ^7.13.0` and `typescript ^5.9.3`. TypeScript is held at 5.x because `openapi-typescript` 7.13 declares the peer `typescript ^5.x`, and the latest TypeScript (7.0.2) would fail `npm ci` on the peer conflict.
- `client/package-lock.json` (new): from `npm --prefix client install`.
- `client/tsconfig.json` (new): `strict`, `declaration`, `outDir: dist`, `rootDir: src`, ES2022 with `moduleResolution: Bundler`.
- `client/src/index.ts` (new): `createPennantPlusClient(options: ClientOptions = {}): Client<paths>` wrapping `createClient<paths>()`, and `export type { paths, components, operations }`.
- `client/openapi.json`, `client/src/schema.d.ts` (new): written by `composer client:generate`. The export left the root `openapi.json` unchanged.
- `client/README.md` (new): install (pnpm `#v2.0.0&path:/client`), a `createPennantPlusClient` example, and how to regenerate.
- `.gitignore`: `/client/node_modules` and `/client/dist`.
- `composer.json`: the `client:generate` script, exactly as in the spec's design.
- `.github/workflows/tests.yml`: new `client` job (ubuntu-latest, PHP 8.5, `actions/setup-node` v7.1.0 pinned by SHA with `node-version: lts/*` and the npm cache on `client/package-lock.json`). It runs `composer update`, `composer run prepare`, then `npm --prefix client ci`, `composer client:generate`, `git diff --exit-code -- openapi.json client` and `npm --prefix client run build`.

### Tests

- `tests/Feature/ClientTest.php` (new):
  - `client/openapi.json` is byte-identical to `openapi.json`.
  - When `client/node_modules/.bin/openapi-typescript` (`.cmd` on Windows) exists, it regenerates the schema from inside `client/` into a temporary file and compares it with `client/src/schema.d.ts`. Otherwise it skips with a message naming `npm --prefix client ci`.
  - `package.json` declares the AC 15 name, version, private flag, type, dependency, devDependencies and scripts; `main`, `types` and every `exports` target are under `./dist/`.
  - `tsconfig.json` is strict, emits declarations and builds into `dist`; the lockfile is committed and `/client/node_modules` and `/client/dist` are gitignored; `src/index.ts` wraps openapi-fetch and re-exports the three types.
- `tests/Feature/ComposerTest.php`: `client:generate` is exactly the three steps in order. The `client` job runs on ubuntu-latest with PHP 8.5 and Node `lts/*`, and has the four AC 18 commands in order. The workflow job lookup is now `pennantPlusWorkflowJob($name)`, which `pennantPlusWithoutAtriumJob()` reuses.
- Targeted run only, not the full suite (the gate runs `vendor/bin/pest`): `pest tests/Feature/ClientTest.php tests/Feature/ComposerTest.php` passed 21 tests with 62 assertions, and the schema check was not skipped. `npm --prefix client run build` passes. Pint `--test` passes on both test files, and `composer validate` passes.
- A scratch consumer (strict, `module`/`moduleResolution: nodenext`, without `skipLibCheck`) imported `dist/index.js`. Unknown paths were rejected, which confirms that the emitted declarations carry the schema types.

### Deviations

- None from the standards.
- Spec design snippet: `src/index.ts` imports the schema as `'../src/schema.js'`, not `'./schema'`. tsc never copies a `.d.ts` input into `outDir`. With `'./schema'`, the emitted `dist/index.d.ts` pointed at a `dist/schema.d.ts` that does not exist (checked by building), so consumers would get untyped or broken declarations. The changed specifier resolves to the committed `src/schema.d.ts` from both `src/` and `dist/`. The `.js` extension also lets it resolve under NodeNext. The exported API is the one in the spec.
- `ClientTest` sits in `tests/Feature` as the task lists, although it uses no framework boot (test-layers puts boot-free tests in `Unit`). `ComposerTest`, which is also boot-free, already sits there.
- Retry check: on the retry, all of 3.1-3.5 were already committed. Targeted runs passed: `pest tests/Feature/ClientTest.php tests/Feature/ComposerTest.php` (21 tests, 62 assertions, the schema check not skipped) and `pest tests/OpenApi` (5 tests). `npm --prefix client run build` passed, and so did Pint `--test` on both test files. No code changed. PHPStan does not analyse `tests/` (`phpstan.neon.dist` covers only `src` and `config`). Run directly on the two test files, it reports Pest-typing noise, for example `markTestSkipped()` on `TestCall`. That noise does not affect `composer analyse`.

## Group 4

Docs and full gate (AC 19-23).

### Files

- `README.md`:
  - The intro describes layered flags with a JSON API and an MCP server, the OpenAPI document and the client, and says that Atrium v2 provides the optional UI.
  - The "Atrium dashboard", "Screen extensions" and "Atrium's theme switcher" sections are removed. Their package-level facts move to the sections that still own them: the global-update purge and the scope-type cache go to "Management API", and the event and `Auditable` paragraphs go to a new "Events" section.
  - The API section no longer names the page.
  - The indexes note says "the newest-first listing of stored values (`GET values`)" instead of the dashboard listing.
  - "Testing" lists what `composer test` runs and points to `composer local:install` instead of an Atrium path repository.
  - New "OpenAPI and TypeScript client" section: `openapi.json`, the pnpm git install on `#v2.0.0&path:/client`, a `createPennantPlusClient` example that type-checks against the committed schema, `npm --prefix client ci` with `composer client:generate`, and the staleness checks.
- `CHANGELOG.md`: a new `v2.0.0` section, with `Unreleased` now comparing `v2.0.0...main`. It has three parts:
  - **Breaking**: the page and plugin, with `extra.atrium.plugins` and `atrium.pennant.*`; the feature resolver and `pennantplus.atrium.resolve_features`; `@pennantplusManages`; `FeatureFlagAccess`; `FeatureValueRow` and the screen registration; `resources/views`, the view namespace and `pennantplus-views`; the pruned keys in `lang/en/pennantplus.php`.
  - **Upgrade**: Atrium v2 for the UI, and Atrium's own Pennant integration for its feature flags.
  - **Added**: `openapi.json`, the client and `composer client:generate`.
- `resources/boost/skills/pennantplus-development/SKILL.md`:
  - Section 7 (the page) and the Atrium-resolver paragraph are removed.
  - The front matter, intro, section 4, the references and the "do not" list no longer mention a page or dashboard.
  - The "do not gate behind a Pennant feature" advice now applies to the management surface.
  - New section 6, "Call the API from TypeScript", covers the client, its install, `createPennantPlusClient` and the regeneration rule. Sections 7-9 are renumbered.
  - The audit-context note moves to section 9, and a "do not edit generated files" anti-pattern is added.
- `AGENTS.md`. `CLAUDE.md` is a symlink to it in this repo, so both always carry identical guidance.
  - The `src/Atrium` rule is replaced by the no-Atrium rule, with the checking tests and the D-006 VCS-repository note.
  - The `src/Atrium` layout bullet is gone. The stray `config/pennantplus.php` bullet moves back into Layout, next to a no-UI bullet.
  - The Frontend section (styles, `FeatureFlagAccess`, the screen registry, `ResetsScreenExtensions`) is replaced by "Generated artifacts". That section covers what `openapi.json` and `client/` are, `composer client:generate`, where the Scramble config lives, and the staleness tests and CI job. It keeps the `test:without-atrium` paragraph.
  - The Keystone bullets no longer mention Atrium requests, the Atrium controller or the `FeatureValueRow` arch exclusion.

### Tests

- `tests/Unit/DocumentationTest.php` (rewritten; the audit-trail scan stays). It checks the following:
  - The README intro names Pennant, the JSON API and the MCP server, and not a page or dashboard.
  - None of the three removed headings remain.
  - The "OpenAPI and TypeScript client" section names `openapi.json`, the package name, `#v2.0.0`, `createPennantPlusClient(` and `composer client:generate`.
  - The README names Atrium v2, and every README line that names Atrium names Atrium v2.
  - Neither the README nor the skill mentions `@pennantplusManages`, `FeatureValueRow`, `FeatureFlagAccess` or `resolve_features`.
  - The skill describes the API, MCP and the client, and has no "page"/"pages" word (the `page=` query parameter is allowed), no dashboard, no `atrium.pennant` and no `Atrium::screens()`.
  - The CHANGELOG `v2.0.0` section has Breaking with every AC 20 removal, Upgrade with Atrium v2 and the Pennant integration, and Added with `openapi.json` and the client.
  - CLAUDE.md and AGENTS.md have no `src/Atrium`, `ResetsScreenExtensions`, screen registry, `Atrium::css()`, `x-atrium`, `FeatureValueRow`, `FeatureFlagAccess` or Frontend section. Both state the no-Atrium rule and name `openapi.json`, `client/` and `composer client:generate`, and their shared guidance (before Ring Master) is identical.
- Targeted run only (the gate runs `vendor/bin/pest` and `composer test`): `pest tests/Unit/DocumentationTest.php` passed 14 tests with 71 assertions. Pint `--test` passes on the test file. Task 4.5 is ticked because the gate covers it.

### Deviations

- None from the standards.
- `CLAUDE.md` is a symlink to `AGENTS.md`, so the edits were made in `AGENTS.md` only. The identical-guidance test still reads both paths.
