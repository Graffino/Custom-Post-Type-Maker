---
name: plan
description: Plan a change in this repo before any edit - approach, files, tests, risks, and the full list of decisions, patterns and open questions the plan relies on, for the user to confirm. Use for any change beyond a few lines, any design choice, anything that touches an approved decision, or when the user says "/plan" or "plan this".
---
<!-- graffino-agentic:begin v1 -->

# /plan

You plan; you do not edit code. The output is one plan the user approves, adjusts or
drops. Nothing is built from an unapproved plan.

## 1. Load
1. `git fetch --all --prune`, `git pull --ff-only`; note the branch and any uncommitted work.
2. `AGENTS.md`, `CONTEXT.md`, `docs/agents/DECISIONS.md`, `PATTERNS.md`, `CONTRACTS.md`,
   `OPEN-QUESTIONS.md`; `ARCHITECTURE.md` for structural changes.
3. UI work: `docs/design/DESIGN.md`, `docs/design/registry.yaml`, the screenshots of
   the screens you will touch.
4. The code: find the **nearest existing example** of what you will build and read it
   properly. Reuse beats writing.

For a large or multi-part change, spawn the `planner` agent per independent part
(planning is read-only, so they can run in parallel) and merge their plans.

## 2. Check coverage
List what the request does not say. A gap that changes the result is a **blocking
question**: ask it now, all in one message. A gap the user cannot answer becomes a
`Q-` entry and that part is out of scope. Never fill a gap with a guess.

## 3. Write the plan

```text
# Plan — <title>

## Size
S | M | L — <what drives it>

## Approach
<2–4 sentences: what changes where, and why this way. The smallest change that meets
the criteria: name the existing code reused, and give a concrete reason for any new
abstraction, dependency, option or broad refactor. Alternatives rejected and why.>

## Files
| Action | Path | What |

## Tests
| # | Behaviour | Test (file · kind) | Not automated? why |

## Design (UI only)
<DS- IDs touched or added · mock confirmed, or to draw and confirm first (DESIGN.md) · tokens used · states covered: empty, loading, error, long text · light/dark>

## Sequencing
one builder | parallel: <part → files, only for disjoint files, contracts and behaviour>

## Risks
<what could go wrong, the fallback, what is hard to reverse>

## Out of scope
<what this does not include>

## Knowledge used
| Entry | What it says | Why it applies | Call |
|---|---|---|---|
| D-004 | … | … | confirm / wrong / outdated |
| PATTERN-007 | … | … | confirm / wrong / outdated |
| C-003 | … | this plan changes/relies on it; consumers: … | confirm / wrong / outdated |
| Q-002 | … | still open, so X is out of scope | confirm / answered |

### Considered, not applied
- <ID> — <why not>

### Assumptions (no entry behind them)
- <assumption> → becomes a Q- or a D- proposal if the user confirms it
```

A change on one side of a contract lists the other side's files too (on disk), or
says why none change.

Entries with a `Confirmed:` date collapse into one line ("9 entries confirmed before,
unchanged: D-002, D-004, …"); show only new, unconfirmed or flagged entries in full.
After the gate, write `Confirmed: <today>` on every entry the user confirmed, then
run the ledger lint.

The knowledge list must be complete: a plan built on a wrong rule is wrong even if
every step looks right.

## Team addon
If `AGENTS.md` has the "Team workflow" block: a ticket ID is required (ask if
missing), read the ticket, its parent and every comment first, and also write the
plan to `docs/agents/tasks/<ticket>/PLAN.md`. Record each knowledge call there.
- One plan per **delivery**: it may cover the ticket's dependent subtasks. Add under
  Size: `Repos · branches` (the integration branch) and `Tasks covered`
  (`<ticket> (Fixes | Part of)` each). Sequencing may also be `separate PR stacked
  on <ticket>`, only when a part is independently reviewable or releasable.
- Complete the **context brief** in `OVERVIEW.md`: where the code is, ownership (one
  builder, or worker → part), the commands that verify it.
- **Fast mode** (the `/start` fast lane): the planner reads the ticket itself and
  fills the whole context brief (`.claude/agents/planner.md`).

## 4. Gate
Show the plan. Ask the user to approve, adjust or drop it, and to call each
knowledge entry. A *wrong* or *outdated* call becomes a PROPOSAL (AGENTS.md → The
gate). An *answered* call updates the Q- entry. Then stop.
<!-- graffino-agentic:end -->
