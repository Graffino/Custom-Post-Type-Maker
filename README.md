# Custom Post Type Maker

![Custom Post Type Maker](https://github.com/Graffino/Custom-Post-Type-Maker/blob/develop/assets/banner-1544x500.png)

Custom Post Type Maker lets you create Custom Post Types and custom Taxonomies in a user friendly way. Originally by [Bakhuys](http://www.bakhuys.com/).

## Description

Custom Post Type Maker is the perfect plugin to create Custom Post Types and custom Taxonomies in a user friendly way, just like managing your regular posts and pages.

## Requirements

- WordPress 6.5 or later
- PHP 8.1 or later

## Installation

1. Upload `custom-post-type-maker` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Click the new menu item 'Post Types' to create a Custom Post Type or a custom Taxonomy

## Additional configuration

You can add `CPTM_DONT_GENERATE_ICON` in `wp-config.php` to prevent CPTM to generate the 16x16px WordPress image_size. You can do this after you upload an initial icon.
We've added this option because some users have very large image galleries and the additional WordPress image_size matters.

## Frequently Asked Questions

Please ask your question in the [Support Forums](https://wordpress.org/support/plugin/custom-post-type-maker/).

## Feature requests

We maintain this plugin and add features that many users ask for. Open a feature request in [Issues](https://github.com/Graffino/custom-post-type-maker/issues), or submit a pull request.

## WP-CLI

```bash
wp cptm list
wp cptm export --file=types.json
wp cptm import types.json --mode=replace --publish
wp cptm code book
```

## Development

Needs Node.js. Tests run in WordPress Playground:

```bash
tests/run.sh            # smoke tests
tests/plugin-check.sh   # builds dist/ and runs Plugin Check
```

## Localization

Help [translate this plugin](https://translate.wordpress.org/projects/wp-plugins/custom-post-type-maker) in your language.

## Changelog

See [CHANGELOG.md](https://raw.githubusercontent.com/Graffino/Custom-Post-Type-Maker/develop/CHANGELOG.md).
