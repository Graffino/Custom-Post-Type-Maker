# Custom Post Type Maker

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
- [Forked] Forked https://wordpress.org/plugins/custom-post-type-maker/


