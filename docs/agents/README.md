# docs/agents — how this repo works with AI agents

<!-- graffino-agentic:begin v1 -->
> For people. One page. The agents read `AGENTS.md` at the repo root; this page
> tells you how to drive them and where things are.

## Day to day
| You want to | Do |
|---|---|
| Change something non-trivial | `/plan <what>` → read the plan and its **Knowledge used** table → approve, adjust or drop |
| Build the approved plan | `/build` (tests first, then code, then the checks; UI changes come with screenshots) |
| A small, clear fix | just ask; tests first where tests exist |
| Find out why something is the way it is | `DECISIONS.md` (index) → the entry → its evidence |
| Find how we build a kind of thing | `PATTERNS.md` — copy only `approved` entries |
| See what another system expects from us | `CONTRACTS.md` (provided, consumed, external) |
| Understand the structure | `ARCHITECTURE.md` |
| See what is still unknown | `OPEN-QUESTIONS.md` |
| UI work | `../design/DESIGN.md` (rules + procedure) and `../design/registry.yaml` (DS- IDs); a new screen is drawn in `../design/mocks/` and confirmed on the board first |
| Show a design to people | "publish the board" (one way: repo → board; edits on the board are lost) |
| Explain old code | `DECISIONS-SUPERSEDED.md`, `PATTERNS-SUPERSEDED.md` (never obeyed) |
| Re-check the ledger | `python3 docs/agents/tools/ledger-lint.py` (`--fix` rebuilds the indexes, `--next D` gives the next ID) |
| Update this setup | `/graffino-agentic-code-setup` (needs the skill installed) |
| Mine the code again | "backfill again" → review only the clashes in the page that opens |

## Your calls in a plan
Each knowledge entry a plan relies on gets a call: **confirm** (it holds; the entry
gets a `Confirmed:` date and is collapsed next time), **wrong** or **outdated** (the
agent writes a PROPOSAL to fix it), **answered** (for an open question).

## The gate
Agents add freely: candidate patterns, open questions, code that follows the
approved entries. They **propose and wait** before they change an approved decision,
pattern or contract, promote a candidate, or add a decision.

## Words
`CONTEXT.md` at the repo root holds the approved terms for this project.
<!-- graffino-agentic:end -->
