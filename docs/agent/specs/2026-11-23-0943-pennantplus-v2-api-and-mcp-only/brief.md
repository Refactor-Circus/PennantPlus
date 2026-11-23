Risk: high

## What changed
- Removal: `src/Atrium/` (plugin, resolver, controller, requests, `FeatureValueRow`), `resources/views`, the `@pennantplusManages` directive, the `pennantplus-views` publish tag, the `atrium` config key, Atrium composer entries and pruned lang keys. Atrium-only tests deleted.
- OpenAPI: `dedoc/scramble` added to `require-dev`; configured in the workbench; `openapi.json` committed at the root.
- Client: new `client/` package (`@refactor-circus/pennantplus-client` 2.0.0) with `openapi-fetch` wrapper and generated `schema.d.ts`; `composer client:generate` script.
- CI: new `client` job; `without-atrium` job reworked. Docs, CHANGELOG (v2.0.0), Boost skill, CLAUDE.md and AGENTS.md updated.
- Diff size: 53 files, +3503 / -2319 (mostly generated JSON and deleted Atrium code).

## Why
PennantPlus v2.0.0 ships only its JSON API and MCP server. Atrium v2 hosts the UI and calls the API through a committed OpenAPI document and a generated TypeScript client. Decisions that shaped it: no new endpoints (D-001), API defaults unchanged (D-002), keep the Atrium VCS repo entry because Cortex needs it (D-006), delete views and prune lang (D-007), client built by `prepare` with `dist/` ignored (D-008), a Node-free staleness test plus a CI job (D-009), Scramble config in the workbench provider (D-010).

## Look at
- `composer.json:99`: the `client:generate` script and the dependency changes (Atrium entries removed, scramble at line 28).
- `workbench/app/Providers/WorkbenchServiceProvider.php:42`: the single Scramble configuration used by both export and test.
- `src/PennantPlusServiceProvider.php`: Atrium methods, view loading and publish tag removed; check nothing else relied on them.
- `config/pennantplus.php`: `atrium` key removed (breaking for anyone setting `resolve_features`).
- `.github/workflows/tests.yml`: new `client` job and the changed `without-atrium` job.

## Tested
The round-1 gate is PASS: `composer test` passed (PHPStan, Pint, 100% type coverage, 128 unit tests, 7 without-atrium tests).
- AC 1-5: NoAtriumTest, ArchTest "names no atrium", PublishTest.
- AC 6, 8, 12, 17: ComposerTest.
- AC 9: RouteListTest.
- AC 10: the existing Mcp and Cortex tests, unchanged and passing.
- AC 13-14: OpenApiTest.
- AC 15-16, 18: ClientTest.
- AC 19-22: DocumentationTest.
- AC 7 and 11 are checked from the diff.

## Risks
- Breaking release: any consumer of the Atrium page, `@pennantplusManages`, `resolve_features`, the views tag or the removed lang keys breaks (listed in the CHANGELOG).
- `npm --prefix client run build` was not run in the gate. The CI `client` job is the first real check.
- Scramble typing may be loose in places, and export determinism across operating systems is unproven on Windows CI.
- npm cannot install from a git subfolder, so how Atrium consumes the client is left to the Atrium spec.
- Cortex still pulls Atrium v0.1 in development.
- Open findings: none (nits: 0).
