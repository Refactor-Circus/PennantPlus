Risk: high

## What changed
- Controller and view: `FeatureFlagController::index` builds the `pennantplus.features` list screen, resolves it on Atrium's screen extensions registry after the 403 check, and passes `resolved` and `rows` to the view. `resources/views/atrium/index.blade.php` renders `header.actions`, tabs, tab panel, `before`/`after` and a `row.actions` trailing cell.
- New `src/Atrium/Data/FeatureValueRow.php`: an unsaved, read-only Model stand-in for each stored value.
- Removed the inline audit trail and the History nav item (`PennantPlugin::navigation()`), plus their two tests.
- Composer and CI: `conflict` on `refactor-circus/atrium` below 0.2.0, a `test:without-atrium` script run by `composer test`, a `WithoutAtrium` suite (`phpunit.without-atrium.xml`, `tests/WithoutAtrium`) and a `without-atrium` CI job.
- Docs: README, Boost skill, AGENTS.md, CHANGELOG, and a row in `standards-feedback.md` for the arch exclusion.

## Why
Atrium PR #16 adds a screen extensions registry. PennantPlus declares its flags page there so Keen's History and other packages add tabs and sections without PennantPlus knowing them. Decisions: D-034 (WithoutAtrium suite plus `conflict`), D-036/D-040/D-042 (`FeatureValueRow` with a named arch exclusion), D-038 (trailing cell only when `$manages` or sections exist), D-039 (`conflict`), D-043 (key from `FeatureFlagManager`'s scope serialization). D-037/D-041 leave the provider move and the arch rule out.

## Look at
- `composer.json:41`: the new `conflict` (`<0.2.0`) is a dependency-contract change. Atrium has no tags yet, so confirm 0.2.0 will be the release that carries the registry.
- `src/Atrium/Http/Controllers/FeatureFlagController.php:42`: `resolve()` must stay after `abort_unless($manages, 403)`; the Gate-based `access` closure is at line 116.
- `src/Atrium/Data/FeatureValueRow.php`: the key encoding (no `|` collisions) and the throwing persistence methods.
- `resources/views/atrium/index.blade.php`: large diff, mostly reindentation. Check the tab/panel replacing the body and the conditional trailing cell.
- `.github/workflows/tests.yml:78`: the `without-atrium` job removes Atrium and Cortex and runs with `--no-scripts`.

## Tested
Gate round 1 passed: `composer test` (phpstan, pint, 162 Pest tests, 7 WithoutAtrium tests), `lint:check` and ringside check. Criteria 1, 4, 5, 6 and 7 are covered by `tests/Feature/Ui/ScreenExtensionsTest.php`; 2 by `tests/Unit/FeatureValueRowTest.php` plus that feature test; 3 by `tests/ArchTest.php`; 8 and 9 by `tests/Feature/Ui/ScreenIsolationTest.php`; 10 by `tests/Feature/ComposerTest.php`; 11 by `tests/WithoutAtrium/*`; 12 by `tests/Unit/DocumentationTest.php`. The gate mapped criteria to files and did not check individual test names.

## Risks
- Merge order: this merges only with Atrium PR #16 and Keen, in the order Atrium, Keen, PennantPlus. Before Atrium is released, `conflict <0.2.0` may fail installs against `dev-main`.
- The flags page administers production flags, and a third-party resolver or `authorize` closure that throws follows Atrium's handling. The README tells registrants not to throw.
- `FeatureValueRow` exposes the raw scope identifier to row permission checks.
- The CI job drops Cortex too, because Cortex requires Atrium.
- Open item (nits: 1): the `require-dev` Atrium constraint stays `dev-main` until the first release, as the spec allows.
