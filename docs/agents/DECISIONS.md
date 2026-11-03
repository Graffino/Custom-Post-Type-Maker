# Decisions — Custom-Post-Type-Maker

<!-- graffino-agentic:begin v1 -->
> Constraints the code must obey. Status: `approved` (obey) · `proposed` (do not
> build on it) · `superseded-by <ID>` (kept to explain old code).
> Adding or changing an entry needs the user's OK (AGENTS.md → The gate).
<!-- graffino-agentic:end -->

## Index
- D-001 · Definitions are stored as posts of the private post types cptm and cptm_tax
- D-002 · A missing cptm_publicly_queryable key means true
- D-003 · Definition names are lowercased and spaces are removed on save
- D-004 · A Dashicon slug overrides an uploaded icon
- D-005 · rewrite.with_front is always set, also when false
- D-006 · Translations come from translate.wordpress.org; the repo ships only the .pot
- D-007 · The plugin requires PHP 8.1 or later
- D-008 · Only users with manage_options may manage definitions
- D-009 · The plugin requires WordPress 6.5 or later

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
- **Status:** approved
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
