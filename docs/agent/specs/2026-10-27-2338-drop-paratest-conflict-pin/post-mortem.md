# Post-mortem: Drop the paratest conflict pin and require Pest ^5.3.4

## Summary

The change was two lines in `composer.json`: delete the `conflict` block and raise `pestphp/pest` to `^5.3.4`. The factory run (wf_cdd95f44-3bc) built that edit in group 1 (tasks 1.1 and 1.2) and then stopped at 1.3 with `needs-decision`. No review round ran. The spec said Cortex had already merged its part of the campaign, and that was false. Cortex `main` (259c86c) still declared `"conflict": {"brianium/paratest": ">=7.26.1"}`, so Pest 5.3.4 (needs paratest ^7.27.4) could not resolve next to `refactor-circus/cortex dev-main`. The Cortex job was waiting on PennantPlus (its D-006), so each job waited on the other. A person broke the cycle (D-004) by building both changes by hand: PennantPlus PR #7 and Cortex PR #14. PR #7 is merged (4886869).

## Metrics

- Rounds: 0 review rounds. One spec-review round (`spec-review.md`, Round 1).
- Decisions: 4 (D-001 ticket, D-002 spec approval, D-003 and D-004 wait cycle).
- Escalations: 1 (Q-002, from the build stage).
- Deviations: 0 in code (`build.md`).
- Findings: none, because no code review ran.
- Outcome: the factory did not finish. A human finished the job outside the factory.

## What went well

- The spec review caught and fixed real problems before the build. It corrected the claim that `composer.lock` is committed (it is gitignored, `.gitignore` line 6), made AC 4 and 5 name concrete commands, and fixed the AC 6 contradiction with the new spec folder.
- The build kept to scope. `git diff` showed only the two `composer.json` hunks, and `composer validate` passed.
- It failed fast and reported the exact resolver conflict (`refactor-circus/cortex dev-main conflicts with brianium/paratest v7.27.4`) with the cause traced to Cortex 259c86c. It did not work around it or touch Cortex.
- It checked its preconditions: 1.1 confirmed the old values and 1.2 confirmed the new ones.

## What cost rounds

- The run ended at the build stage with a cross-package deadlock and cost one human decision (D-003) that was superseded by D-004 within the same day.
- D-003 (hold until Cortex main drops its conflict) could not work, because Cortex was holding for PennantPlus. The cycle was visible at escalation time, but the factory offered no way to build two jobs together.
- The Cortex change was a spec with a `questions.yml` and no branch or PR, so the "already merged" claim could not have been true.

## Root causes

1. **spec_gap**: The spec's Risks section says "Atrium and Cortex (required in dev) already merged their part of the campaign." It was never checked against Cortex `main`. Only Atrium and Keystone had merged. The spec review (Round 1) verified the lockfile claim but not this one. Evidence: `spec.md` Risks; `build.md` 1.3.
2. **spec_gap**: Acceptance criteria 4 and 5 depend on a sibling package's unmerged state, so they can't be met alone. The spec named no dependency on the Cortex job, and the campaign order was not recorded. Evidence: `decision.md` reasons; `decisions.md` D-003/D-004.
3. **tooling**: Mutual dev-main dependencies between campaign packages make a deadlock possible, and the factory has no way to run two jobs as one. The workaround was manual. Evidence: D-004 ("Both changes were made by hand in one go").
4. **spec_gap**: AC 4 uses `composer update`, which refuses without a lock. The worktree had only `composer.local.lock` from `composer local:install`. This did not decide the outcome here, but the commands would have needed a change even without Cortex. Evidence: `build.md` 1.3.

No implementer_error or reviewer_noise was found.

## What human review caught

No PR review comments or ticket comments from reviewers exist. The only human input was decisions in RingMaster: D-002 (approve as written, no note), D-003, and D-004, which resolved the deadlock by building both PRs by hand. The human review delta is therefore about the process gap, not code.

## Proposed follow-ups

- Add a spec-writing and spec-review check: every claim that another repo or PR "already merged" must be verified against that repo's `origin/main` (grep for the relevant key) and cited.
- For campaign specs, record the dependency order and cross-package preconditions (here, Cortex before PennantPlus) in the spec and in the RingMaster task, so a cycle shows up at planning rather than at build.
- Add a factory feature for linked jobs that build and verify together, with merge order stated, to replace the manual D-004 route.
- Make the local-check ACs work with the worktree setup (`composer local:update`, `composer.local.lock`), or have the build stage create a lock.
