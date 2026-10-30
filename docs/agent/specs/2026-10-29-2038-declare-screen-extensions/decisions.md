# Decisions

Earlier D-ids are recorded in shape.md.

## D-034: Suite: proving the package runs without Atrium, and refusing an Atrium too old for the screen registry
- Choice: Prove it both ways: a WithoutAtrium suite inside `composer test`, and a CI lane with Atrium uninstalled. Every package that renders Atrium screens adds a composer `conflict` on refactor-circus/atrium versions older than the screen-extensions registry (Atrium PR #16).
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: user, cross-spec decision (2026-10-29)

## D-035: Suite: malformed integration config entries (screen maps, slots) at boot
- Choice: Throw InvalidArgumentException in local and testing. In production, report once, skip the entry and keep booting (same rule as Atrium D-022).
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: user, cross-spec decision (2026-10-29)

## D-036: How should the list screen offer the standard `row.actions` slot, given that a stored value is not a model?
- Choice: Add `FeatureValueRow` (unsaved Model stand-in, following Atrium's `SettingsPanelRow`) with a named exclusion in the model-events arch rule
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-037: Should this spec add an arch rule banning Atrium classes outside `src/Atrium`, and move the guarded registrations in `PennantPlusServiceProvider` into an `Atrium\AtriumIntegration` class?
- Choice: Leave it out of this spec (separate change)
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-038: When nothing is registered, should non-managers see the extra trailing row cell and header cell?
- Choice: Render the trailing cell only when `$manages` or when the `row.actions` slot has sections, so non-managers see unchanged markup
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-039: How should PennantPlus handle installs that have an Atrium older than the screen extensions registry (Atrium PR #16)?
- Choice: Bump the `require-dev` Atrium constraint to the registry release and add a `conflict` entry for older Atrium, so a mismatched install fails at `composer update`
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-040: Q1 from the spec: `FeatureValueRow` stand-in and its arch exclusion, or no row slot?
- Choice: The `FeatureValueRow` stand-in plus a named exclusion in the arch rule, with a standards-feedback row
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-041: Q2 from the spec: add the 'no Atrium class outside src/Atrium' arch rule in this change?
- Choice: Leave the rule and the `PennantPlusServiceProvider` move out of this spec
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-042: Q1: Should the row slot use a FeatureValueRow stand-in with a named arch-test exclusion, or should the screen skip row.actions?
- Choice: Add the FeatureValueRow stand-in and a named exclusion in the model-events arch rule
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)

## D-043: How should the scope be serialized in the FeatureValueRow key, which the unit test for for() needs?
- Choice: Reuse FeatureFlagManager's existing scope serialization (the serialized scope string already stored in Pennant's features table)
- Rationale: The user accepted the recommendation.
- Date: 2026-10-29
- Raised by: spec review; the user accepted all recommendations (2026-10-29)
