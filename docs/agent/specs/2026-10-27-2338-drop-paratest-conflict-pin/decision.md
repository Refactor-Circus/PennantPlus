# Factory decision

- Run: wf_cdd95f44-3bc (2026-10-28)
- Status: needs-decision (stage: build)

## Reasons

- Group 1 tasks 1.1 and 1.2 are done. The `composer.json` edit (drop `conflict`, Pest `^5.3.4`) is in the job worktree, uncommitted, and `composer validate` passes.
- 1.3 is blocked: `pestphp/pest v5.3.4 requires brianium/paratest ^7.27.4`, and `refactor-circus/cortex dev-main conflicts with brianium/paratest v7.27.4`. Cortex origin main (259c86c) still has `"conflict": {"brianium/paratest": ">=7.26.1"}`.
- The Cortex job (2026-10-27-2251-drop-paratest-conflict-for-pest-5-3, D-006) is holding until PennantPlus drops its conflict, so each job is waiting on the other.

## Open questions

- Q-002: how to proceed given the wait cycle with Cortex (see questions.yml).

## Nits

None (no review round ran).
