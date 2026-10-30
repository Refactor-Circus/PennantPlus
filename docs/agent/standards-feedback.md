# Standards Feedback

Every time work has to break a standard, add a row. `/ring-master-discover-standards` reads this log, so changes to the shared standards are based on evidence.

| Date | Spec | Standard | Deviation | Why |
| --- | --- | --- | --- | --- |
| 2026-10-30 | 2026-10-29-2038-declare-screen-extensions | models/lifecycle-events, testing/arch-tests | `RefactorCircus\PennantPlus\Atrium\Data\FeatureValueRow` extends Eloquent's `Model` but is excluded by name from `tests/ArchTest.php`'s `every model fires an event for every lifecycle hook`, and is not a `{Entity}Model` in `Models/` with a table and policy. A separate arch rule asserts it is final and declares no table. | Atrium's `row.actions` row slot takes a `Model` per row, and a stored Pennant value is not one. The stand-in (after Atrium's `SettingsPanelRow`) is unsaved and read-only (`save()`, `fresh()`, `refresh()` and `delete()` throw), so it has no lifecycle to announce (D-036, D-040, D-042). |
