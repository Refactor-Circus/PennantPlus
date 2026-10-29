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

## Addendum (2026-10-29): every screen declares slots and tab bar

User decision (Atrium D-013): every Atrium screen in this package declares itself with `Screen::make("{package}.{screen}")`, renders the Atrium tab bar (only visible once at least 2 tabs are visible), and declares the standard slots: `header.actions`, `before` and `after` on all screens; `aside` and `history` (when audited) on detail screens; the `row.actions` row slot on list screens. Screens without built-in tabs get a default `overview` tab. With nothing registered, every existing feature and browser test passes unchanged. A feature test per screen registers a tab and a section in each standard slot and asserts that they render.
PennantPlus screens: the feature flags screen (`pennant`) and scopes (`pennant/scopes`).
