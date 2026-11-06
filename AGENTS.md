# AGENTS.md — Custom-Post-Type-Maker

<!-- graffino-agentic:begin v1 -->
## How we work here

**Before any work:** `git fetch --all --prune`, then `git pull --ff-only`. Stop on a
non-fast-forward or on uncommitted changes you did not make.

**Always load** (indexes; open the entry, or its topic file, before you rely on it):
`docs/agents/DECISIONS.md`, `docs/agents/PATTERNS.md`,
`docs/agents/CONTRACTS.md`, `docs/agents/OPEN-QUESTIONS.md`, `CONTEXT.md`.

**Load on demand:**

| When | Load |
|---|---|
| You rely on an entry | the entry, or its topic file under `docs/agents/<kind>/` |
| Structural work | `docs/agents/ARCHITECTURE.md` |
| Old code or a past decision needs explaining | `docs/agents/DECISIONS-SUPERSEDED.md`, `PATTERNS-SUPERSEDED.md` (never obeyed) |
| UI work | `docs/design/DESIGN.md`, `docs/design/registry.yaml` |
| Banners, icons or other brand images change | `docs/design/brand/` (the sources) and `docs/design/DESIGN.md` → Source |
| The wordpress.org listing changes (description, stable tag, tested up to, changelog) | `readme.txt` |

**Flow:** a change to more than a few lines, or one with a design choice, starts with
`/plan`. The approved plan runs with `/build`. Small fixes go straight to build, with
tests first where tests exist.

### The gate
Ask yourself: **does this change something already approved?**
- **Do, then report:** add a `candidate` pattern; add or answer an open question
  (only with an answer the user gave); write a `Proposed answer` the evidence
  supports (the question stays `investigating` until the user confirms it); fix a
  typo, link or moved path; code that follows the approved entries.
- **Propose and wait:** change, supersede or delete an `approved` pattern or
  decision; promote `candidate → approved`; add a decision; code that contradicts an
  entry. Use this shape, then stop:

```text
PROPOSAL: <add-decision | change | supersede | promote | delete>
- Existing: <ID and what it says, or "none">
- Proposed: <the new rule>
- Why now: <what changed>
- Impact: <files that follow the old rule>
- If approved: <old ID> → superseded-by <new ID> (kept, not deleted)
```
Before you propose, check `docs/agents/REJECTED.md`: never re-propose a rejected item
without new evidence. A rejected proposal is added there with the reason.

### Hard rules
- Build only from `approved` entries. Unanswered means out of scope: record a Q-,
  never invent a value, a requirement or a behaviour.
- Add nothing nobody asked for. Note it as a suggestion instead.
- Build the smallest change that meets the approved criteria, reusing existing code.
  A new abstraction, dependency, configuration option, extension point or broad
  refactor needs a concrete reason today. Never at the cost of tests or error handling.
- Tests first where the repo has tests. Never change what an existing test asserts
  to make code pass; an assertion you think is wrong goes to the user.
- Never write a secret, token, password or personal data into a file, commit or log.
- Never push, tag, release, or deploy without the user's go for that action.
- When you cite past work, label the intent: **replicate** (only for an `approved`
  pattern) or **for awareness only**. History is not a template.
- The code wins over any document. When they disagree, say so and fix the document.
- Report honestly: a failed check is reported with its output; a skipped step is
  named with its reason.

### Ledger rules
- IDs are one sequence per kind in this repo: `D-`, `PATTERN-`, `C-`, `Q-`. Never renumber.
- A provided contract (C-) lives here only; consumers link to it. Changing one is a
  PROPOSAL, and the plan names the consumer files too.
- Superseded entries stay (`status: superseded-by <ID>`) so old code stays explainable.
  Their index lines live in `DECISIONS-SUPERSEDED.md` / `PATTERNS-SUPERSEDED.md`, not
  in the always-loaded indexes; the lint (`--fix`) moves them.
- After any ledger change: `python3 docs/agents/tools/ledger-lint.py --fix` (next free
  ID: `--next D`). It must pass.
- **Confirmed:** when the user confirms an entry in a plan, set `- **Confirmed:** <date>`
  on it. Any edit to an entry removes its Confirmed line.
- A new reusable practice becomes a `candidate` pattern in the same session.
- A settled constraint becomes a decision PROPOSAL in the same session.
<!-- graffino-agentic:end -->

