---
name: build
description: Build an approved plan (or a small, clear fix) in this repo - tests first where tests exist, then code until they pass, then the definition-of-done checks and, for UI, screenshots compared with the design. Records new practices as candidate patterns. Use after /plan is approved, or when the user says "/build" or "build it".
---
<!-- graffino-agentic:begin v1 -->

# /build

You build exactly the approved plan. If the plan is wrong or impossible, stop and
report; do not improvise. Unrequested behaviour is a defect.

## 1. Branch
From the default branch (develop if present): `feat|fix|chore/<short-name>`. Commit
only on that branch. Never push, merge to a shared branch, tag or release without
the user's go.

**Parallel parts** only when they are truly independent (disjoint files, contracts
and behaviour): one `builder` agent per part with `isolation: "worktree"`, each on a
branch cut from yours. Parts that overlap go to one agent, in order; when in doubt,
one. Bring each part's commits onto your branch as soon as it returns (rebase or
cherry-pick, then fast-forward; history stays linear); a conflict in code or tests
goes back to a builder, then the affected tests run again. Remove a worktree only
after its commits are on your branch.

**Team addon** (AGENTS.md has the "Team workflow" block): work on the delivery's
integration branch: if it doesn't exist yet, push it, open the draft PR and comment
both links on every ticket it covers first. Parallel parts use private worker
branches that are never pushed (`/start` integrates them). Commit titles
`<ticket> - <type>(<scope>): …`, naming the ticket each commit implements. When a
test author wrote the tests, skip step 2. Under `/start`, return the handoff
(`.claude/agents/builder.md`) and leave the journal to the conductor; otherwise end
the session with the ticket journal. Never merge.

## 2. Tests first (repos with tests)
1. Write the tests for the plan's behaviours, in the repo's existing test style.
2. Run them. Each new test must **fail for the right reason** (the behaviour is
   missing), not because the setup is broken.
3. Commit: `test(<scope>): …`.

No test suite: list each behaviour as "checked by hand" with how you will check it.

## 3. Code
- Write code until the tests pass. **Never change what a test asserts.** Mechanical
  fixes (compile error, path, setup) are allowed and named in the report.
- Read the nearest existing example; match its idiom, naming and comment density.
- Follow the approved patterns the plan cites. Copy no `candidate`.
- **As simply as the plan allows.** The smallest change that meets the approved
  criteria, reusing existing code. A new abstraction, dependency, configuration
  option, extension point or broad refactor needs a concrete reason today, written
  in the report. Never save effort by weakening a test or dropping error handling.
- **Defects you meet.** One your own work introduced: fix it. An existing one that
  blocks an approved acceptance criterion, with clear expected behaviour and a small
  fix: write the failing reproduction first (team addon: the test author writes it),
  then fix it, and name cause, fix and check in the report. Anything else: don't fix
  it; name it in the report as an observation. Unspecified behaviour or conflicting
  requirements: stop and ask.

## 4. UI changes (visual apps)
1. A new or changed screen is built only from its **confirmed** mock in
   `docs/design/mocks/` (DESIGN.md → Procedure), never from the board or Figma. Not
   confirmed yet: stop and ask. Use only tokens from `docs/design/DESIGN.md`. A new color, size or font is a
   PROPOSAL, not a code change.
2. Cover the states: empty, loading, error, long text, many items; light and dark;
   the smallest and largest supported window/viewport; keyboard focus.
3. Take screenshots with the command in AGENTS.md section 2, light and dark, of every
   changed screen. Save them as `docs/design/screens/<DS-id>.<light|dark>.png`.
4. Compare with the previous screenshots and the design rules. Fix what differs.
   Add or update the `DS-` entry in `registry.yaml`.
5. Look at the screenshots yourself before you report. A screen nobody looked at is
   not done.

## 5. Verify (definition of done)
Run every check in AGENTS.md section 6. Report each with its result. A failure is
reported with its output; never call a failed or skipped check done.

## 6. Record
- A reusable practice you established → `candidate` entry in `docs/agents/PATTERNS.md`.
- A constraint the work settled → decision PROPOSAL to the user.
- A question that came up and stays open → `Q-` entry.
- Commit: `<type>(<scope>): <what and why>` with the session's co-author trailer.

## 7. Report
What changed (files), tests (added · passing · assertions changed: must be 0),
checks run and results, screenshots (UI), defects fixed on the way (with their
reproduction), deviations from the plan and why, new abstractions or dependencies
and their reason, candidate patterns and Q- entries added, observations, what
remains. Then ask whether to merge
the branch into the default branch.
<!-- graffino-agentic:end -->
