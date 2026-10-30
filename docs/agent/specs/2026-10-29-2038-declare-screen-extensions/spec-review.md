
## Round 1

Changes to spec.md, and why. Open questions were left undecided; recommended options remain the working assumption.

- Criteria 6 and 7: removed the "present for every viewer" wording, which described a state impossible after the 403. Stated who reaches the page. Made the trailing cell's treatment depend on the open non-managers question, and named the cell as the only accepted markup change. Added an empty-registry feature test asserting no `screen-tabs`, `screen-tab-panel` or `section-*` testids.
- Criteria 1, 2, 3, 4, 9: added tests for the default overview ordering, pagination of `rows`, `?tab=unknown` fallback, throwing extensions (Atrium behaviour to be confirmed and recorded), class_exists guard; FeatureValueRow key collision, read-only persistence and listed assertions; arch exclusion by FQCN with final/no-table assertions and a standards-feedback check; before/after Gate, fail-closed row permission, null user; registry reset pair. Scope serialization scheme left to decision D3.
- Criteria 8 and 10: replaced the unsatisfiable `docs` grep with one limited to `src`, `resources`, README, CLAUDE.md and `docs/agent/product`; made behaviour tests (rendered HTML, `navigation()`) primary and the file scan secondary.
- Design and Out of scope: noted `Screen::standard` over `Screen::make` and the superseded audit-subject bullet; added the scope picker endpoint, Keen's wildcard config, changelog/version bump, and composer constraints to Out of scope.
- Risks: split merge order, older Atrium and failing extensions out as risks; moved Q1 and Q2 into "Decisions awaiting an answer" (D2, D4) beside new D1, D3, D5, keeping recommended options in the body.
