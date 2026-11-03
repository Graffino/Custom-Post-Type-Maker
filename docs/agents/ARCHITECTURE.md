# Architecture — Custom-Post-Type-Maker

<!-- graffino-agentic:begin v1 -->
> How this repo is built, generated from the code at backfill and kept current by
> whoever changes the structure. The code wins: when this page is wrong, fix it.
<!-- graffino-agentic:end -->

## Overview
A WordPress plugin with one PHP class (`Cptm` in `class-cptm.php`). It runs inside
wp-admin of each site that installs it. Site admins create definitions on admin
screens; on every request the plugin reads the definitions and registers the post
types and taxonomies with WordPress core. There is no front-end output of its own.

```text
wp-admin screens ──save──▶ cptm / cptm_tax posts + post meta (wp_posts, wp_postmeta)
                                   │
every request: init ──read─────────┘──▶ register_post_type() / register_taxonomy() ──▶ WordPress core
save ──▶ option cptm_plugin_settings_changed ──▶ next admin_init: flush_rewrite_rules()
media modal ◀── wp_prepare_attachment_for_js filter (adds image sizes, cptm_icon)
GitHub (develop/master) ──release copy──▶ wordpress.org SVN (trunk, tags/X.Y.Z, assets) ──▶ sites
```

## Parts
| Part | Where (class-cptm.php) | Responsibility | Talks to |
|---|---|---|---|
| Plugin header and bootstrap | top of file, last line | plugin metadata, ABSPATH guard, `new Cptm()` | WordPress plugin loader |
| Constructor | `__construct()` | wires every hook | WordPress hooks |
| `init()` | `init()`, `admin_capabilities()` | registers the private types `cptm`, `cptm_tax` (all capabilities = manage_options, D-008); image size `cptm_icon` | WordPress core |
| Registration | `cptm_create_custom_post_types()`, `register_post_type_definition()`, `register_taxonomy_definition()`, `post_type_labels()`, `taxonomy_labels()` | reads definitions, registers post types and taxonomies once each | wp_posts, wp_postmeta, core |
| Meta readers | `meta_value()`, `meta_flag()`, `meta_optional_flag()`, `meta_array()` | read stored meta with the C-001 defaults; no object unserialize | wp_postmeta |
| Admin menu | `cptm_admin_menu()` | "Post Types" menu and "Taxonomies" submenu (manage_options) | wp-admin |
| Asset loader | `cptm_styles()`, `is_plugin_screen()` | enqueues css/js on the plugin screens only | css/, js/ |
| Meta boxes | `cptm_meta_box()`, `cptm_tax_meta_box()`, row helpers | "Options" forms | wp-admin edit screen |
| Save handlers | `cptm_save_post()`, `cptm_tax_save_post()`, `can_save()`, `save_*()` | nonce + capability check, sanitize, validate names, write meta, set the flush flag | wp_postmeta, wp_options, transients |
| Admin notices | `cptm_admin_notices()` | shows a rejected-name error once | transient `cptm_notice_<user>` |
| Rewrite flush | `cptm_plugin_settings_flush_rewrite()`, `cptm_plugin_activate_flush_rewrite()` | flush on flag, on activation, on deactivation | core rewrite API |
| List columns | `cptm_*_columns()` | custom columns on the two definition lists only | wp-admin lists |
| Admin footer | `cptm_admin_footer()`, `other_registered_table()` | sidebar box and "other registered" tables on the two lists (hidden, moved by JS) | js/overview.js |
| Updated messages | `cptm_post_updated_messages()` | notices after save, both definition types | wp-admin |
| Media filter | `wp_prepare_attachment_for_js()` | adds intermediate sizes to the media modal data | js/add-edit.js |
| Admin JS | js/overview.js, js/add-edit.js | moves footer parts into the page; icon picker (wp.media, strings from `cptmL10n`) | DOM, wp.media |
| Admin CSS | css/overview.css, css/add-edit.css | layout of lists, sidebar and option tables | wp-admin |

## Data flow
1. **Save a definition:** admin submits the edit form → `save_post_cptm` / `save_post_cptm_tax` → nonce and capability check → sanitize and validate → `update_post_meta` per field → option `cptm_plugin_settings_changed` = true.
2. **Register:** any request → `init` → `get_posts` for published `cptm` and `cptm_tax` → build args from meta with defaults (PATTERN-005) → `register_post_type` / `register_taxonomy`, once per definition.
3. **Flush:** next `admin_init` → flag is true → `flush_rewrite_rules()` → flag = false.
4. **Icon:** admin clicks "Upload Icon" → wp.media frame → the `cptm_icon` size URL (or full URL, C-003) → hidden field `cptm_icon_url`.

## State and storage
| What | Where | Owner | Notes |
|---|---|---|---|
| Post type definitions | wp_posts (type `cptm`) + wp_postmeta `cptm_*` | this plugin | C-001 |
| Taxonomy definitions | wp_posts (type `cptm_tax`) + wp_postmeta `cptm_tax_*` | this plugin | C-001 |
| Flush flag | wp_options `cptm_plugin_settings_changed` | this plugin | C-006 |
| Icon image size | attachment metadata `cptm_icon` | WordPress media | C-004 |

## Configuration
| Name | Where set | Used by | Notes |
|---|---|---|---|
| `CPTM_DONT_GENERATE_ICON` | site `wp-config.php` | `init()` | C-003 |

## Build, deploy, runtime
No build step. The source files ship as they are. Release: AGENTS.md section 5 and
PATTERN-001. Runtime: WordPress admin on each site. Local runtime: WordPress Playground (AGENTS.md section 2).

## Boundaries
- Only users with `manage_options` read or write definitions (D-008). The save handlers check the nonce and the capability.
- Input is sanitized on save with allowlists; every admin output is escaped. The description stays stored HTML-encoded (C-001).
- Stored meta is a compatibility promise to existing sites (C-001).
