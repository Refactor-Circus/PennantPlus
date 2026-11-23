# PennantPlus v2: API and MCP only

## Goal

PennantPlus v2.0.0 ships only its JSON API and MCP server. The Atrium plugin, the Atrium feature resolver and the `@pennantplusManages` Blade conditional are deleted. Atrium v2 hosts the UI and calls the API through a committed OpenAPI document and a generated TypeScript client.

## Acceptance criteria

### Removal

1. `src/Atrium/` no longer exists (`PennantPlugin`, `FeatureFlagAccess`, `Data/FeatureValueRow`, `Http/Controllers/FeatureFlagController`, `Http/Requests/*` and `Http/Requests/Concerns/AuthorizesFeatureFlags` are gone). `tests/ArchTest.php` has a rule that `RefactorCircus\PennantPlus` does not use `RefactorCircus\Atrium`. A test also finds no case-insensitive `atrium` in any file under `src/`, `config/`, `lang/` or `database/`.
2. `PennantPlusServiceProvider` no longer has `registerAtriumFeatureResolver()` or `registerAtriumBladeConditional()`, and imports no Atrium class. After boot in the main suite, `Blade::getCustomDirectives()` has no `pennantplusManages`, `elsepennantplusManages` or `endpennantplusManages`, and no route is named `atrium.pennant.*`.
3. `config/pennantplus.php` has no `atrium` key, so `pennantplus.atrium.resolve_features` is gone. No banner mentions Atrium, a dashboard or a page. The `ability`, `store` and `scope_types_cache_seconds` banners mention only the API endpoints and MCP tools.
4. `resources/views/` is deleted. The provider no longer calls `loadViewsFrom()` and has no `pennantplus-views` publish tag. `vendor:publish --tag=pennantplus` publishes only the config and lang files, and a test asserts this.
5. `lang/en/pennantplus.php` keeps only the keys `src/` uses (`global`, `other`, `scope_missing`, `feature_disabled`). A test asserts that `src/` references every key that remains.
6. `composer.json`:
   - `refactor-circus/atrium` is not in `require`, `require-dev`, `suggest` or `conflict`.
   - There is no `extra.atrium` (so no `extra.atrium.plugins`).
   - The `description` does not mention Atrium.
   - Keystone stays the only `refactor-circus/*` entry in `require`.

   `tests/Feature/ComposerTest.php` asserts each of these.
7. The Atrium-only tests are deleted:
   - `tests/Feature/AtriumFeatureResolverTest.php`, `AtriumPluginTest.php`, `AtriumScreenControlsTest.php`, `AtriumThemeSwitcherTest.php` and `NavigationSectionTest.php`
   - `tests/Feature/Ui/*`
   - `tests/Unit/FeatureValueRowTest.php`
   - the Atrium-page test in `PerformanceTest.php` ("asks whether the viewer manages feature flags once per page")
   - the Atrium cases in `DocumentationTest.php` and `ComposerTest.php`

   `tests/TestCase.php` no longer registers `AtriumServiceProvider`, uses `ResetsScreenExtensions` or sets `atrium.*` config. `CortexTestCase` adds back only what Cortex needs to boot.
8. The `tests/WithoutAtrium` suite (`composer test:without-atrium`, `phpunit.without-atrium.xml`) still proves that the provider boots and that the JSON API and MCP server answer with Atrium unloadable. It no longer names the deleted classes or the Blade directive. The CI `without-atrium` job uninstalls `refactor-circus/cortex`, which pulls in Atrium, without naming a package that is no longer in `require-dev`. `ComposerTest` checks the job the same way it does today.
9. The JSON API is unchanged. A test asserts that the registered routes include exactly these `pennantplus.*` API names, with their verbs and URIs as in `src/Domains/*/routes.php`:
   - `pennantplus.features.index`, `.show`, `.check` and `.destroy`
   - `pennantplus.values.index`, `.update` and `.destroy`
   - `pennantplus.scopes.index` and `.models`
   - `pennantplus.history.index`
10. The MCP server keeps its ten tools unchanged, and the existing `tests/Feature/Mcp/*` and `tests/Cortex/*` tests pass without edits to their assertions.
11. `testbench.yaml` and `workbench/` reference no Atrium provider, asset, route or ability:
    - `AtriumServiceProvider` and the `atrium-assets` asset are removed.
    - `workbench.start` is `/pennantplus/features`.
    - `WorkbenchServiceProvider` no longer sets `atrium.plugins` or defines `viewAtrium`.

### OpenAPI

