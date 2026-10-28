# Decisions

## D-001: Create the GitHub issue as drafted?

- Question: Create the GitHub issue "Drop the paratest conflict pin and require Pest ^5.3.4" as drafted?
- Choice: Create the ticket as drafted
- Rationale: Same as #150 in Atrium (2026-10-28)
- Date: 2026-10-28 (answered in RingMaster)
- Raised by: ticket

## D-002: Approve the spec as written?

- Question: Approve the spec "Drop the paratest conflict pin and require Pest ^5.3.4" as written?
- Choice: Approve as written
- Rationale: Answered in RingMaster, no note
- Date: 2026-10-28 (answered in RingMaster)
- Raised by: approval

## D-003: How should this job proceed while Cortex main keeps its paratest conflict?

- Question: Cortex main still declares the brianium/paratest conflict, so PennantPlus can't resolve pestphp/pest ^5.3.4 next to refactor-circus/cortex dev-main. Cortex's own job is waiting on PennantPlus (its D-006), so each job waits on the other. How should this job proceed?
- Choice: Hold this job until Cortex main drops its conflict, then resume group 1 from 1.3
- Rationale: Answered in RingMaster, no note
- Date: 2026-10-28 (answered in RingMaster)
- Raised by: build

## D-004: Break the wait cycle with Cortex

- Question: Cortex and PennantPlus each require the other's dev-main, so neither pin-drop can go green until the other is merged. How should the cycle be broken?
- Choice: Build both now; merge one with CI red only from the other's pin, then re-run its CI once the other is merged
- Rationale: Supersedes D-003. Both changes were made by hand in one go and checked against each other (pest 5.3.4, paratest 7.27.4; suites pass with --parallel): PennantPlus PR #7, Cortex PR #14.
- Date: 2026-10-28 (answered in a session)
- Raised by: build
