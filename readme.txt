=== Custom Post Type Maker ===
Contributors: graffino, zeno.popovici, jornbakhuys
Tags: custom, post, type, custom post type, custom post types, maker, make, cpt, post types, taxonomy, taxonomies, tax, custom taxonomies
Requires at least: 3.0.0
Tested up to: 4.8
Stable tag: 1.1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.me/zenopopovici/

Custom Post Type Maker lets you create Custom Post Types and custom Taxonomies in a user friendly way.

== Description ==

Custom Post Type Maker is the perfect plugin to create Custom Post Types and custom Taxonomies in a user friendly way, just like managing your regular posts and pages.

Originally by [Bakhuys](http://www.bakhuys.com/).

= Features =
* Fully integrates with the Wordpress API, for the best compatibility
* Lets you create Custom Post Types and custom Taxonomies without the need of writing any code
* Provides an interface to manage your Custom Post Types, just like managing your regular posts and pages
* Provides almost all the parameters of the WordPress CPT API
* Shows you a list of all other registered Custom Post Types and custom Taxonomies in Wordpress
* Uses the WordPress Media Uploader or Dash Icons (Native Wordpress Icons) to let you manage the Custom Post Type icon

== Installation ==

1. Upload 'custom-post-type-maker' to the '/wp-content/plugins/' directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Click the new menu item 'Post Types' to create a Custom Post Type or a custom Taxonomy.

== Frequently Asked Questions ==

= Where do I get support? =
Ask your question in the [Support Forums](http://wordpress.org/support/plugin/custom-post-type-maker). Please note that due to restricted time availability we're not actively answering questions, unless a real bug is reported. See: "What should I try before reporting a bug" section.

= What should I try before reporting a bug? =
1. Disable all your plugins except "Custom Post Type Maker".
2. See if the plugin behaves normally with the default Wordpress theme.
3. Try to run the plugin on a clean Wordpress install.

If all of this fails, see: "How should I report bugs?" section.

= How should I report bugs? =
Please report your bug on [GitHub](https://github.com/Graffino/custom-post-type-maker/issues). Issues will not be handled elsewhere.

Make sure you attach to the report:
1. Your Wordpress version
2. Your plugin version
3. Screenshots
4. Steps to reproduce the problem
5. Anything else you think would be useful to pinpoint the problem

= How do I request a feature? =
We're supporting this plugin but not actively developing it. If you're interested to contribute you can submit a PR on [GitHub](https://github.com/Graffino/custom-post-type-maker/pulls).

= How do I get the plugin in my own language? =
You'll have to do it yourself. Help [translate this plugin](https://translate.wordpress.org/projects/wp-plugins/custom-post-type-maker).

== Screenshots ==

1. Creating Custom Post Types
2. Overview of your created Custom Post Types
3. Creating custom Taxonomies

== Changelog ==

= 1.1.3
- Removed forgotten development dump. Sorry about that.

= 1.1.2 =
- [Bugfix] Make `with_front` available in `register_post` when set to `false` (Credit: @cmerrick). Closes: [#7](https://github.com/Graffino/Custom-Post-Type-Maker/issues/7)

= 1.1.1 =
- [Feature] Auto-flush rewrite rules on: custom post save, plugin activation, plugin deactivation.
- [Bugfix] Made `publicly_queryable` default to true. This fixes permalink errors after upgrading to v1.1.0 on existing installations.
- [Localization] Add french translation. (Credit: @momo-fr).

= 1.1.0 =
- [Feature] Implemented `publicly_queryable`. Closes: [#5](https://github.com/Graffino/Custom-Post-Type-Maker/issues/5)

= 1.0.4 =
- [Bugfix] Renamed plugin to match WP Plugins

= 1.0.3 =
- [Bugfix] Fix typos

= 1.0.2 =
- [Bugfix] Fixed `undefined` error that prevented media library from loading

= 1.0.1 =
- Compatibility with future version of WP

= 1.0.0 =
* [Added] Ability to select [DashIcons](https://developer.wordpress.org/resource/dashicons/#layout) as Custom Post Type icon.
* [Bugfix] Fixed `add_utility_page provokes "deprecated" notice in 4.5.2`
* [Forked] Forked https://wordpress.org/plugins/custom-post-type-maker/

== Upgrade Notice ==

= 1.1.3 =
- Removed forgotten development dump in v1.1.2. Sorry about that.

= 1.1.1 =
- Important upgrade: this fixes permalink errors after upgrading to v1.1.0 on existing installations.