12. `dedoc/scramble` is in `require-dev` only. Nothing under `src/` or `config/` references `Dedoc\`.
13. `openapi.json` is committed at the package root and is exported by `scramble:export` against the Testbench workbench:
    - It has an OpenAPI 3.x document whose `info.title` is `PennantPlus` and whose `info.version` is `2.0.0`.
    - It has one operation for each route in AC 9, matching path and method.
    - It contains no machine-specific values (no absolute file paths and no random keys).
14. A Pest test exports the document again into a temporary file, using the same workbench configuration, and asserts that it equals the committed `openapi.json` after JSON decoding. The test fails when the API changes and the document is not regenerated.

### TypeScript client

15. `client/package.json` declares the following:
    - name `@refactor-circus/pennantplus-client`, version `2.0.0`, `"private": true` and `"type": "module"`
    - `openapi-fetch` as a dependency, and `openapi-typescript` and `typescript` as devDependencies
    - the scripts `generate` (`openapi-typescript openapi.json -o src/schema.d.ts`), `build` (`tsc -p .`) and `prepare` (`npm run build`)
    - `main`, `types` and `exports` pointing into `dist/`

    `client/package-lock.json` is committed. `client/node_modules` and `client/dist` are gitignored.
16. `client/src/schema.d.ts` is the committed `openapi-typescript` output for `client/openapi.json`. `client/src/index.ts`:
    - exports `createPennantPlusClient(options?: ClientOptions): Client<paths>`, which wraps `openapi-fetch`'s `createClient<paths>()`
    - re-exports the `paths`, `components` and `operations` types

    `npm --prefix client run build` type-checks and builds without errors.
17. The composer script `client:generate` regenerates, in order:
    1. `openapi.json`, through the Testbench `scramble:export`
    2. `client/openapi.json`, copied from the root document
    3. `client/src/schema.d.ts`, through `npm --prefix client run generate`

    `ComposerTest` asserts these steps.
18. A Pest client test does two checks:
    - It always asserts that `client/openapi.json` is byte-identical to `openapi.json`.
    - When `client/node_modules/.bin/openapi-typescript` exists, it regenerates the schema into a temporary file and asserts that it equals `client/src/schema.d.ts`. When the binary is missing, the test is skipped with a message that names `npm --prefix client ci`.

    A new CI job `client` (Ubuntu, PHP 8.5, Node LTS) runs `npm --prefix client ci`, then `composer client:generate`, then `git diff --exit-code -- openapi.json client` and `npm --prefix client run build`. A stale document or client therefore fails CI.

### Docs and gate

19. `README.md`:
    - It describes the package as layered Pennant flags with a JSON API and an MCP server. The "Atrium dashboard", "Screen extensions" and "Atrium's theme switcher" sections are removed.
    - A new "OpenAPI and TypeScript client" section covers `openapi.json`, installing the client as a git dependency on the v2 tag, a `createPennantPlusClient` example, and `composer client:generate`.
    - It says that Atrium v2 provides the optional UI.

    `DocumentationTest` asserts these points. It also asserts that neither README nor the Boost skill mentions `@pennantplusManages`, `FeatureValueRow`, `FeatureFlagAccess` or `resolve_features`.
20. `CHANGELOG.md` has a `v2.0.0` section with these parts:
    - **Breaking**: lists each removal, namely the Atrium page and plugin (`extra.atrium.plugins`), the feature resolver and `pennantplus.atrium.resolve_features`, `@pennantplusManages`, `FeatureFlagAccess`, `FeatureValueRow`, the views and the `pennantplus-views` tag, and the pruned lang keys.
    - **Upgrade**: points to Atrium v2 for the UI and to Atrium's own Pennant integration for its feature flags.
    - **Added**: `openapi.json` and the client.
21. The Boost skill `resources/boost/skills/pennantplus-development/SKILL.md` drops section 7 (the Atrium page) and the Atrium-resolver paragraph. It describes the API, MCP and the client, and does not mention the page anywhere, including the "do not" list.
22. `CLAUDE.md` and `AGENTS.md` (kept identical in their shared guidance) no longer describe `src/Atrium`, the screen registry, `ResetsScreenExtensions` or Atrium styles. They state the following:
    - Nothing in the package names Atrium.
    - `openapi.json` and `client/` are generated, are refreshed with `composer client:generate`, and are checked by the staleness tests.
23. `composer test` passes: PHPStan, Pint, type coverage at 100%, the unit suite and `test:without-atrium`.

## Design

**Deletion.** Remove `src/Atrium/**`, `resources/views/atrium/**` (and with it `resources/views`), the two private Atrium methods in `PennantPlusServiceProvider`, its `loadViewsFrom()` call and its `pennantplus-views` `publishes()` call, the `atrium` section of `config/pennantplus.php`, and the Atrium entries in `composer.json`. `FeatureGate` and `gate.global_only` (`*SupportFeature`) stay. They are generic and gate routes and MCP tools. Only Atrium's resolver went through them. `ArchTest` loses the `src/Atrium/Http/Requests` glob and the `FeatureValueRow` exclusion, and gains `arch('names no atrium')->expect('RefactorCircus\PennantPlus')->not->toUse('RefactorCircus\Atrium')`. Atrium remains in `vendor/` in development because Cortex requires it, so the `Atrium` VCS repository entry stays (D-006).

**OpenAPI.** Add `dedoc/scramble` to `require-dev` and `Dedoc\Scramble\ScrambleServiceProvider` to the `testbench.yaml` providers. `WorkbenchServiceProvider::register()` sets `scramble.api_path` to `pennantplus`, `scramble.info.version` to `2.0.0` and a fixed `scramble.servers`. Both the export and the staleness test read this one configuration. `tests/OpenApi/OpenApiTest.php` runs under a dedicated `tests/OpenApiTestCase.php`, bound in `tests/Pest.php` with `uses(...)->in('OpenApi')`. That test case registers the package providers, the Scramble provider and `WorkbenchServiceProvider`, then calls `Artisan::call('scramble:export', ['--path' => $tmp])`. It also asserts the route coverage in AC 13. The route-name test in AC 9 lives in `tests/Feature/Http/RouteListTest.php`.

**Client.** The client lives in `client/`:
- `package.json` and `package-lock.json`
- `tsconfig.json` (strict, `declaration: true`, `outDir: dist`)
- `openapi.json`, a copy of the root document, so `openapi-typescript` runs inside the package
- `src/schema.d.ts`, generated
- `src/index.ts`
- `README.md`, a short usage note

The wrapper:

```ts
import createClient, { type Client, type ClientOptions } from 'openapi-fetch';
import type { paths } from './schema';
export type { paths, components, operations } from './schema';
export function createPennantPlusClient(options: ClientOptions = {}): Client<paths> {
  return createClient<paths>(options);
}
```

Consumers install it as a git dependency. npm and pnpm run `prepare`, which builds `dist/`, so `dist/` is not committed (D-008). The client is not export-ignored, because npm's GitHub tarballs honour `export-ignore`. `tests/Feature/ClientTest.php` holds the checks in AC 18. CI adds a `client` job to `.github/workflows/tests.yml`.

**Composer script.**

```json
"client:generate": [
  "@php vendor/bin/testbench scramble:export --path=openapi.json --ansi",
  "@php -r \"copy('openapi.json', 'client/openapi.json');\"",
  "npm --prefix client run generate"
]
```

**Config and API defaults.** The defaults stay as they are: `routes.enabled` is `true`, the middleware is `['api']` and the ability is `null` (D-002). The spec adds no new config keys.

**Departures from standards.** None. This spec only removes code and adds dev-only tooling.

## Out of scope

- New endpoints, including the `stats`, `search` and `settings-summary` endpoints listed in shape.md (D-001).
- Any change to API payloads, MCP tools, actions, events, the driver or the gate.
- Atrium's port of the page, its integration flags and `atrium.integrations.pennantplus.*`. The Atrium v2 spec owns these.
- Keystone's removal of `registerAtriumPlugin()`. This package never calls it (Keystone spec, `waits_for`).
- Publishing the client to an npm registry.
- Changing the Cortex dev dependency, or the Atrium it pulls in.

## Risks and open questions

- **Scramble on Laravel 13 / PHP 8.5.** If no stable `dedoc/scramble` release supports `laravel/framework ^13.35`, the build stops and escalates. It does not fork Scramble or hand-write the document.
- **Schema accuracy.** Scramble infers schemas from the requests' `rules()`, which return `XData::rules()`, and from the resources. Some fields may come out loosely typed. The spec requires a complete, deterministic document, not exact typing. Tightening it with Scramble attributes is a follow-up.
- **Determinism.** The export must be byte-stable across runs and operating systems (key order, `servers`). The Windows CI job runs the staleness test too. If line endings differ there, the test compares decoded JSON (AC 14), and `.gitattributes` already forces `eol=lf`.
- **npm subdirectory git dependencies.** npm cannot install a package from a subfolder of a git repository, and pnpm can (`#v2.0.0&path:/client`). How Atrium's Nuxt layer pins the client is decided in the Atrium spec. If it needs a published package or a root `package.json`, that is a follow-up here.
- **Transitive Atrium.** Cortex (dev) still installs Atrium v0.1, whose auto-discovered provider boots in the workbench. Its routes sit outside `api_path`, so they stay out of `openapi.json`.
