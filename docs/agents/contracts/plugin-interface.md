# Contracts — Plugin interface

> Provided contracts. Index: `docs/agents/CONTRACTS.md`.

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
- **Rules:** When it is defined, the plugin never makes the `cptm_icon` size (C-007). The icon picker then uses the full-size URL.
- **Evidence:** class-cptm.php:163-165, js/add-edit.js:27-31, README.md:19-20, readme.txt:35-36
- **Source:** code, docs · confidence high

### C-005 · Text domain custom-post-type-maker
- **Status:** approved
- **Interface:** the text domain and `Domain Path: /lang` in the plugin header.
- **Consumers:** translate.wordpress.org language packs.
- **Shape:** `custom-post-type-maker` in every translatable string.
- **Rules:** Never change the domain. Literal strings only, so translators can extract them.
- **Evidence:** class-cptm.php:9-10, class-cptm.php:91
- **Source:** code · confidence high

### C-009 · WP-CLI commands
- **Status:** approved
- **Interface:** `wp cptm list [--format]`, `wp cptm export [--file]`, `wp cptm import <file> [--mode=skip|replace] [--publish]`, `wp cptm code <name>`.
- **Consumers:** site owners and deploy scripts.
- **Shape:** export and import use C-008. `import` exits with an error when an item fails.
- **Rules:** Keep the command names and options; add new options only.
- **Evidence:** includes/class-cptm-cli.php; tests/smoke/blueprint.json (wp-cli steps)
- **Source:** code · confidence high
