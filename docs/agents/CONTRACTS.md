# Contracts — Custom-Post-Type-Maker

<!-- graffino-agentic:begin v1 -->
> Promises across a boundary. **Provided:** this repo owns the contract; the text
> lives here only. **Consumed:** a link to the provider's entry plus the fields this
> repo relies on. **External:** what we rely on from a third-party service.
> Changing a provided contract is a PROPOSAL (AGENTS.md → The gate) and the plan
> lists the consumers' files too. Status: `approved` · `proposed` · `superseded-by`.
<!-- graffino-agentic:end -->

## Index
### [Export](contracts/export.md)
- C-008 · Export file format · provided

### [Media](contracts/media.md)
- C-004 · Image size cptm_icon and the wp_prepare_attachment_for_js filter · provided · _superseded-by C-007_
- C-007 · Image size cptm_icon, made on demand, and the wp_prepare_attachment_for_js filter · provided

### [Plugin interface](contracts/plugin-interface.md)
- C-002 · Plugin identity custom-post-type-maker/class-cptm.php · provided
- C-003 · Constant CPTM_DONT_GENERATE_ICON · provided
- C-005 · Text domain custom-post-type-maker · provided
- C-009 · WP-CLI commands · provided

### [Stored data](contracts/stored-data.md)
- C-001 · Stored definition data (post meta on cptm / cptm_tax) · provided
- C-006 · Option cptm_plugin_settings_changed · provided
- C-010 · Options cptm_granted_caps and cptm_delete_data_on_uninstall · provided

---

## Provided

The provided contracts live in topic files in `docs/agents/contracts/` (see the index above).

## Consumed

_None. The plugin consumes no interface from another Graffino repo._

## External

### Custom Post Type UI · stored settings (read only)
- **Interface:** the options `cptui_post_types` and `cptui_taxonomies` (arrays keyed by name; flags are the strings 'true' / 'false').
- **We rely on:** post types: name, label, singular_label, description, public, publicly_queryable, show_ui, show_in_menu, show_in_nav_menus, show_in_rest, rest_base, rest_namespace, has_archive, has_archive_string, exclude_from_search, capability_type, hierarchical, can_export, delete_with_user, rewrite, rewrite_slug, rewrite_withfront, query_var, menu_position, menu_icon, supports, taxonomies. Taxonomies: name, label, singular_label, public, publicly_queryable, hierarchical, show_ui, show_in_nav_menus, query_var, rewrite, rewrite_slug, show_admin_column, show_in_rest, rest_base, show_tagcloud, show_in_quick_edit, sort, default_term, object_types.
- **Known quirks:** The plugin never writes these options. Custom Post Type UI registers the same names, so the import uses `allow_registered` and makes drafts.
- **Evidence:** includes/class-cptm-definition.php `from_cptui()`; tests/smoke/run.php section 6

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
- **Known quirks:** The plugin requires WordPress 6.5 (D-009). Labels that an older version does not know are ignored.
- **Evidence:** class-cptm.php:335-356, class-cptm.php:431-443, class-cptm.php:191-203
