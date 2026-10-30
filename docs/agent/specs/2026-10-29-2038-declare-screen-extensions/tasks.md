# Tasks: Declare screen extensions (PennantPlus)

## Group 1: Feature flags screen
package: PennantPlus
depends on: none
files: src/Atrium/Data/FeatureValueRow.php, src/Atrium/Http/Controllers/FeatureFlagController.php, src/Atrium/PennantPlugin.php, resources/views/atrium/index.blade.php, tests/TestCase.php, tests/ArchTest.php, docs/agent/standards-feedback.md, tests/Unit/FeatureValueRowTest.php, tests/Feature/Ui/ScreenExtensionsTest.php, tests/Feature/Ui/ScreenIsolationTest.php, tests/Feature/AtriumAuditTrailTest.php, tests/Feature/HistoryNavigationTest.php
criteria: 1, 2, 3, 4, 5, 6, 7, 8, 9

- [x] 1.1 Write the tests: `FeatureValueRow` unit test, screen declaration, layout and pagination tests, Gate access cases, 403 zero-call test, nothing-registered test, audit-trail and nav removal, and the ordered isolation pair.
- [x] 1.2 Add `ResetsScreenExtensions` to `tests/TestCase.php`, the `FeatureValueRow` stand-in, its named arch exclusion and the standards-feedback row.
- [x] 1.3 Resolve the screen in `FeatureFlagController::index` with the Gate access closure and pass `resolved` and `rows` to the view.
- [x] 1.4 Lay out tabs, panel, slots and the trailing row cell in the view, remove the audit trail, and drop `historyNavItem()` from `PennantPlugin::navigation()`.
- [x] 1.5 Delete `AtriumAuditTrailTest` and `HistoryNavigationTest` and list them in the PR.
- [x] 1.6 Run vendor/bin/pest; all green.

## Group 2: Atrium optional
package: PennantPlus
depends on: none
files: composer.json, tests/Feature/ComposerTest.php, phpunit.without-atrium.xml, tests/WithoutAtrium/*, .github/workflows/tests.yml
criteria: 10, 11

- [x] 2.1 Write the composer.json test and the WithoutAtrium suite (bootstrap, test case, boot, routes, JSON API, MCP assertions).
- [x] 2.2 Add the `conflict` entry and the `test:without-atrium` script to `composer test`, and add the `without-atrium` CI job.
- [x] 2.3 Run vendor/bin/pest; all green.

## Group 3: Docs and gate
package: PennantPlus
depends on: 1, 2
files: README.md, CHANGELOG.md, CLAUDE.md, resources/boost/skills/pennantplus-development/SKILL.md
criteria: 12, 13

- [x] 3.1 Write a check (the grep done-step) that `src`, `resources`, README, CLAUDE.md and `docs/agent/product` contain no `audit-trail` or `historyNavItem`.
- [x] 3.2 Update the README, changelog, CLAUDE.md and the Boost skill.
- [x] 3.3 Run composer test and vendor/bin/pest; all green.
