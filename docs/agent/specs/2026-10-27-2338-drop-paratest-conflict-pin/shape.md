# Drop the paratest conflict pin and require Pest ^5.3.4 — Shaping Notes

## Scope

Tooling change in `composer.json` only (ticket #6):

- Remove `"brianium/paratest": ">=7.26.1"` from `conflict`, and the `conflict` key itself since it holds nothing else.
- Raise `require-dev` `pestphp/pest` from `^5.3.1` to `^5.3.4` (Pest 5.3.4 needs `brianium/paratest ^7.27.4`).
- Refresh `composer.lock` entries for pest and paratest if a lock is committed.

Out of scope: any other dependency or code change (campaign #1, same as Keystone PR #8).

## Decisions

- D-001: ticket created as drafted (#6).
- No source, test or doc changes: nothing in `src/` or `tests/` references paratest.

## Context

- `composer.json` line 32: `"pestphp/pest": "^5.3.1"`; lines 154-156: the `conflict` block.
- Acceptance: `composer update pestphp/pest brianium/paratest` resolves and `composer test` passes, including `--parallel`.
- No product docs are affected.

## Standards

- `architecture/dependencies`: the only standard touching Composer constraints; the change keeps `require`/`require-dev` free of anything optional or new.
