# Custom Post Type Maker

![Custom Post Type Maker](https://github.com/Graffino/Custom-Post-Type-Maker/blob/master/assets/banner-1544x500.png)

Custom Post Type Maker lets you create Custom Post Types and custom Taxonomies in a user friendly way. Originally by [Bakhuys](http://www.bakhuys.com/).

## Description

Custom Post Type Maker is the perfect plugin to create Custom Post Types and custom Taxonomies in a user friendly way, just like managing your regular posts and pages.

## Installation

1. Upload 'custom-post-type-maker' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Click the new menu item 'Post Types' to create a Custom Post Type or a custom Taxonomy

## Frequently Asked Questions

Please ask your question in the [Support Forums](http://wordpress.org/support/plugin/custom-post-type-maker).

## Feature requests

We're supporting this plugin but not actively developing it. If you're interested to contribute you can submit a PR on [GitHub](https://github.com/Graffino/custom-post-type-maker/pulls).

## Localization

Help [translate this plugin](https://translate.wordpress.org/projects/wp-plugins/custom-post-type-maker) in your language.

## Changelog

## 1.1.15

- Better sanitize taxonomy name as suggested by @ldeejay

## 1.1.14

- Compatibility with future version of WP

## 1.1.13

- Fix translations. Add .pot template file, reported by @wicko77

## 1.1.12

- Remove 'All' from taxonomy names as suggested by @gnowland

## 1.1.11

- Update assets.

## 1.1.10

- Compatibility with future version of WP

### 1.1.9

- Compatibility with future version of WP

### 1.1.8

- Fixes undefined variable (Thanks @richardshea,@kubik101)[#20](https://github.com/Graffino/Custom-Post-Type-Maker/issues/21), [#20](https://github.com/Graffino/Custom-Post-Type-Maker/issues/21)

### 1.1.7

- Makes code compatible with WordPressCore PHP Linter
- Marks plugin compatible with future WordPress versions

### 1.1.6

- Fixes tab navigation (Thanks @mediengestalter2)[#16](https://github.com/Graffino/Custom-Post-Type-Maker/issues/16)

### 1.1.5

- Add ability to show custom post in REST API (Thanks @asithade)[#14](https://github.com/Graffino/Custom-Post-Type-Maker/issues/14).

### 1.1.4

- Add ability to show custom taxonomy column in post listing.

### 1.1.3

- Removed forgotten development dump. Sorry about that.

### 1.1.2

- [Bugfix] Make `with_front` available in `register_post` when set to `false` (Credit: @cmerrick). Closes: [#7](https://github.com/Graffino/Custom-Post-Type-Maker/issues/7)

### 1.1.1

- [Feature] Auto-flush rewrite rules on: custom post save, plugin activation, plugin deactivation.
- [Bugfix] Made `publicly_queryable` default to true. This fixes permalink errors after upgrading to v1.1.0 on existing installations.
- [Localization] Add french translation. (Credit: @momo-fr).

### 1.1.0

- [Feature] Implemented `publicly_queryable`. Closes: [#5](https://github.com/Graffino/Custom-Post-Type-Maker/issues/5)

### 1.0.4

- [Bugfix] Renamed plugin to match WP Plugins

### 1.0.3

- [Bugfix] Fix typos

### 1.0.2

- [Bugfix] Fixed `undefined` error that prevented media library from loading

### 1.0.1

- Compatibility with future version of WP

### 1.0.0

- [Added] Ability to select [DashIcons](https://developer.wordpress.org/resource/dashicons/#layout) as Custom Post Type icon.
- [Bugfix] Fixed `add_utility_page provokes "deprecated" notice in 4.5.2`