## 1. What this repo is
Custom Post Type Maker is a WordPress plugin by Graffino. Site admins use it to
create custom post types and custom taxonomies from wp-admin, with no code. It is
published on wordpress.org (slug `custom-post-type-maker`, 6,000+ active installs).
Git on GitHub is the source; the wordpress.org SVN repository is the release channel.

## 2. Stack and commands
PHP WordPress plugin, one main file (`class-cptm.php`), plain CSS and jQuery. No build step, no Composer, no npm.

```bash
# Lint (WordPress-Core standard). Not run: phpcs and php are not installed here.
phpcs --standard=phpcs.xml class-cptm.php

# PHP syntax check. Not run: php is not installed here.
php -l class-cptm.php

# Smoke tests in a fresh Playground (D-011). Run 2026-11-03: PASS (72 + 6 + CLI).
tests/run.sh

# Build dist/ (no dev files) and run Plugin Check on it. Run 2026-11-03: 0 errors, 0 warnings.
tests/plugin-check.sh

# Local WordPress + screenshots (Q-002). Admin login is automatic with --login.
npx @wp-playground/cli@latest server --port=9400 --wp=latest --login \
  --mount=.:/wordpress/wp-content/plugins/custom-post-type-maker

# Rebuild the translation template (WP-CLI inside Playground). Run 2026-11-03: OK.
# Blueprint step: wp i18n make-pot <plugin dir> <plugin dir>/lang/custom-post-type-maker.pot --exclude=.dev,docs,.claude,assets
npx @wp-playground/cli@latest run-blueprint --wp=latest --php=8.1 \
  --mount=.:/wordpress/wp-content/plugins/custom-post-type-maker --blueprint=<makepot.json>

# Screenshots of admin screens and brand PNGs (headless Chrome; Playground must run).
"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --headless=new --hide-scrollbars \
  --user-data-dir=<scratch profile> --force-device-scale-factor=1 --virtual-time-budget=8000 \
  --window-size=1280,1500 --screenshot=<out.png> "http://127.0.0.1:9400/wp-admin/<screen>"

# Read the release channel (read-only).
svn ls https://plugins.svn.wordpress.org/custom-post-type-maker/tags/
```

## 3. Layout
- `class-cptm.php` — plugin header, class `Cptm`: hooks, admin menu, meta box forms, save handlers.
- `includes/` — `Cptm_Definition` (the definition mapper), `Cptm_Tools` (Tools page), `Cptm_CLI` (WP-CLI).
- `uninstall.php` — opt-in data removal.
- `tests/`, `bin/` — Playground smoke tests, Plugin Check, release build (never shipped).
- `css/`, `js/` — admin styles and scripts for the overview lists and the add/edit screens.
- `lang/` — translation template `custom-post-type-maker.pot`.
- `assets/` — wordpress.org banners, icons and screenshots (go to SVN `assets/`, not trunk).
- `readme.txt` — the wordpress.org readme (stable tag, tested-up-to, changelog).
- `README.md`, `CHANGELOG.md` — GitHub readme and changelog.
- `docs/agents/`, `docs/design/` — the agent ledger and design guardrails.

## 4. Rules for this repo
- Text domain is `custom-post-type-maker` in every translatable string.
- A version change touches five places (PATTERN-002).
- SVN commits publish to every site that runs the plugin. Never run `svn commit` without Zeno's go.

## 5. Release
Git-flow: work on `develop`; `release/vX.Y.Z` branch; merge to `master`; tag `vX.Y.Z`;
merge the tag back into `develop`. Then run `tests/run.sh` and `tests/plugin-check.sh`, and copy `dist/custom-post-type-maker/` (from `bin/build.sh`) to SVN `trunk/` in the working copy
`/Users/Zeno/Work/Graffino/WordPress/wordpress.org/Custom-Post-Type-Maker`, `svn cp trunk tags/X.Y.Z`,
and `svn commit` (PATTERN-001; needs the wordpress.org SVN password, typed by Zeno). Banners, icons, screenshots and `blueprints/blueprint.json` go to SVN `assets/`.
Zeno says go for each tag, push and SVN commit.

## 6. Definition of done
- `tests/run.sh` passes (includes the C-001 check of data saved by 1.2.0).
- `tests/plugin-check.sh` shows 0 errors.
- A new behaviour gets a smoke test in `tests/smoke/run.php`.
- Screenshots of every changed admin screen, compared with `docs/design/DESIGN.md`.
- New practices recorded as candidate patterns; open points recorded as Q- entries.
