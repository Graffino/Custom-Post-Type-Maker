# Changelog

## 2.0.0

- New: Tools page (Post Types → Tools) with JSON export and import of definitions. Imports are saved as drafts unless you select "Publish".
- New: import your post types and taxonomies from Custom Post Type UI (read only; Custom Post Type UI is not changed).
- New: "Get code" box on each definition and on the Tools page: PHP code that registers the same type without this plugin.
- New: WP-CLI commands: `wp cptm list`, `wp cptm export`, `wp cptm import`, `wp cptm code`.
- New post type options: Block Template, Template Lock, Embeddable (WordPress 6.8+), and a Custom capability type. Administrators get the custom capabilities automatically.
- New taxonomy option: Sort.
- New: optional data removal on uninstall (Tools page). Your posts and terms are never deleted.
- Changed: the 16×16 menu icon size is no longer made for every upload; it is made only for the image you select as an icon.
- Multisite: network activation now flushes the permalinks of every site.
- Security: only administrators (`manage_options`) can view, create, edit or delete post type and taxonomy definitions. Before, Editors could reach these screens through a direct URL.
- Security: the save handler checks the user's capability, sanitizes every field, and accepts only known values for supports, built-in taxonomies and post types.
- Security: all values on the admin screens are escaped on output. Stored data is read without `unserialize()` of objects.
- Post type and taxonomy names are validated: lower-case letters, numbers, dashes and underscores, at most 20 (post types) or 32 (taxonomies) characters, and no reserved or already registered names.
- New post type options: Show in Navigation Menus, Show in Admin Bar, REST Base, REST Namespace, Can Export, Delete With User, Archive Slug.
- New taxonomy options: Public, Publicly Queryable, Show in Navigation Menus, Show in Tag Cloud, Show in Quick Edit, REST Base, Default Term.
- Full, translatable label sets for custom post types and taxonomies (current WordPress labels).
- Each post type and taxonomy is now registered once per request (before, more definitions caused repeated registrations).
- Fixed: the list of "Other registered" post types and taxonomies showed your own definitions when you had more than 5.
- Fixed: the plugin columns printed on the post lists of other post types.
- Fixed: dead Changelog, Support and Vote links; untranslatable strings; "Seperate" typo.
- Requires PHP 8.1 and WordPress 6.5 or later. Tested up to WordPress 7.1.

## 1.2.0

- Added `CPTM_DONT_GENERATE_ICON` constant for disabling the 16x16px image_size via wp-config.php as suggested by @clubside and @ldeejay
- Fixed spelling as suggested by @ldeejay
- Compatibility with latest version of WP

## 1.1.15

- Better sanitize taxonomy name as suggested by @ldeejay

## 1.1.14

- Compatibility with future version of WP

## 1.1.13

- Fix translations. Add .pot template file, reported by @wicko77

## 1.1.12

- Remove 'All' from taxonomy names as suggested by @gnowland

## 1.1.11

- Update assets

## 1.1.10

- Compatibility with future version of WP

## 1.1.9

- Compatibility with future version of WP

## 1.1.8

- Fixes undefined variable (Thanks @richardshea,@kubik101)[#20](https://github.com/Graffino/Custom-Post-Type-Maker/issues/21), [#20](https://github.com/Graffino/Custom-Post-Type-Maker/issues/21)

### 1.1.7

- Makes code compatible with WordPressCore PHP Linter
- Marks plugin compatible with future WordPress versions

## 1.1.6

- Fixes tab navigation (Thanks @mediengestalter2)[#16](https://github.com/Graffino/Custom-Post-Type-Maker/issues/16)

## 1.1.5

- Add ability to show custom post in REST API (Thanks @asithade)[#14](https://github.com/Graffino/Custom-Post-Type-Maker/issues/14).

## 1.1.4

- Add ability to show custom taxonomy column in post listing.

## 1.1.3

- Removed forgotten development dump. Sorry about that.

## 1.1.2

- [Bugfix] Make `with_front` available in `register_post` when set to `false` (Credit: @cmerrick). Closes: [#7](https://github.com/Graffino/Custom-Post-Type-Maker/issues/7)

## 1.1.1

- [Feature] Auto-flush rewrite rules on: custom post save, plugin activation, plugin deactivation.
- [Bugfix] Made `publicly_queryable` default to true. This fixes permalink errors after upgrading to v1.1.0 on existing installations.
- [Localization] Add french translation. (Credit: @momo-fr).

## 1.1.0

- [Feature] Implemented `publicly_queryable`. Closes: [#5](https://github.com/Graffino/Custom-Post-Type-Maker/issues/5)

## 1.0.4

- [Bugfix] Renamed plugin to match WP Plugins

## 1.0.3

- [Bugfix] Fix typos

## 1.0.2

- [Bugfix] Fixed `undefined` error that prevented media library from loading

## 1.0.1

- Compatibility with future version of WP

## 1.0.0

- [Added] Ability to select [DashIcons](https://developer.wordpress.org/resource/dashicons/#layout) as Custom Post Type icon.
- [Bugfix] Fixed `add_utility_page provokes "deprecated" notice in 4.5.2`
