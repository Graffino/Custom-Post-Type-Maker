# Contracts — Custom-Post-Type-Maker

<!-- graffino-agentic:begin v1 -->
> Promises across a boundary. **Provided:** this repo owns the contract; the text
> lives here only. **Consumed:** a link to the provider's entry plus the fields this
> repo relies on. **External:** what we rely on from a third-party service.
> Changing a provided contract is a PROPOSAL (AGENTS.md → The gate) and the plan
> lists the consumers' files too. Status: `approved` · `proposed` · `superseded-by`.
<!-- graffino-agentic:end -->

## Index
- C-001 · Stored definition data (post meta on cptm / cptm_tax) · provided
- C-002 · Plugin identity custom-post-type-maker/class-cptm.php · provided
- C-003 · Constant CPTM_DONT_GENERATE_ICON · provided
- C-004 · Image size cptm_icon and the wp_prepare_attachment_for_js filter · provided
- C-005 · Text domain custom-post-type-maker · provided
- C-006 · Option cptm_plugin_settings_changed · provided

---

## Provided

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
  - Only definitions with `post_status` publish are registered. The fallback name 'no_name' is never registered.
- **Rules:** Never rename or remove a key. Never change the meaning of a stored value. A new key needs a default that keeps old definitions unchanged (PATTERN-005).
- **Evidence:** class-cptm.php:227-292 (read), class-cptm.php:1049-1187 (write)
- **Source:** code · confidence high

### C-002 · Plugin identity custom-post-type-maker/class-cptm.php
- **Status:** approved
- **Interface:** the WordPress `active_plugins` option holds `custom-post-type-maker/class-cptm.php`.
- **Consumers:** every site that runs the plugin.
- **Shape:** folder `custom-post-type-maker`, main file `class-cptm.php`.
- **Rules:** Never rename the main file. A rename deactivates the plugin on update. The file was renamed twice before (2acc802, c8fb570).
- **Evidence:** class-cptm.php:1-11, commits 2acc802, c8fb570
- **Source:** code, git · confidence high

### C-003 · Constant CPTM_DONT_GENERATE_ICON
- **Status:** approved
- **Interface:** a PHP constant that a site owner defines in `wp-config.php`.
- **Consumers:** site owners with large image libraries.
- **Shape:** `defined( 'CPTM_DONT_GENERATE_ICON' )`, any value.
- **Rules:** When it is defined, the plugin does not register the `cptm_icon` image size. The icon picker then uses the full-size URL.
- **Evidence:** class-cptm.php:163-165, js/add-edit.js:27-31, README.md:19-20, readme.txt:35-36
- **Source:** code, docs · confidence high

### C-004 · Image size cptm_icon and the wp_prepare_attachment_for_js filter
- **Status:** approved
- **Interface:** image size `cptm_icon` (16×16, hard crop) and a filter on `wp_prepare_attachment_for_js` for all attachments.
- **Consumers:** js/add-edit.js reads `attachment.sizes.cptm_icon`; all media modals on the site get the extra sizes.
- **Shape:** `response.sizes[<size>] = { height, width, url, orientation }`.
- **Rules:** Keep the size name. The filter runs site-wide.
- **Evidence:** class-cptm.php:77, class-cptm.php:164, class-cptm.php:1578-1601, js/add-edit.js:27
- **Source:** code · confidence high

### C-005 · Text domain custom-post-type-maker
- **Status:** approved
- **Interface:** the text domain and `Domain Path: /lang` in the plugin header.
- **Consumers:** translate.wordpress.org language packs.
- **Shape:** `custom-post-type-maker` in every translatable string.
- **Rules:** Never change the domain. Literal strings only, so translators can extract them.
- **Evidence:** class-cptm.php:9-10, class-cptm.php:91
- **Source:** code · confidence high

### C-006 · Option cptm_plugin_settings_changed
- **Status:** approved
- **Interface:** a boolean option in `wp_options`.
- **Consumers:** the plugin itself across requests (PATTERN-004).
- **Shape:** true after a definition save; false after the flush.
- **Rules:** Keep the name, so a flag set before an update still triggers the flush.
- **Evidence:** class-cptm.php:1190, class-cptm.php:1197-1199
- **Source:** code · confidence high

## Consumed

_None. The plugin consumes no interface from another Graffino repo._

## External

### wordpress.org · plugin SVN repository
- **Interface:** `https://plugins.svn.wordpress.org/custom-post-type-maker`. Local working copy: `/Users/Zeno/Work/Graffino/WordPress/wordpress.org/Custom-Post-Type-Maker`.
- **We rely on:** `trunk/` holds `class-cptm.php`, `css/`, `js/`, `lang/`, `readme.txt`, `README.md`, `CHANGELOG.md`. `tags/X.Y.Z/` (no "v") holds a copy of trunk. Root `assets/` holds banners, icons and screenshots. `readme.txt` `Stable tag` selects the tag. `phpcs.xml`, `.github/`, `docs/`, `.claude/`, `AGENTS.md`, `CLAUDE.md` and `CONTEXT.md` are not shipped.
- **Known quirks:** wordpress.org shows a warning when `Tested up to` is three or more majors behind.
- **Evidence:** readme.txt:1-8, `svn ls …/tags/`

### WordPress core · post type and taxonomy API
- **Interface:** `register_post_type()`, `register_taxonomy()`, `wp_enqueue_media()`, `add_meta_box()`, `manage_*_columns` filters.
- **We rely on:**
  - Post type args: labels, description, menu_icon, rewrite (slug, with_front, feeds, pages), menu_position, public, show_ui, has_archive, exclude_from_search, capability_type, hierarchical, show_in_menu, query_var, show_in_rest, publicly_queryable, supports, taxonomies.
  - Taxonomy args: label, labels, rewrite (slug or false), show_ui, hierarchical, query_var, show_in_rest, show_admin_column.
- **Known quirks:** `show_in_rest` needs WordPress 4.7 or later, but `readme.txt` says "Requires at least: 3.0.0".
- **Evidence:** class-cptm.php:335-356, class-cptm.php:431-443, class-cptm.php:191-203
