# Decisions — Custom-Post-Type-Maker

<!-- graffino-agentic:begin v1 -->
> Constraints the code must obey. Status: `approved` (obey) · `proposed` (do not
> build on it) · `superseded-by <ID>` (kept to explain old code).
> Adding or changing an entry needs the user's OK (AGENTS.md → The gate).
<!-- graffino-agentic:end -->

## Index
- D-001 · Definitions are stored as posts of the private post types cptm and cptm_tax
- D-002 · A missing cptm_publicly_queryable key means true
- D-003 · Definition names are lowercased and spaces are removed on save · _superseded-by D-010_
- D-004 · A Dashicon slug overrides an uploaded icon
- D-005 · rewrite.with_front is always set, also when false
- D-006 · Translations come from translate.wordpress.org; the repo ships only the .pot
- D-007 · The plugin requires PHP 8.1 or later
- D-008 · Only users with manage_options may manage definitions
- D-009 · The plugin requires WordPress 6.5 or later
- D-010 · Definition names are sanitized with sanitize_key and validated on save
- D-011 · Tests are Playground smoke tests, run locally; no CI for now
- D-012 · A custom capability type grants its capabilities to the Administrator role

---

## D-001 · Definitions are stored as posts of the private post types cptm and cptm_tax
- **Status:** approved
- **Date:** 2026-11-03
- **Rule:** Each post type definition is a `cptm` post and each taxonomy definition is a `cptm_tax` post, with its settings in post meta. On every `init`, the plugin reads all published definitions and registers them.
- **Why:** inferred — the plugin reuses the WordPress post list and edit screens as its admin UI.
- **Rejected:** none recorded.
- **Evidence:** class-cptm.php:67-68, class-cptm.php:112-160, class-cptm.php:210-449
- **Source:** code · confidence high

## D-002 · A missing cptm_publicly_queryable key means true
- **Status:** approved
- **Date:** 2026-11-03
- **Rule:** Read `cptm_publicly_queryable` as true when the key does not exist. Only a stored value other than '1' makes it false.
- **Why:** Commit db5575d: this default fixes permalink errors on sites that upgraded to 1.1.0.
- **Rejected:** none recorded.
- **Evidence:** class-cptm.php:265-272, commit db5575d
- **Source:** code, git · confidence high

## D-003 · Definition names are lowercased and spaces are removed on save
- **Status:** superseded-by D-010
- **Date:** 2026-11-03
- **Rule:** Save `cptm_name` and `cptm_tax_name` as `sanitize_text_field( strtolower( str_replace( ' ', '', … ) ) )`.
- **Why:** Commits 77ca7e0 ("Enforces lowercase custom post name") and f733bd2 ("Better sanitize taxonomy name").
- **Rejected:** none recorded.
- **Evidence:** class-cptm.php:1051, class-cptm.php:1147
- **Source:** code, git · confidence high

## D-004 · A Dashicon slug overrides an uploaded icon
- **Status:** approved
- **Date:** 2026-11-03
- **Rule:** When `cptm_icon_slug` is not empty, `menu_icon` is the slug. Otherwise `menu_icon` is `cptm_icon_url`.
- **Why:** Code comment "If DashIcon is set ignore uploaded".
- **Rejected:** none recorded.
- **Evidence:** class-cptm.php:241-246, class-cptm.php:495-500
- **Source:** code · confidence high

