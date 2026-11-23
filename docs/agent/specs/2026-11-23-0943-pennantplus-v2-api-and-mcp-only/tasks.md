# Tasks: PennantPlus v2: API and MCP only

## Group 1: Remove the Atrium integration
depends on: none
files: src/Atrium/** (delete), src/PennantPlusServiceProvider.php, config/pennantplus.php, resources/views/** (delete), lang/en/pennantplus.php, composer.json, .github/workflows/tests.yml, testbench.yaml, workbench/app/Providers/WorkbenchServiceProvider.php, tests/TestCase.php, tests/CortexTestCase.php, tests/ArchTest.php, tests/Feature/ComposerTest.php, tests/Feature/PerformanceTest.php, tests/Feature/AtriumFeatureResolverTest.php (delete), tests/Feature/AtriumPluginTest.php (delete), tests/Feature/AtriumScreenControlsTest.php (delete), tests/Feature/AtriumThemeSwitcherTest.php (delete), tests/Feature/NavigationSectionTest.php (delete), tests/Feature/Ui/** (delete), tests/Unit/FeatureValueRowTest.php (delete), tests/Unit/DocumentationTest.php, tests/WithoutAtrium/WithoutAtriumTest.php, tests/Feature/Http/RouteListTest.php, tests/Feature/NoAtriumTest.php, tests/Feature/PublishTest.php
criteria: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11

- [x] 1.1 Write the tests first. ArchTest: add a rule that `RefactorCircus\PennantPlus` does not use `RefactorCircus\Atrium`, and drop the `src/Atrium` glob and the `FeatureValueRow` exclusion. NoAtriumTest: a case-insensitive scan for `atrium` under src/config/lang/database; no `pennantplusManages` directives; no `atrium.pennant.*` routes; no `atrium` config key; every lang key is referenced from src. PublishTest: `--tag=pennantplus` publishes only config and lang, and there is no `pennantplus-views` tag. RouteListTest: the exact `pennantplus.*` API names, verbs and URIs (AC 9). ComposerTest: no `refactor-circus/atrium` in require, require-dev, suggest or conflict; no `extra.atrium`; no Atrium in the description; the `without-atrium` job removes `refactor-circus/cortex`.
- [x] 1.2 Delete the Atrium-only tests listed in AC 7, the Atrium-page test in PerformanceTest, and the Atrium cases in DocumentationTest. In TestCase, drop `AtriumServiceProvider`, `ResetsScreenExtensions` and the `atrium.*` config. In CortexTestCase, add back only what Cortex needs to boot. In WithoutAtriumTest, remove the references to `FeatureFlagAccess`, `PennantPlugin` and the directive, and keep the API and MCP checks.
- [x] 1.3 Delete `src/Atrium/**` and `resources/views/**`. In PennantPlusServiceProvider, remove `registerAtriumFeatureResolver()`, `registerAtriumBladeConditional()`, the Atrium imports, `loadViewsFrom()` and the `pennantplus-views` publish. Prune the lang file to `global`, `other`, `scope_missing` and `feature_disabled`.
- [x] 1.4 In config/pennantplus.php, remove the `atrium` section and the dashboard and page wording from the `store`, `ability`, `scope_types_cache_seconds` and `audit` banners.
- [x] 1.5 In composer.json, drop `refactor-circus/atrium` from require-dev, suggest and conflict, drop `extra.atrium`, and reword the description. Keep the Atrium VCS repository (D-006). In tests.yml, make the `without-atrium` job remove `refactor-circus/cortex` only.
- [x] 1.6 In testbench.yaml, remove the Atrium provider and `atrium-assets`, and set `start` to `/pennantplus/features`. In WorkbenchServiceProvider, remove `atrium.plugins`, the `PennantPlugin` import and `viewAtrium`, and update the docblock.
- [x] 1.7 Run vendor/bin/pest and `composer test:without-atrium`; all green.

## Group 2: OpenAPI export
depends on: 1
files: composer.json, testbench.yaml, workbench/app/Providers/WorkbenchServiceProvider.php, openapi.json, tests/OpenApiTestCase.php, tests/OpenApi/OpenApiTest.php, tests/Pest.php, tests/Feature/ComposerTest.php
criteria: 12, 13, 14

- [x] 2.1 Write the tests first. OpenApiTest, under OpenApiTestCase, binds `uses(...)->in('OpenApi')` in Pest.php and registers the package providers, `ScrambleServiceProvider` and `WorkbenchServiceProvider`. It covers the following: the export to a temporary file equals the committed `openapi.json` after JSON decoding; `info.title` is `PennantPlus` and `info.version` is `2.0.0`; there is one operation per AC 9 route (path and method); there are no absolute paths. ComposerTest: `dedoc/scramble` is in require-dev and not in require, and src/config have no `Dedoc\`.
- [x] 2.2 Add `dedoc/scramble`, with a caret on the latest release that supports Laravel 13, to require-dev, and add its provider to the testbench.yaml providers. Set `scramble.api_path` to `pennantplus`, `info.version` to `2.0.0` and a fixed `servers` in `WorkbenchServiceProvider::register()`. If no compatible release exists, stop and escalate (spec Risks).
- [x] 2.3 Export `openapi.json` with `vendor/bin/testbench scramble:export --path=openapi.json` and commit it. Run the export twice to confirm the output is byte-stable.
- [x] 2.4 Run vendor/bin/pest; all green.

## Group 3: TypeScript client
depends on: 2
files: client/package.json, client/package-lock.json, client/tsconfig.json, client/openapi.json, client/src/schema.d.ts, client/src/index.ts, client/README.md, .gitignore, composer.json, .github/workflows/tests.yml, tests/Feature/ClientTest.php, tests/Feature/ComposerTest.php
criteria: 15, 16, 17, 18

- [ ] 3.1 Write the tests first. ClientTest: `client/openapi.json` is byte-identical to `openapi.json`; the schema regenerates equal to `client/src/schema.d.ts` when `client/node_modules/.bin/openapi-typescript` exists, otherwise the test is skipped with a message naming `npm --prefix client ci`; package.json has the name, version, private flag, type, dependency, devDependencies, scripts and dist entry points from AC 15. ComposerTest: the `client:generate` steps in order (AC 17), and the tests.yml `client` job runs `npm --prefix client ci`, `composer client:generate`, `git diff --exit-code -- openapi.json client` and `npm --prefix client run build`.
- [ ] 3.2 Create client/package.json and tsconfig.json (strict, declaration, outDir dist), and add `client/node_modules` and `client/dist` to .gitignore. Run `npm --prefix client install` and commit package-lock.json.
- [ ] 3.3 Write client/src/index.ts with `createPennantPlusClient(options?: ClientOptions): Client<paths>` and the type re-exports. Add a short client/README.md.
- [ ] 3.4 Add the `client:generate` composer script (export, copy, `npm --prefix client run generate`). Run it and commit client/openapi.json and client/src/schema.d.ts. Add the `client` CI job (ubuntu, PHP 8.5, Node LTS).
- [ ] 3.5 Run `npm --prefix client run build` and vendor/bin/pest (ClientTest not skipped); all green.

## Group 4: Docs and full gate
depends on: 1, 3
files: README.md, CHANGELOG.md, resources/boost/skills/pennantplus-development/SKILL.md, CLAUDE.md, AGENTS.md, tests/Unit/DocumentationTest.php
criteria: 19, 20, 21, 22, 23

- [ ] 4.1 Write the tests first, in DocumentationTest. README: describes the API and MCP, has an "OpenAPI and TypeScript client" section with `createPennantPlusClient` and `composer client:generate`, and mentions Atrium v2 as the optional UI. Neither README nor the Boost skill mentions `@pennantplusManages`, `FeatureValueRow`, `FeatureFlagAccess` or `resolve_features`. CHANGELOG has a `v2.0.0` section with Breaking, Upgrade and Added. CLAUDE.md and AGENTS.md have no `src/Atrium` and name `composer client:generate`.
- [ ] 4.2 README: remove the "Atrium dashboard", "Screen extensions" and "Atrium's theme switcher" sections and the Atrium wording in the intro, API and Testing sections. Add the client section.
- [ ] 4.3 CHANGELOG: add a v2.0.0 section with Breaking (every removal in AC 20), Upgrade (Atrium v2 for the UI and Atrium's own Pennant integration) and Added (openapi.json and the client).
- [ ] 4.4 Boost skill: drop section 7 and the resolver paragraph, remove the page wording throughout, and describe the client. In CLAUDE.md and AGENTS.md, replace the Atrium layout and Frontend guidance with the no-Atrium rule and the generated-artifacts rule.
- [ ] 4.5 Run vendor/bin/pest and the full `composer test`; all green.
