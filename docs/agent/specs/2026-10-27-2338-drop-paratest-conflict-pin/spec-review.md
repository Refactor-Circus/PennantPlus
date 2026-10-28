# Spec review

## Round 1
- Design: removed "refresh composer.lock if one is committed". composer.lock exists locally but is gitignored (`.gitignore` line 6, `git ls-files` empty), so it is never committed. Findings claiming it is committed were wrong; the gitignored reading was verified.
- AC 4: now names commands (`composer update ... --with-dependencies`, `composer show` for paratest and pest) and limits the local lock diff to pest, paratest and their transitive dependencies.
- AC 5: lists the `composer test` steps (analyse, lint:check, test:types, test:unit with `--parallel`) and the Atrium and Cortex `dev-main` constraints.
- AC 6: reworded to `git diff --name-only` listing only `composer.json` plus the spec folder; fixes the contradiction with the new spec folder.
- Risks: recorded dev-only, no consumer impact, the Pest floor replacing the conflict guard, and that CI resolves fresh with no lock.
- Open questions unchanged (none).
