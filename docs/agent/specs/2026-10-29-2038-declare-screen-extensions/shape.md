# Declare screen extensions (PennantPlus) — Shaping Notes

## Scope

Move the PennantPlus feature flags screen onto Atrium's screen extensions registry (Atrium `2026-10-29-2037-screen-extensions-registry`).

- **Inline audit trail.** Remove `<x-atrium::audit-trail source="pennantplus" />` from `resources/views/atrium/index.blade.php:195`. Declare the `pennantplus.features` screen with a package-wide audited subject, so Keen's History section attaches.
- **Out of scope (section B):** `historyNavItem`. The Atrium feature resolver and `@pennantplusManages` already follow the add-on-extends-host direction.

## Decisions

- D-001: declarations only.

## Context

- **Depends on:** Atrium `screen-extensions-registry`. Pairs with Keen `history-section-by-capability`.
- **References:** see `references.md`.

## Standards

- architecture/dependencies: no inline checks for optional packages in views.
- atrium/components: `x-atrium::*` only, so `StylesTest` stays green.
- testing/test-layers: feature tests assert each screen declares its subject, and the existing suites stay green.
