# AGENTS.md — Custom-Post-Type-Maker

<!-- graffino-agentic:begin v1 -->
## How we work here

**Before any work:** `git fetch --all --prune`, then `git pull --ff-only`. Stop on a
non-fast-forward or on uncommitted changes you did not make.

**Always load** (indexes; open the entry, or its topic file, before you rely on it):
`docs/agents/DECISIONS.md`, `docs/agents/PATTERNS.md`,
`docs/agents/CONTRACTS.md`, `docs/agents/OPEN-QUESTIONS.md`, `CONTEXT.md`. Load
`docs/agents/ARCHITECTURE.md` before structural work. UI work also loads
`docs/design/DESIGN.md` and `docs/design/registry.yaml`.

**Flow:** a change to more than a few lines, or one with a design choice, starts with
`/plan`. The approved plan runs with `/build`. Small fixes go straight to build, with
tests first where tests exist.

### The gate
Ask yourself: **does this change something already approved?**
- **Do, then report:** add a `candidate` pattern; add or answer an open question
  (only with an answer the user gave); fix a typo, link or moved path; code that
  follows the approved entries.
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

# Tests: none. The repo has no test suite (see Q-001).

# Local WordPress + screenshots (Q-002). Admin login is automatic with --login.
npx @wp-playground/cli@latest server --port=9400 --wp=latest --login \
  --mount=.:/wordpress/wp-content/plugins/custom-post-type-maker

# Rebuild the translation template (WP-CLI inside Playground). Run 2026-11-03: OK.
# Blueprint step: wp i18n make-pot <plugin dir> <plugin dir>/lang/custom-post-type-maker.pot --exclude=.dev,docs,.claude,assets
npx @wp-playground/cli@latest run-blueprint --wp=latest --php=8.1 \
  --mount=.:/wordpress/wp-content/plugins/custom-post-type-maker --blueprint=<makepot.json>

# Read the release channel (read-only).
svn ls https://plugins.svn.wordpress.org/custom-post-type-maker/tags/
```

## 3. Layout
- `class-cptm.php` — the whole plugin: plugin header, class `Cptm`, all hooks and admin UI.
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
merge the tag back into `develop`. Then copy the shipped files to SVN `trunk/` in the working copy
`/Users/Zeno/Work/Graffino/WordPress/wordpress.org/Custom-Post-Type-Maker`, `svn cp trunk tags/X.Y.Z`,
and `svn commit` (PATTERN-001; needs the wordpress.org SVN password, typed by Zeno). Banners and icons go to SVN `assets/`.
Zeno says go for each tag, push and SVN commit.

## 6. Definition of done
- `php -l class-cptm.php` passes (on a machine with PHP).
- `phpcs --standard=phpcs.xml` shows no new errors in changed lines.
- The plugin activates on the current WordPress release with `WP_DEBUG` on and logs no notices.
- Existing post types and taxonomies saved by older versions still register (C-001).
- Screenshots of every changed admin screen, compared with `docs/design/DESIGN.md`.
- New practices recorded as candidate patterns; open points recorded as Q- entries.