## D-005 · rewrite.with_front is always set, also when false
- **Status:** approved
- **Date:** 2026-11-03
- **Rule:** Always pass `rewrite['with_front']` from `cptm_withfront`. Add `slug`, `feeds` and `pages` only when they are true.
- **Why:** Commit ab9701f ("Correct with_front behaviour when set to false", GitHub issue #7).
- **Rejected:** none recorded.
- **Evidence:** class-cptm.php:280-292, commit ab9701f
- **Source:** code, git · confidence high

## D-006 · Translations come from translate.wordpress.org; the repo ships only the .pot
- **Status:** approved
- **Date:** 2026-11-03
- **Rule:** Do not add .po or .mo files to the repo. Keep `lang/custom-post-type-maker.pot` current.
- **Why:** Commit 01f294d ("Remove default langs in favor of WP polyglotes").
- **Rejected:** Bundled language files (removed in 01f294d).
- **Evidence:** lang/custom-post-type-maker.pot, commit 01f294d
- **Source:** git · confidence high

## D-007 · The plugin requires PHP 8.1 or later
- **Status:** approved
- **Date:** 2026-11-03
- **Rule:** Support PHP 8.1 and later only. Declare `Requires PHP: 8.1` in the plugin header and in `readme.txt`. Code may use PHP 8.1 features (typed properties, `match`, enums, `readonly`, `str_contains`).
- **Why:** Zeno's decision on 2026-11-03 for the next release.
- **Rejected:** Keep support for older PHP versions.
- **Evidence:** none yet in code; the next release adds the `Requires PHP` headers.
- **Source:** user · confidence high

## D-008 · Only users with manage_options may manage definitions
- **Status:** approved
- **Date:** 2026-11-03
- **Rule:** The screens, the save handler and the capabilities of `cptm` and `cptm_tax` all require `manage_options`.
- **Why:** Zeno's answer to Q-003 on 2026-11-03. A definition changes the site structure, so it is an admin task.
- **Rejected:** Editor access through `capability_type` 'page'.
- **Evidence:** class-cptm.php:173 (menu); the next release adds it to the post types and the save handler.
- **Source:** user · confidence high

## D-009 · The plugin requires WordPress 6.5 or later
- **Status:** approved
- **Date:** 2026-11-03
- **Rule:** Declare `Requires at least: 6.5` in the plugin header and in `readme.txt`. Code may use any WordPress API that 6.5 has.
- **Why:** Zeno's decision on 2026-11-03. It covers PHP 8.1 (D-007) and the labels and REST args of release 1.3.0.
- **Rejected:** 6.3 (the lowest version with full PHP 8.1 support); 7.0 (current majors only).
- **Evidence:** none yet in code; release 1.3.0 adds the headers.
- **Source:** user · confidence high

## D-010 · Definition names are sanitized with sanitize_key and validated on save
- **Status:** approved
- **Date:** 2026-11-03
- **Rule:** Save `cptm_name` and `cptm_tax_name` as `sanitize_key( str_replace( ' ', '', … ) )`. Reject an empty name, a name longer than 20 (post type) or 32 (taxonomy) characters, a reserved name, or a name that another post type or taxonomy already uses. On rejection, keep the old name and show an admin notice.
- **Why:** Release 1.3.0 plan, approved by Zeno on 2026-11-03. WordPress rejects names over these limits, and a reserved name breaks core queries.
- **Rejected:** The 1.2.0 rule (D-003), which accepted any characters and any length.
- **Evidence:** class-cptm.php `name_error()`, `save_name()`
- **Source:** user · confidence high

## D-011 · Tests are Playground smoke tests, run locally; no CI for now
- **Status:** approved
- **Date:** 2026-11-03
- **Rule:** Automated tests live in `tests/` and run in WordPress Playground with `tests/run.sh`. Plugin Check runs with `tests/plugin-check.sh` on the built release. Both must pass before a release. There is no CI workflow until Zeno picks a runner.
- **Why:** Zeno's answers to Q-001 and to the CI runner question on 2026-11-03. The plugin has no PHP toolchain on the dev machine; Playground needs only Node.js.
- **Rejected:** A GitHub Actions workflow now (runner not chosen); PHPUnit with wp-env (needs Docker).
- **Evidence:** tests/run.sh, tests/plugin-check.sh
- **Source:** user · confidence high

## D-012 · A custom capability type grants its capabilities to the Administrator role
- **Status:** approved
- **Date:** 2026-11-03
- **Rule:** When a post type definition uses a custom capability type, register it with `map_meta_cap` true and add the derived primitive capabilities to the Administrator role on save or import. Record them in the option `cptm_granted_caps`, so the opt-in uninstall can remove them.
- **Why:** Zeno's decision on 2026-11-03. Without the grant, nobody can edit the posts of that post type.
- **Rejected:** Grant nothing and rely on a role plugin (as Custom Post Type UI does).
- **Evidence:** includes/class-cptm-definition.php `grant_capabilities()`
- **Source:** user · confidence high
