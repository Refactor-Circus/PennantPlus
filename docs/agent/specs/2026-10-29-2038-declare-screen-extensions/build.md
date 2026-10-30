# Build: Declare screen extensions (PennantPlus)

## Group 2

Worktree `PennantPlus--2026-10-29-2038-declare-screen-extensions--g2`, branch `ring-master/2026-10-29-2038-declare-screen-extensions--g2`, commit `119e958` ("2026-10-29-2038-declare-screen-extensions: group 2 Atrium optional"). Criteria 10 and 11.

### Files

- `composer.json`: `conflict` `{"refactor-circus/atrium": "<0.2.0"}`; new `test:without-atrium` script (`vendor/bin/pest --configuration=phpunit.without-atrium.xml --test-directory=tests/WithoutAtrium`), called by `test` right after `@test:unit`. Atrium stays in `require-dev` (`dev-main`) and `suggest`. The same edits were mirrored by hand into the worktree's gitignored `composer.local.json`, which was not regenerated.
- `phpunit.without-atrium.xml`: one `WithoutAtrium` testsuite (`tests/WithoutAtrium`), bootstrap `tests/WithoutAtrium/bootstrap.php`. The other settings match `phpunit.xml.dist`.
- `tests/WithoutAtrium/bootstrap.php`: requires `vendor/autoload.php` and unsets the `RefactorCircus\Atrium\` PSR-4 prefix (dirs and length entries) on every registered Composer loader.
- `tests/WithoutAtrium/WithoutAtriumTestCase.php`: Testbench with Pennant, MCP and PennantPlus providers only (no Atrium provider), sqlite in memory, Pennant's migrations. It turns on the MCP web route (`mcp/pennantplus`) for the `tools/list` check and sets the whole `pennantplus.mcp` block, because the package config merges one level deep.
- `tests/WithoutAtrium/WithoutAtriumTest.php`: checks that the prefix is gone from every loader, that `AtriumServiceProvider`, `Gatekeeper` and `PluginRegistry` do not load, and that the provider boots with its guards holding (no `pennantplusManages` Blade directive, `FeatureFlagAccess` and `PennantPlugin` not loaded). It also checks that no `atrium.` route exists while `pennantplus.features.index` does, that the JSON API answers, that MCP `tools/list` succeeds over the web route, and that no `RefactorCircus\Atrium\*` or `RefactorCircus\PennantPlus\Atrium\*` class, interface or trait is declared after a JSON request and an MCP call.
- `tests/Feature/ComposerTest.php`: checks that `require` has no Atrium and that Keystone is its only `refactor-circus/*` package, that Atrium is in `suggest` and in `require-dev` as `dev-main`, and that `conflict` is exactly `{"refactor-circus/atrium": "<0.2.0"}`. It also checks that `test:without-atrium` is called right after `@test:unit`, and that the CI `without-atrium` job runs `composer remove --dev refactor-circus/atrium --no-update`, then `composer update`, then `composer test:without-atrium`, in that order.
- `.github/workflows/tests.yml`: new `without-atrium` job (ubuntu, PHP 8.5). It removes Atrium and Cortex with `--no-update`, runs `composer update --no-scripts`, then runs `composer test:without-atrium`. (Corrected on the retry, see below.)

### Retry

- **Cortex requires Atrium.** The first pass said no dev package requires Atrium, but `refactor-circus/cortex` lists `refactor-circus/atrium` in its `require`. Removing only Atrium would make `composer update` in the CI job fail. The job now runs `composer remove --dev refactor-circus/atrium refactor-circus/cortex --no-update`. PennantPlus reaches Cortex only through Keystone's guarded `CortexIntegration`, so the suite does not need it.
- **New test** in `tests/Feature/ComposerTest.php`: `removes atrium and every dev package that requires it in the without-atrium job`. It reads `vendor/{package}/composer.json` for each `require-dev` package and checks that every package whose `require` names Atrium is in the job's `composer remove --dev` list. The order test now matches any `composer remove --dev ... --no-update` line, not only the Atrium-only one.
- Commit: "2026-10-29-2038-declare-screen-extensions: group 2 Atrium optional" (`87be23c`, second commit on the group branch). `pint --test` passes on `ComposerTest.php`. I did not run the suite; the gate runs it.
- **`composer.local.json`.** It already has the `conflict` and the `test:without-atrium` script, so I left it alone. Its Atrium path repository still says `../Atrium`, and so does `composer.local.lock`, but `vendor/refactor-circus/atrium` is a symlink to `../Atrium--2026-10-29-2037-screen-extensions-registry`. I did not regenerate the file or reinstall.

### Tests

The gate runs the suite; I did not run it. `php -l` passes on the new PHP files, `composer validate` passes, and `pint --test` passes on the new tests.

### Deviations and notes

- **`--test-directory=tests/WithoutAtrium`.** Pest loads `{test-directory}/Pest.php` before PHPUnit's bootstrap. `tests/Pest.php` calls `uses(TestCase::class)`, and Pest calls `class_exists()` on it, which loads `tests/TestCase.php`. Once group 1 adds `ResetsScreenExtensions` to that class, this would load an Atrium trait before the prefix is removed, or fatal once Atrium is uninstalled. The suite therefore has its own test directory with no `Pest.php`, and its test file applies `WithoutAtriumTestCase` itself.
- **`composer update --no-scripts` in CI.** `testbench.yaml` names `RefactorCircus\Atrium\AtriumServiceProvider`, so the `post-autoload-dump` `package:discover` would fail with Atrium removed. Testbench test cases ignore package discovery, so the suite does not need it.
- **Conflict value `<0.2.0`.** The spec defers the exact release. Atrium has no tags, and its CHANGELOG names only a `v0.1.0` placeholder, so this uses the value Keen's sibling spec set for the same cross-spec decision (Keen D-029). `dev-main` installs are not matched.
- **MCP check by URL.** Laravel MCP's web route has no name, so the test posts to `WithoutAtriumTestCase::MCP_ROUTE` instead of `route(...)` (`testing/test-layers` asks for route names).
- Not changed, because the files are outside this group: `.gitattributes` does not `export-ignore` `phpunit.without-atrium.xml`, so the file ships in dist archives (`phpunit.xml.dist` is export-ignored).

## Group 1 (salvaged)

Worktree `PennantPlus--2026-10-29-2038-declare-screen-extensions--g1`, branch `ring-master/2026-10-29-2038-declare-screen-extensions--g1`. Criteria 1 to 9.

### What I found

- No `index.lock` and no merge or rebase in progress. The group's paths had no uncommitted changes. The only change in the tree is `AGENTS.md`, which is outside this group, so I left it alone.
- The build agent had already committed all the work as `3585ddd` ("2026-10-29-2038-declare-screen-extensions: group 1 Feature flags screen"). It ran out before ticking tasks and writing this section. I checked the commit against tasks 1.1 to 1.5:
  - `src/Atrium/Data/FeatureValueRow.php`: final, unsaved model stand-in. It has a string key `key` (the feature name, `rawurlencode`d so `|` can't collide, then `|`, then the scope string Pennant stores), no timestamps and `public private(set) StoredFeatureValue $value`. It is built with `for()`, and `save()`, `fresh()`, `refresh()` and `delete()` throw `LogicException`.
  - `FeatureFlagController::index`: runs `ScreenExtensions::resolve(Screen::standard('pennantplus.features', Screen::LIST)->access(Gate closure), $request)` after `abort_unless($manages, 403)`, and passes `resolved` and `rows` (the current page through `FeatureValueRow::for`) next to `values`.
  - View: `header.actions` in the page header's `actions` slot, then `screen-tabs`. An open registered tab shows `screen-tab-panel` in place of the body. Otherwise `before` comes first and `after` last. The trailing `row.actions` cell renders only when `$manages` or a row has sections (D-038). The audit trail is gone.
  - `PennantPlugin::navigation()` no longer calls `historyNavItem()`.
  - `tests/TestCase.php` uses `ResetsScreenExtensions`. `tests/ArchTest.php` excludes `FeatureValueRow` by name with a comment, and adds a rule that it is final and has no table. `docs/agent/standards-feedback.md` has the exclusion row.
  - Tests: `tests/Unit/FeatureValueRowTest.php`, `tests/Feature/Ui/ScreenExtensionsTest.php` (declaration, sections per slot, layout order, overview, `?tab=`, unknown tab, pagination, row record, Gate cases, 403 with zero calls, nothing registered, audit-trail, nav and source scan) and `tests/Feature/Ui/ScreenIsolationTest.php` (the ordered pair).
  - Deleted: `tests/Feature/AtriumAuditTrailTest.php` and `tests/Feature/HistoryNavigationTest.php`. List them in the PR.
- **Outside the group's files:** `3585ddd` also adds a `Unit` testsuite (`tests/Unit`) to `phpunit.xml.dist`. Without it the new `tests/Unit/FeatureValueRowTest.php` would not run. The Unit tests run on Pest's plain test case, because `tests/Pest.php` applies `TestCase` only to `Feature`.

### What I committed

Nothing new. The group's work is already in `3585ddd`, and the group's paths had nothing uncommitted. `php -l` passes on `FeatureValueRow.php` and `FeatureFlagController.php`.

### What is left

- 1.6: the suite has not been run. I was told not to run it, and the gate runs it after the build.

## Group 1

Worktree `PennantPlus--2026-10-29-2038-declare-screen-extensions--g1`, branch `ring-master/2026-10-29-2038-declare-screen-extensions--g1`, commit `3585ddd` ("2026-10-29-2038-declare-screen-extensions: group 1 Feature flags screen"). Criteria 1 to 9.

### Files

No new changes. Tasks 1.1 to 1.5 are in `3585ddd` (see "Group 1 (salvaged)" above for each file). This retry covered only 1.6.

### Tests

- 1.6 is ticked. The gate runs `vendor/bin/pest` after the build, so I did not run it.
- `pint --test` passes on `src/Atrium`, `tests/Unit`, `tests/Feature/Ui`, `tests/TestCase.php` and `tests/ArchTest.php`.

### Deviations

- Same as the salvaged section: `3585ddd` also adds a `Unit` testsuite to `phpunit.xml.dist`, which is outside the group's files. Without it `tests/Unit/FeatureValueRowTest.php` would not run.
- `docs/agent/standards-feedback.md` has the row for the named `FeatureValueRow` arch exclusion.
- The PR must list the deleted `tests/Feature/AtriumAuditTrailTest.php` and `tests/Feature/HistoryNavigationTest.php`.

## Integration, wave 1

Merged `--g1` then `--g2` into `ring-master/2026-10-29-2038-declare-screen-extensions` with `--no-ff`. Neither merge had conflicts, because the two groups touch no files in common. Fixes are in `c8fe680` ("2026-10-29-2038-declare-screen-extensions: integrate wave 1").

- **`tests/Feature/Ui/ScreenExtensionsTest.php` (group 1).** Six tests that render a registered section or tab failed. The cause was the test's `PennantScreenMarker` component. Blade's `@component` compiles a class component only when the name holds `\` or `::class`, so a global class compiles as a view. That either failed to resolve or, once registered class-first, emitted an unbalanced `endif`. The file now declares `namespace RefactorCircus\PennantPlus\Tests\Feature\Ui`, imports the SPL iterators it uses, and registers `Blade::component(PennantScreenMarker::class, 'pennant-marker')`. No assertion changed.
- **`phpunit.without-atrium.xml` (group 2).** The header comment held `--test-directory`, and XML forbids `--` inside a comment, so PHPUnit refused to load the suite. The comment now describes the option without spelling it out.
- **Tests.** `composer test:unit`: 157 passed. `composer test:without-atrium`: 7 passed. `pint --test` passes on the edited test.

## Group 3

Branch `ring-master/2026-10-29-2038-declare-screen-extensions`, commit "2026-10-29-2038-declare-screen-extensions: group 3 Docs and gate". Criteria 12 and 13.

### Files

- `tests/Unit/DocumentationTest.php` (new, outside the group's file list; see Deviations). It is plain PHP with no framework boot, so it lives in `tests/Unit`.
  - It scans `src`, `resources` (Markdown included), `README.md`, `CLAUDE.md` and `docs/agent/product` (skipped while missing) for `audit-trail` and `historyNavItem`. This is the done-step grep as a test. Group 1's source scan skips Markdown and leaves the docs to this test.
  - It checks that the README and the Boost skill name `pennantplus.features`, the `header.actions`, `before`, `after` and `row.actions` slots, `FeatureValueRow`, refactor-circus/keen and the minimum Atrium. The minimum Atrium is read from the `conflict` entry in `composer.json`, so the docs follow when the release is set.
  - It checks that both say a row permission is a Gate ability, and that they name `$record->value->scope` as the raw scope.
  - The test failed (5 of 5) before the docs changed.
- `README.md`: the audit-trail paragraph is gone. The page now says it has no history or history link of its own, and that Keen adds its History section. A new "Screen extensions" subsection covers:
  - the screen key and the minimum Atrium (0.2.0, through `conflict`);
  - the slots, and Keen's default slot `after`;
  - `overview` and `?tab=`;
  - the page's own check running first;
  - Gate abilities with the row as the argument, guests asked as guests, undefined abilities denied;
  - `FeatureValueRow` and its raw-scope warning;
  - the "do not throw" note from the spec's risks;
  - a registration example.
- `resources/boost/skills/pennantplus-development/SKILL.md`: the same changes in short form in section 7, with the example.
- `CHANGELOG.md` (Unreleased):
  - Removed the two unreleased entries this spec reverses: the sidebar **Audit log** link and the page's audit trail.
  - Added the screen declaration and the WithoutAtrium suite.
  - Added two Breaking entries: the Atrium 0.2.0 `conflict`, and history moving to Keen. The history route and MCP tool stay.
- `CLAUDE.md`, a symlink to `AGENTS.md`, so the edit is in `AGENTS.md`:
  - A Frontend paragraph on the declared screen, the access closure, the layout, `FeatureValueRow`/`rows`, Keen for history and `ResetsScreenExtensions`.
  - A paragraph on Atrium staying optional, with the `conflict` and the WithoutAtrium suite.
  - The arch-test note now names the `FeatureValueRow` exclusion instead of "PennantPlus has none today".

### Tests

- `vendor/bin/pest tests/Unit/DocumentationTest.php`: 5 passed (23 assertions). `pint --test` passes on it.
- Done-step grep for the PR: `grep -rn -E "audit-trail|historyNavItem" src resources README.md CLAUDE.md docs/agent/product` gives no output.
- 3.3 is ticked without running `composer test`: the gate runs the full suite after the build, as instructed.

### Deviations

- **`tests/Unit/DocumentationTest.php` is outside the group's file list.** Task 3.1 asks for a check, and the job asks for the tests first. The docs check needed a test file, and none of the group's files is a test.
- **`AGENTS.md`** is the real file behind `CLAUDE.md`, so it is the file committed. Before this group, the worktree had an uncommitted ring-master sync line in `AGENTS.md` (the "Keep this checkout on `main`" bullet). I kept it out of this commit and left it uncommitted afterwards, as I found it.
- The spec's grep does not cover `CHANGELOG.md`, so the test does not scan it either. It already holds neither name: everything in it is unreleased, and the two entries that named them are gone.
