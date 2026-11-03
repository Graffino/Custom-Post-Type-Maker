# Contracts — Stored data

> Provided contracts. Index: `docs/agents/CONTRACTS.md`.

### C-001 · Stored definition data (post meta on cptm / cptm_tax)
- **Status:** approved
- **Interface:** post meta on posts of type `cptm` and `cptm_tax` in each site's database.
- **Consumers:** about 6,000 sites that run the plugin. Every new version must read the data that older versions wrote.
- **Shape:**
  - `cptm` text: `cptm_name` (lowercase, no spaces), `cptm_label`, `cptm_singular_name`, `cptm_description` (stored HTML-encoded), `cptm_icon_url`, `cptm_icon_slug` (Dashicons class), `cptm_custom_rewrite_slug`, `cptm_menu_position` (integer as a string).
  - `cptm` '1'/'0' flags: `cptm_public`, `cptm_show_ui`, `cptm_has_archive`, `cptm_exclude_from_search`, `cptm_hierarchical`, `cptm_rewrite`, `cptm_withfront`, `cptm_feeds`, `cptm_pages`, `cptm_query_var`, `cptm_show_in_rest`, `cptm_publicly_queryable`, `cptm_show_in_menu`. A missing flag means false, except `cptm_publicly_queryable` (D-002).
  - `cptm_capability_type`: 'post' or 'page'; missing means 'post'.
  - `cptm` serialized string arrays: `cptm_supports` (missing means title + editor), `cptm_builtin_taxonomies` ('category', 'post_tag').
  - `cptm_tax` text: `cptm_tax_name`, `cptm_tax_label`, `cptm_tax_singular_name`, `cptm_tax_custom_rewrite_slug`.
  - `cptm_tax` '1'/'0' flags: `cptm_tax_show_ui`, `cptm_tax_hierarchical`, `cptm_tax_rewrite`, `cptm_tax_query_var`, `cptm_tax_show_in_rest`, `cptm_tax_show_admin_column`.
  - `cptm_tax` serialized string array: `cptm_tax_post_types`.
  - Added in 1.3.0, values '1', '0' or '' (''= WordPress default; the argument is not passed): `cptm_show_in_nav_menus`, `cptm_show_in_admin_bar`, `cptm_can_export`, `cptm_delete_with_user`, `cptm_tax_public`, `cptm_tax_publicly_queryable`, `cptm_tax_show_in_nav_menus`, `cptm_tax_show_tagcloud`, `cptm_tax_show_in_quick_edit`.
  - Added in 1.3.0, text ('' = not passed): `cptm_archive_slug`, `cptm_rest_base`, `cptm_rest_namespace`, `cptm_tax_rest_base`, `cptm_tax_default_term`.
  - Added in 2.0.0: `cptm_embeddable`, `cptm_tax_sort` ('1', '0' or ''); `cptm_template` (JSON list of blocks, '' = none); `cptm_template_lock` ('', 'all', 'insert', 'contentOnly').
  - From 2.0.0, `cptm_capability_type` may also hold a custom name (sanitize_key, at most 20 characters); it registers as `array( name, name + 's' )` with `map_meta_cap` true (D-012).
  - Only definitions with `post_status` publish are registered. The fallback name 'no_name' is never registered.
- **Rules:** Never rename or remove a key. Never change the meaning of a stored value. A new key needs a default that keeps old definitions unchanged (PATTERN-005).
- **Evidence:** includes/class-cptm-definition.php `post_type_registration()`, `taxonomy_registration()` (read); `sanitize()`, `save()` (write). tests/smoke/run.php section 1 checks the 1.2.0 format. 1.2.0 read code: commit dfdd9cc, class-cptm.php:227-292.
- **Source:** code · confidence high

### C-006 · Option cptm_plugin_settings_changed
- **Status:** approved
- **Interface:** a boolean option in `wp_options`.
- **Consumers:** the plugin itself across requests (PATTERN-004).
- **Shape:** true after a definition save; false after the flush.
- **Rules:** Keep the name, so a flag set before an update still triggers the flush.
- **Evidence:** class-cptm.php:1190, class-cptm.php:1197-1199
- **Source:** code · confidence high

### C-010 · Options cptm_granted_caps and cptm_delete_data_on_uninstall
- **Status:** approved
- **Interface:** options in `wp_options` (not autoloaded).
- **Consumers:** `uninstall.php`.
- **Shape:** `cptm_granted_caps`: list of capability names added to the Administrator role (D-012). `cptm_delete_data_on_uninstall`: bool, set on the Tools page.
- **Rules:** Uninstall removes data only when `cptm_delete_data_on_uninstall` is true, and never deletes content posts or terms.
- **Evidence:** includes/class-cptm-definition.php `grant_capabilities()`, includes/class-cptm-tools.php `handle_settings()`, uninstall.php; tests/smoke/uninstall.php
- **Source:** code · confidence high
