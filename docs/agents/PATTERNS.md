# Patterns — Custom-Post-Type-Maker

<!-- graffino-agentic:begin v1 -->
> How we build things here. Copy only `approved` entries. `candidate` = seen once or
> not yet confirmed; agents add these freely. Promotion needs the user's OK.
<!-- graffino-agentic:end -->

## Index
- PATTERN-001 · Git-flow release, then a mirror to wordpress.org SVN · approved
- PATTERN-002 · A release bumps the version in five places · approved
- PATTERN-003 · Enqueue admin assets only on the plugin's own screens · approved
- PATTERN-004 · Flush rewrite rules through an option flag · approved
- PATTERN-005 · Read definition meta with array_key_exists and a default · approved
- PATTERN-006 · Conventional commit subjects · approved
- PATTERN-007 · A new definition setting is optional and passed only when set · approved
- PATTERN-008 · Build meta box rows with the row helpers · candidate
- PATTERN-009 · Read and write definitions only through Cptm_Definition · candidate

---

## PATTERN-001 · Git-flow release, then a mirror to wordpress.org SVN
- **Status:** approved
- **Use when:** you release a new version.
- **How:**
  1. Work on `develop`.
  2. Make the branch `release/vX.Y.Z`. Merge it to `master` and tag `vX.Y.Z`.
  3. Merge the tag back into `develop`.
  4. Copy the shipped files to SVN `trunk/`. Copy trunk to `tags/X.Y.Z` (no "v").
  5. Commit to SVN with the same message as the Git commit. Zeno types the SVN password.
- **Example:** commits 213a0c1 and dfdd9cc (v1.2.0); SVN r2754584
- **Why:** wordpress.org serves the version that `Stable tag` names in `tags/`.
- **Source:** git · confidence high

## PATTERN-002 · A release bumps the version in five places
- **Status:** approved
- **Use when:** you change the version number.
- **How:** Change all five: plugin header `Version:` (class-cptm.php:7), the `@version` docblock (:34), `$this->version` (:64), `readme.txt` `Stable tag:` and its changelog, `CHANGELOG.md`.
- **Example:** commit f733bd2 (1.1.14 → 1.1.15)
- **Why:** Release 1.2.0 missed the `@version` docblock, which still says 1.1.15.
- **Source:** git · confidence high

## PATTERN-003 · Enqueue admin assets only on the plugin's own screens
- **Status:** approved
- **Use when:** you add a style or a script.
- **How:** In `cptm_styles()`, check `$hook` and the `cptm` / `cptm_tax` post type before you enqueue.
- **Example:** class-cptm.php:184
- **Why:** inferred — other admin screens must not load the plugin's CSS and JS.
- **Source:** code · confidence high

## PATTERN-004 · Flush rewrite rules through an option flag
- **Status:** approved
- **Use when:** a change alters the registered types or their rewrite rules.
- **How:** Set the option `cptm_plugin_settings_changed` to true. The next `admin_init` flushes and resets the flag. Never flush on every request.
- **Example:** class-cptm.php:1190, class-cptm.php:1196-1201
- **Why:** Commit db5575d added the auto-flush on save, activation and deactivation.
- **Source:** code, git · confidence high

## PATTERN-005 · Read definition meta with array_key_exists and a default
- **Status:** approved
- **Use when:** you read a definition setting from post meta.
- **How:** `array_key_exists( 'key', $meta ) && $meta['key'][0] == '1' ? true : false`, or the same shape with a default value. A new setting needs a default that keeps old definitions working (C-001).
- **Example:** class-cptm.php:252-274
- **Why:** Old definitions do not have keys that later versions added.
- **Source:** code · confidence high

## PATTERN-006 · Conventional commit subjects
- **Status:** approved
- **Use when:** you write a commit message.
- **How:** `feat:`, `fix:` or `chore:` prefix and a short subject.
- **Example:** commits fa591e9, f733bd2, 2a4e6ef
- **Why:** All commits since 2021 use this shape.
- **Source:** git · confidence medium

## PATTERN-007 · A new definition setting is optional and passed only when set
- **Status:** approved
- **Use when:** you add a register_post_type or register_taxonomy argument to the forms.
- **How:** Store '1', '0' or '' (or text, '' when empty). Show "WordPress default" as the first option (`optional_bool_row()`). In the register code, pass the argument only when the value is not empty (`meta_optional_flag()`).
- **Example:** class-cptm.php `register_post_type_definition()` (the "Settings added in 1.3.0" loops)
- **Why:** Definitions saved by older versions do not have the key; they must keep the WordPress default (C-001).
- **Source:** code, user · confidence high
- **Confirmed:** 2026-11-03

## PATTERN-008 · Build meta box rows with the row helpers
- **Status:** candidate
- **Use when:** you add a field to the Options meta box.
- **How:** Call `text_row()`, `bool_row()`, `optional_bool_row()` or `checkbox_row()`. They escape all output and keep the `tabindex` order.
- **Example:** class-cptm.php `cptm_meta_box()`
- **Why:** The 1.2.0 forms repeated the markup per field, and several fields printed values without escaping.
- **Source:** code · confidence medium

## PATTERN-009 · Read and write definitions only through Cptm_Definition
- **Status:** candidate
- **Use when:** any code reads, writes, imports, exports or registers a definition.
- **How:** Read with `post_type_registration()` / `taxonomy_registration()` / `flat()`. Clean any input with `sanitize()`, then write with `save()`. Add a new key to `Cptm_Definition::KEYS` first; the form, import, export and code export then pick it up.
- **Example:** includes/class-cptm-definition.php; class-cptm.php `save_definition()`; includes/class-cptm-tools.php `handle_import()`
- **Why:** The form, the JSON import, the Custom Post Type UI import and WP-CLI must apply the same rules (C-001, D-010).
- **Source:** code · confidence medium
