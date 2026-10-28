# Drop the paratest conflict pin and require Pest ^5.3.4

Ticket: [#6](https://github.com/Refactor-Circus/PennantPlus/issues/6)

## Goal
Remove the temporary `brianium/paratest` conflict so the package resolves the current Pest and paratest releases, matching Keystone PR #8 (campaign #1).

## Acceptance criteria
1. `composer.json` has no `conflict` key.
2. `composer.json` `require-dev` has `"pestphp/pest": "^5.3.4"`.
3. No other `composer.json` entry changes.
4. Local check: after `composer update pestphp/pest brianium/paratest --with-dependencies`, `composer show brianium/paratest` reports 7.27.4 or newer and `composer show pestphp/pest` reports 5.3.4 or newer. The local `composer.lock` diff touches only pestphp/pest, brianium/paratest and their transitive dependencies.
5. `composer test` passes from that resolved install, with Atrium and Cortex at their `dev-main` constraints from `require-dev`. It runs `@analyse`, `@lint:check`, `@test:types` and `@test:unit` (`vendor/bin/pest --parallel`).
6. `git diff --name-only` against main lists only `composer.json`, plus the spec folder under `docs/agent/specs/`. `composer.lock` is gitignored and never committed.

## Design
Edit `composer.json` only: delete the `conflict` block, raise the Pest constraint. `composer.lock` is gitignored (`.gitignore` line 6) and not committed; refresh the local copy only as criterion 4 describes, and do not stage it. CI resolves fresh with no lock.

## Out of scope
Any other dependency, code or documentation change.

## Risks and open questions
None open. The change is dev-only (`require-dev`), so it has no consumer impact. The `^5.3.4` Pest floor (which needs paratest ^7.27.4) replaces the conflict as the guard against a broken paratest. Atrium and Cortex (required in dev) already merged their part of the campaign.
