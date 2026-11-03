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
| Part | Path | Responsibility | Talks to |
|---|---|---|---|
| Plugin header and bootstrap | class-cptm.php:1-47, :1612 | plugin metadata, direct-access guard, `new Cptm()` | WordPress plugin loader |
| Constructor | class-cptm.php:60-92 | wires every hook, loads the text domain | WordPress hooks |
| `init()` | class-cptm.php:97-166 | registers the private types `cptm`, `cptm_tax`; image size `cptm_icon` | WordPress core |
| `cptm_create_custom_post_types()` | class-cptm.php:210-449 | reads definitions, registers post types and taxonomies | wp_posts, wp_postmeta, core |
| Admin menu | class-cptm.php:171-175 | "Post Types" menu and "Taxonomies" submenu (manage_options) | wp-admin |
| Asset loader | class-cptm.php:182-205 | enqueues css/js on the plugin screens only | css/, js/ |
| Meta boxes | class-cptm.php:454-1030 | "Options" forms for a post type and a taxonomy | wp-admin edit screen |
| Save handler | class-cptm.php:1037-1191 | nonce check, writes meta, sets the flush flag | wp_postmeta, wp_options |
| Rewrite flush | class-cptm.php:1196-1209 | flush on flag, on activation, on deactivation | core rewrite API |
| List columns | class-cptm.php:1217-1301 | custom columns on the definition lists | wp-admin lists |
| Admin footer | class-cptm.php:1306-1540 | sidebar box and "other registered" tables (hidden, moved by JS) | js/overview.js |
| Updated messages | class-cptm.php:1549-1568 | notices after save | wp-admin |
| Media filter | class-cptm.php:1578-1601 | adds intermediate sizes to the media modal data | js/add-edit.js |
| Admin JS | js/overview.js, js/add-edit.js | moves footer parts into the page; icon picker (wp.media) | DOM, wp.media |
| Admin CSS | css/overview.css, css/add-edit.css | layout of lists, sidebar and option tables | wp-admin |

## Data flow
1. **Save a definition:** admin submits the edit form → `save_post` → nonce check → `update_post_meta` per field → option `cptm_plugin_settings_changed` = true.
2. **Register:** any request → `init` → `get_posts` for published `cptm` and `cptm_tax` → build args from meta with defaults (PATTERN-005) → `register_post_type` / `register_taxonomy`. The register loops sit inside the read loops, so each type is registered more than once (fix candidate).
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
PATTERN-001. Runtime: WordPress admin on each site. No local runtime yet (Q-002).

## Boundaries
- Admin users write definitions. The save handler checks a nonce but no capability (Q-003).
- Definition values are escaped on save, and some admin outputs echo them raw. This is a fix candidate for the next release.
- Stored meta is a compatibility promise to existing sites (C-001).
