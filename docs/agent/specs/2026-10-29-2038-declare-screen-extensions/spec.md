# Declare screen extensions (PennantPlus)

## Goal

The PennantPlus feature flags page declares itself on Atrium's screen extensions registry (Atrium spec `2026-10-29-2037-screen-extensions-registry`, PR #16), so other packages, Keen's History among them, add tabs and sections without PennantPlus knowing they exist. PennantPlus drops its inline audit trail and History nav item.

## Acceptance criteria

1. **Screen declared.** `FeatureFlagController::index` builds `Screen::standard('pennantplus.features', Screen::LIST)` (`RefactorCircus\Atrium\Domains\Screens\Data\Screen`) with no record, resolves it with `ScreenExtensions::resolve($screen, $request)` after its `abort_unless($manages, 403)`, and passes the result to the view as `resolved`. It has no built-in tabs, so the synthetic `overview` tab is the default. Proven by `tests/Feature/Ui/ScreenExtensionsTest.php`, which registers a tab and a section in every declared slot (`header.actions`, `before`, `after`, row slot `row.actions`) and asserts each section renders once in its slot (`data-testid="section-{key}"`), an `aside` section does not render, and the tab opens through `?tab=`.
2. **Value rows.** A stored value (`StoredFeatureValue`) is not a model, so each row of the values table is wrapped in `RefactorCircus\PennantPlus\Atrium\Data\FeatureValueRow`, following Atrium's `SettingsPanelRow` (Atrium D-031, D-033). It is a final, unsaved `Model` with a string primary key `key` (`"{feature}|{scope}"`), no timestamps and no table, and it exposes `public private(set) StoredFeatureValue $value`, built with `FeatureValueRow::for(StoredFeatureValue $value): self`. `row.actions` renders once per value row with it as `$record`. A feature test with two stored values asserts the row section renders once per row and its `authorize` closure receives each `FeatureValueRow` with the right `value`. A unit test covers `for()`.
3. **Arch test exclusion.** `tests/ArchTest.php`'s `every model fires an event for every lifecycle hook` scans every `Model` under `src`, so it excludes `FeatureValueRow` by class name, with a comment that it is an unsaved row stand-in. The exclusion is recorded as a row in `docs/agent/standards-feedback.md`. The arch suite passes.
4. **Access closure.** The screen gets `access(fn (string $permission, Request $request, ?Model $record): bool => Gate::forUser($request->user())->allows($permission, $record === null ? [] : [$record]))`. PennantPlus has no policies; its own check (`pennantplus.ability`) is a Gate ability too. A feature test asserts that a `permission('acme.extra')` section renders only once `Gate::define('acme.extra', …)` allows the user.
5. **Own authorization first.** A user who may not manage flags gets 403 and no registered `authorize` closure or resolver runs (feature test with counting closures).
6. **Layout.** `header.actions` goes in `x-atrium::page-header`'s `actions` slot and `x-atrium::screen-tabs` under the header. With a registered tab open, `x-atrium::screen-tab-panel` replaces the page body and only `header.actions` stays. Otherwise `before` comes first in the body and `after` last. `row.actions` goes in the trailing cell of each value row; that cell (and its empty header cell) is present for every viewer, with the existing manage controls still shown only when `$manages`. A feature test asserts all slots on the overview tab, and only `header.actions` plus the panel with a registered tab open.
7. **Nothing registered, nothing changes.** With nothing registered, no tab bar and no extra markup render, and the existing suites pass. Existing tests may change only where this spec forces it (criterion 8); each changed or deleted test is listed in the PR by file and test name.
8. **Audit trail and History nav item removed.** `<x-atrium::audit-trail source="pennantplus" />` leaves `resources/views/atrium/index.blade.php`, and `PennantPlugin::navigation()` no longer calls `historyNavItem()`. A feature test asserts no file under `resources/views` contains `x-atrium::audit` and no navigation item's URL points at `history`. Forced test changes: `tests/Feature/AtriumAuditTrailTest.php` and `tests/Feature/HistoryNavigationTest.php` are deleted. The history route and MCP tool (`tests/Feature/HistoryTest.php`) stay.
9. **Test isolation.** `tests/TestCase.php` uses `RefactorCircus\Atrium\Testing\ResetsScreenExtensions`.
10. **Docs.** The README (line 170) and the Boost skill (`resources/boost/skills/pennantplus-development/SKILL.md:120`) drop the audit-trail text, name the `pennantplus.features` screen and its slots, and say that history comes from Keen. Done-step: a grep for `audit-trail` and `historyNavItem` in `src`, `resources`, `docs`, README and CLAUDE.md is empty and listed in the PR.
11. **Gate.** `composer test` passes, including `StylesTest`. PennantPlus has no browser suite.

## Design

- **One screen.** The shape named two: `pennant` and `pennant/scopes`. But `pennant/scopes` (`FeatureFlagController::scopes`) returns JSON for the scope picker, and Atrium excludes JSON routes from screens (Atrium criterion 19). So only `pennantplus.features` is declared.
- **`Atrium\Data\FeatureValueRow`**, per criterion 2. The controller passes `rows` (the current page's values mapped through `FeatureValueRow::for`) next to `values`, so pagination stays on `values`.
- History comes from Keen's wildcard registration (default slot `after`). PennantPlus declares no audit subject and registers nothing into other screens (D-001).
- No config, routes, migrations, events or MCP changes. Atrium stays in `suggest` and `require-dev`.

## Out of scope

- Moving the guarded Atrium references in `PennantPlusServiceProvider` (`Gatekeeper`, `PluginRegistry` for the feature resolver and `@pennantplusManages`) into `src/Atrium`, and an arch rule banning Atrium classes outside `src/Atrium`. Those references are already guarded by `class_exists`; moving them is a separate change.
- Keen's History section and nav items.

## Risks and open questions

- **Merge grouping.** The removals are forced by Atrium PR #16, so this merges with Atrium and Keen as a group.
- **Q1: the `FeatureValueRow` stand-in and its arch exclusion.** It is settled here by Atrium's precedent (D-033), but it needs an exclusion in an existing arch rule. Options: (a) the stand-in plus a named exclusion (recommended, so the list screen offers the standard row slot); (b) declare the screen without rendering `row.actions`, with no stand-in.
- **Q2: arch rule for Atrium references.** Showroom and Polycart gain "no Atrium class outside src/Atrium". PennantPlus would fail it today because of `PennantPlusServiceProvider`. Options: (a) leave it out of this spec (recommended, small spec); (b) move both registrations into an `Atrium\AtriumIntegration` class and add the rule here.
