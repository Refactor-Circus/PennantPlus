# Decisions

## D-001: Add the stats, search and settings-summary endpoints from shape.md's "missing data becomes API"?
- Choice: No new endpoints. The JSON API keeps exactly its current routes.
- Rationale: shape.md's scope finds that no API is missing, because every page action already has a route, and the job brief says the same. The PennantPlus page had no widgets, search or settings panel. Adding endpoints would grow the public surface without a consumer.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-002: Does the API stay on by default (`pennantplus.routes.enabled`)?
- Choice: Keep the current defaults: `routes.enabled` true, middleware `['api']`, `ability` null.
- Rationale: Changing them would be a second breaking change that this spec was not shaped for. The config banner already warns to add auth middleware.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-003: What is the exact stats payload?
- Choice: There is none, because no stats endpoint is added (D-001).
- Rationale: The question only exists if the endpoint does.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-004: Do any package-to-package references become their own specs?
- Choice: No new specs. Atrium's port of the UI is the Atrium v2 spec (`blocks`). Keystone's hook removal is the Keystone spec (`waits_for`). PennantPlus never calls `registerAtriumPlugin()`.
- Rationale: The work this package needs from others is already covered by existing specs.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-005: Keep the tests/WithoutAtrium suite and its CI job?
- Choice: Keep the suite, its phpunit config, its composer script and its CI job. Trim them to the API and MCP checks and drop the references to deleted classes. The job uninstalls `refactor-circus/cortex`, which pulls in Atrium.
- Rationale: Cortex, a dev dependency, still installs Atrium, so this suite is the only proof that the package works without Atrium. Keeping that coverage is the conservative choice.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-006: Keep the Atrium VCS repository entry in composer.json?
- Choice: Keep it.
- Rationale: `refactor-circus/cortex` (require-dev) requires `refactor-circus/atrium`. Composer resolves VCS repositories only from the root package, so removing the entry would break `composer update`. No Atrium package entry remains.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-007: What happens to the views and lang files?
- Choice: Delete `resources/views`, `loadViewsFrom()` and the `pennantplus-views` publish tag. Prune `lang/en/pennantplus.php` to the keys `src/` uses (`global`, `other`, `scope_missing`, `feature_disabled`). Keep the lang loading and the `pennantplus-lang` tag.
- Rationale: Only the deleted page used them. The CHANGELOG lists the removals as breaking.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-008: How is the TypeScript client distributed?
- Choice: Commit the TypeScript source and the generated `schema.d.ts`. Build `dist/` through the `prepare` script on install, with `dist/` gitignored. Set `"private": true` and version `2.0.0`. Do not export-ignore `client/`.
- Rationale: npm and pnpm run `prepare` for git dependencies, so there is no committed build output to go stale. `private` prevents an accidental registry publish. GitHub tarballs honour `export-ignore`, so ignoring `client/` would strip the client from git installs.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-009: How does a PHP test detect a stale client without Node?
- Choice: Always assert that `client/openapi.json` equals `openapi.json`. Regenerate `schema.d.ts` and compare it only when `client/node_modules` exists, and skip with a message otherwise. A CI `client` job installs Node, regenerates everything and fails on any `git diff`.
- Rationale: The package suite stays runnable without Node, including the Windows matrix. CI still proves that both generated artifacts are fresh.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-010: Where does the Scramble configuration live?
- Choice: In `WorkbenchServiceProvider::register()`: `api_path` `pennantplus`, `info.version` `2.0.0` and a fixed `servers`. The staleness test registers the same provider.
- Rationale: The CLI export and the test then read one configuration. Scramble stays dev-only and out of `src/` and `config/`.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-011: Which version number, and does the Keystone constraint change?
- Choice: v2.0.0, as `meta.yml` `release` says. Keep `refactor-circus/keystone: ^0.1` unless the Keystone job tags a release that this package must require.
- Rationale: The version was set at shaping. PennantPlus uses none of the Keystone API that the Keystone spec removes.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-012: What is the workbench start page once the Atrium page is gone?
- Choice: `/pennantplus/features`.
- Rationale: It is the plain JSON API, the one surface left to demo. It needs no extra dependency.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-013: Keep the `*SupportFeature` default in `pennantplus.gate.global_only`?
- Choice: Keep it.
- Rationale: It is a generic gate pattern used by routes and MCP tools, and it is not Atrium code. No `PennantPlusSupportFeature` class exists in this package, so there is nothing to move to Atrium.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-014: Spec review
- Choice: Skipped. `meta.yml` goes straight to `status: approved`.
- Rationale: The user instructed the job to proceed without spec review or questions.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-015: Which version does the suite release under?
- Choice: Every suite package releases as v2.0.0 to match: Keystone, Atrium, Cortex, Impex, Keen, PennantPlus, Polycart, Roster and Showroom. Every `refactor-circus/*` constraint (require and require-dev) is `^2.0`, and every repo aliases `dev-main` to `2.0.x-dev`. Foundry and Guardian stay on Keystone `^0.1` until they migrate.
- Rationale: The user said "i'd like everything release as v2 to match". This supersedes any earlier choice to keep `^0.1` constraints or release Keystone as v0.2.0.
- Date: 2026-11-23
- Raised by: user
