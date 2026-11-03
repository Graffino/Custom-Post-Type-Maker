<?php
/*
Plugin Name: Custom Post Type Maker
Plugin URI: https://github.com/Graffino/Custom-Post-Type-Maker
Description: Custom Post Type Maker lets you create Custom Post Types and custom Taxonomies in a user friendly way.
Version: 1.3.0
Requires at least: 6.5
Requires PHP: 8.1
Author: Graffino
Author URI: https://www.graffino.com/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: custom-post-type-maker
Domain Path: /lang


Originally by: http://www.bakhuys.com/

Released under the GPL v.2, http://www.gnu.org/copyleft/gpl.html

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

// phpcs:disable WordPress.PHP.YodaConditions.NotYoda
// phpcs:disable Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace
// phpcs:disable Squiz.PHP.DisallowMultipleAssignments.Found

*/
/**
 * @author    Zeno Popovici <zeno@graffino.com>
 * @copyright Copyright (c) 2018, Graffino
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @package   Custom_Post_Types_Maker
 * @version   1.3.0
 */

// Avoid direct calls to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main class to run the plugin
 *
 * @since 1.0.0
 */
class Cptm {
	/** Capability needed to manage definitions (D-008). */
	const CAPABILITY = 'manage_options';

	/** Max post type name length (WordPress limit). */
	const MAX_POST_TYPE_LENGTH = 20;

	/** Max taxonomy name length (WordPress limit). */
	const MAX_TAXONOMY_LENGTH = 32;

	/** Values allowed in cptm_supports. */
	const SUPPORTS = array( 'title', 'editor', 'excerpt', 'trackbacks', 'custom-fields', 'comments', 'revisions', 'thumbnail', 'author', 'page-attributes', 'post-formats' );

	/** Values allowed in cptm_builtin_taxonomies. */
	const BUILTIN_TAXONOMIES = array( 'category', 'post_tag' );

	/** Post type names that WordPress core uses or reserves. */
	const RESERVED_POST_TYPES = array( 'post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation', 'wp_font_family', 'wp_font_face', 'action', 'author', 'order', 'theme', 'cptm', 'cptm_tax' );

	/** Taxonomy names that WordPress core uses or reserves (registered taxonomies and public query vars). */
	const RESERVED_TAXONOMIES = array( 'category', 'post_tag', 'nav_menu', 'link_category', 'post_format', 'wp_theme', 'wp_template_part_area', 'wp_pattern_category', 'attachment', 'attachment_id', 'author', 'author_name', 'calendar', 'cat', 'category_name', 'category__and', 'category__in', 'category__not_in', 'comments_per_page', 'comments_popup', 'custom', 'customize_messenger_channel', 'customized', 'cpage', 'day', 'debug', 'embed', 'error', 'exact', 'feed', 'fields', 'hour', 'm', 'minute', 'monthnum', 'more', 'name', 'nav_menu', 'nonce', 'nopaging', 'offset', 'order', 'orderby', 'p', 'page', 'page_id', 'paged', 'pagename', 'pb', 'perm', 'post', 'post__in', 'post__not_in', 'post_mime_type', 'post_status', 'post_tag', 'post_type', 'posts', 'posts_per_archive_page', 'posts_per_page', 'preview', 'robots', 's', 'search', 'second', 'sentence', 'showposts', 'static', 'status', 'subpost', 'subpost_id', 'tag', 'tag__and', 'tag__in', 'tag__not_in', 'tag_id', 'tag_slug__and', 'tag_slug__in', 'taxonomy', 'tb', 'term', 'terms', 'theme', 'title', 'type', 'types', 'w', 'withcomments', 'withoutcomments', 'year' );

	/** @var string Plugin URL */
	private string $dir;

	/** @var string Plugin path */
	private string $path;

	/** @var string Plugin version */
	private string $version;

	/** @var int Next tabindex in a meta box */
	private int $tab = 1;

	/**
	 * Constructor
	 */
	public function __construct() {
		// vars
		$this->dir     = plugins_url( '', __FILE__ );
		$this->path    = plugin_dir_path( __FILE__ );
		$this->version = '1.3.0';

		// actions
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'cptm_create_custom_post_types' ) );
		add_action( 'admin_menu', array( $this, 'cptm_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'cptm_styles' ) );
		add_action( 'add_meta_boxes', array( $this, 'cptm_create_meta_boxes' ) );
		add_action( 'save_post_cptm', array( $this, 'cptm_save_post' ) );
		add_action( 'save_post_cptm_tax', array( $this, 'cptm_tax_save_post' ) );
		add_action( 'admin_init', array( $this, 'cptm_plugin_settings_flush_rewrite' ) );
		add_action( 'admin_notices', array( $this, 'cptm_admin_notices' ) );
		add_action( 'manage_cptm_posts_custom_column', array( $this, 'cptm_custom_columns' ), 10, 2 );
		add_action( 'manage_cptm_tax_posts_custom_column', array( $this, 'cptm_tax_custom_columns' ), 10, 2 );
		add_action( 'admin_footer', array( $this, 'cptm_admin_footer' ) );

		// filters
		add_filter( 'manage_cptm_posts_columns', array( $this, 'cptm_change_columns' ) );
		add_filter( 'manage_edit-cptm_sortable_columns', array( $this, 'cptm_sortable_columns' ) );
		add_filter( 'manage_cptm_tax_posts_columns', array( $this, 'cptm_tax_change_columns' ) );
		add_filter( 'manage_edit-cptm_tax_sortable_columns', array( $this, 'cptm_tax_sortable_columns' ) );
		add_filter( 'post_updated_messages', array( $this, 'cptm_post_updated_messages' ) );
		add_filter( 'parent_file', array( $this, 'cptm_parent_file' ) );
		add_filter( 'submenu_file', array( $this, 'cptm_submenu_file' ) );
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'wp_prepare_attachment_for_js' ), 10, 3 );

		// hooks
		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
		register_activation_hook( __FILE__, array( $this, 'cptm_plugin_activate_flush_rewrite' ) );

		// Translations load on demand from translate.wordpress.org (D-006).
	}

	/**
	 * Capabilities of the cptm and cptm_tax post types: every capability is manage_options (D-008).
	 *
	 * @return array<string, string>
	 */
	private function admin_capabilities(): array {
		return array_fill_keys(
			array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'delete_posts', 'publish_posts', 'read_private_posts', 'read', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts', 'create_posts' ),
			self::CAPABILITY
		);
	}

	/**
	 * Initialize plugin
	 */
	public function init(): void {
		// Create cptm post type
		$labels = array(
			'name'               => __( 'Custom Post Type Maker', 'custom-post-type-maker' ),
			'singular_name'      => __( 'Custom Post Type', 'custom-post-type-maker' ),
			'add_new'            => __( 'Add New', 'custom-post-type-maker' ),
			'add_new_item'       => __( 'Add New Custom Post Type', 'custom-post-type-maker' ),
			'edit_item'          => __( 'Edit Custom Post Type', 'custom-post-type-maker' ),
			'new_item'           => __( 'New Custom Post Type', 'custom-post-type-maker' ),
			'view_item'          => __( 'View Custom Post Type', 'custom-post-type-maker' ),
			'search_items'       => __( 'Search Custom Post Types', 'custom-post-type-maker' ),
			'not_found'          => __( 'No Custom Post Types found', 'custom-post-type-maker' ),
			'not_found_in_trash' => __( 'No Custom Post Types found in Trash', 'custom-post-type-maker' ),
		);

		register_post_type(
			'cptm',
			array(
				'labels'       => $labels,
				'public'       => false,
				'show_ui'      => true,
				'capabilities' => $this->admin_capabilities(),
				'map_meta_cap' => false,
				'hierarchical' => false,
				'rewrite'      => false,
				'query_var'    => 'cptm',
				'supports'     => array(
					'title',
				),
				'show_in_menu' => false,
			)
		);

		// Create cptm_tax post type
		$labels = array(
			'name'               => __( 'Custom Taxonomies', 'custom-post-type-maker' ),
			'singular_name'      => __( 'Custom Taxonomy', 'custom-post-type-maker' ),
			'add_new'            => __( 'Add New', 'custom-post-type-maker' ),
			'add_new_item'       => __( 'Add New Custom Taxonomy', 'custom-post-type-maker' ),
			'edit_item'          => __( 'Edit Custom Taxonomy', 'custom-post-type-maker' ),
			'new_item'           => __( 'New Custom Taxonomy', 'custom-post-type-maker' ),
			'view_item'          => __( 'View Custom Taxonomy', 'custom-post-type-maker' ),
			'search_items'       => __( 'Search Custom Taxonomies', 'custom-post-type-maker' ),
			'not_found'          => __( 'No Custom Taxonomies found', 'custom-post-type-maker' ),
			'not_found_in_trash' => __( 'No Custom Taxonomies found in Trash', 'custom-post-type-maker' ),
		);

		register_post_type(
			'cptm_tax',
			array(
				'labels'       => $labels,
				'public'       => false,
				'show_ui'      => true,
				'capabilities' => $this->admin_capabilities(),
				'map_meta_cap' => false,
				'hierarchical' => false,
				'rewrite'      => false,
				'query_var'    => 'cptm_tax',
				'supports'     => array(
					'title',
				),
				'show_in_menu' => false,
			)
		);

		// Add image size for the Custom Post Type icon
		if ( ! defined( 'CPTM_DONT_GENERATE_ICON' ) ) {
			add_image_size( 'cptm_icon', 16, 16, true );
		}
	}

	/**
	 * Add admin menu items
	 */
	public function cptm_admin_menu(): void {
		// add cptm page to options menu
		add_menu_page( __( 'CPT Maker', 'custom-post-type-maker' ), __( 'Post Types', 'custom-post-type-maker' ), self::CAPABILITY, 'edit.php?post_type=cptm', '', 'dashicons-layout' );
		add_submenu_page( 'edit.php?post_type=cptm', __( 'Taxonomies', 'custom-post-type-maker' ), __( 'Taxonomies', 'custom-post-type-maker' ), self::CAPABILITY, 'edit.php?post_type=cptm_tax' );
	}

	/**
	 * The current admin screen, if it is a screen of this plugin.
	 *
	 * @param  string $base Screen base: 'edit' (list) or 'post' (add / edit).
	 * @return bool
	 */
	private function is_plugin_screen( string $base ): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen && $base === $screen->base && in_array( $screen->post_type, array( 'cptm', 'cptm_tax' ), true );
	}

	/**
	 * Keep the "Post Types" menu open on the taxonomy screens.
	 *
	 * @param  string $parent_file Parent menu file.
	 * @return string
	 */
	public function cptm_parent_file( $parent_file ) {
		$screen = get_current_screen();
		return ( $screen && 'cptm_tax' === $screen->post_type ) ? 'edit.php?post_type=cptm' : $parent_file;
	}

	/**
	 * Highlight the "Taxonomies" submenu on the taxonomy screens.
	 *
	 * @param  string|null $submenu_file Submenu file.
	 * @return string|null
	 */
	public function cptm_submenu_file( $submenu_file ) {
		$screen = get_current_screen();
		return ( $screen && 'cptm_tax' === $screen->post_type ) ? 'edit.php?post_type=cptm_tax' : $submenu_file;
	}

	/**
	 * Register admin styles
	 */
	public function cptm_styles(): void {
		// register overview style
		if ( $this->is_plugin_screen( 'edit' ) ) {
			wp_enqueue_style( 'cptm_admin_styles', $this->dir . '/css/overview.css', array(), $this->version );
			wp_enqueue_script( 'cptm_admin_js', $this->dir . '/js/overview.js', array( 'jquery' ), $this->version, true );
			add_thickbox();
		}

		// register add / edit style
		if ( $this->is_plugin_screen( 'post' ) ) {
			wp_enqueue_style( 'cptm_add_edit_styles', $this->dir . '/css/add-edit.css', array(), $this->version );
			wp_enqueue_script( 'cptm_admin__add_edit_js', $this->dir . '/js/add-edit.js', array( 'jquery' ), $this->version, true );
			wp_localize_script(
				'cptm_admin__add_edit_js',
				'cptmL10n',
				array(
					'frameTitle'  => __( 'Add Icon', 'custom-post-type-maker' ),
					'frameButton' => __( 'Select icon', 'custom-post-type-maker' ),
					'addIcon'     => __( 'Add icon', 'custom-post-type-maker' ),
					'uploadIcon'  => __( 'Upload Icon', 'custom-post-type-maker' ),
				)
			);
			wp_enqueue_media();
		}
	}

	/**
	 * One raw meta value, or null when the key does not exist.
	 *
	 * @param  array  $meta All meta of a definition (get_post_meta( $id )).
	 * @param  string $key  Meta key.
	 * @return string|null
	 */
	private function meta_value( array $meta, string $key ): ?string {
		return isset( $meta[ $key ][0] ) ? (string) $meta[ $key ][0] : null;
	}

	/**
	 * A '1'/'0' flag. A missing key gives $default (C-001).
	 *
	 * @param  array  $meta    All meta of a definition.
	 * @param  string $key     Meta key.
	 * @param  bool   $default Value for a missing key.
	 * @return bool
	 */
	private function meta_flag( array $meta, string $key, bool $default = false ): bool {
		$value = $this->meta_value( $meta, $key );
		return null === $value ? $default : '1' === $value;
	}

	/**
	 * A setting added in 1.3.0: '1', '0' or '' (WordPress default). Null means "do not pass the argument".
	 *
	 * @param  array  $meta All meta of a definition.
	 * @param  string $key  Meta key.
	 * @return bool|null
	 */
	private function meta_optional_flag( array $meta, string $key ): ?bool {
		$value = $this->meta_value( $meta, $key );
		if ( '1' === $value ) {
			return true;
		}
		if ( '0' === $value ) {
			return false;
		}
		return null;
	}

	/**
	 * A stored list of strings. Never creates objects.
	 *
	 * @param  string|null $raw Raw (serialized) meta value.
	 * @return string[]
	 */
	private function meta_array( ?string $raw ): array {
		if ( null === $raw || '' === $raw || ! is_serialized( $raw ) ) {
			return array();
		}
		$value = unserialize( $raw, array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- classes are not allowed.
		return is_array( $value ) ? array_values( array_filter( $value, 'is_string' ) ) : array();
	}

	/**
	 * The labels of a custom post type.
	 *
	 * @param  string $plural   Plural label.
	 * @param  string $singular Singular label.
	 * @return array<string, string>
	 */
	private function post_type_labels( string $plural, string $singular ): array {
		/* translators: %s: singular post type name */
		$s = fn( string $text ): string => sprintf( $text, $singular );
		/* translators: %s: plural post type name */
		$p = fn( string $text ): string => sprintf( $text, $plural );

		return array(
			'name'                     => $plural,
			'singular_name'            => $singular,
			'menu_name'                => $plural,
			/* translators: %s: singular post type name */
			'add_new'                  => $s( __( 'Add %s', 'custom-post-type-maker' ) ),
			/* translators: %s: singular post type name */
			'add_new_item'             => $s( __( 'Add New %s', 'custom-post-type-maker' ) ),
			/* translators: %s: singular post type name */
			'edit_item'                => $s( __( 'Edit %s', 'custom-post-type-maker' ) ),
			/* translators: %s: singular post type name */
			'new_item'                 => $s( __( 'New %s', 'custom-post-type-maker' ) ),
			/* translators: %s: singular post type name */
			'view_item'                => $s( __( 'View %s', 'custom-post-type-maker' ) ),
			/* translators: %s: plural post type name */
			'view_items'               => $p( __( 'View %s', 'custom-post-type-maker' ) ),
			/* translators: %s: plural post type name */
			'search_items'             => $p( __( 'Search %s', 'custom-post-type-maker' ) ),
			/* translators: %s: plural post type name */
			'not_found'                => $p( __( 'No %s found', 'custom-post-type-maker' ) ),
			/* translators: %s: plural post type name */
			'not_found_in_trash'       => $p( __( 'No %s found in Trash', 'custom-post-type-maker' ) ),
			/* translators: %s: singular post type name */
			'parent_item_colon'        => $s( __( 'Parent %s:', 'custom-post-type-maker' ) ),
			/* translators: %s: plural post type name */
			'all_items'                => $p( __( 'All %s', 'custom-post-type-maker' ) ),
			/* translators: %s: singular post type name */
			'archives'                 => $s( __( '%s Archives', 'custom-post-type-maker' ) ),
			/* translators: %s: singular post type name */
			'attributes'               => $s( __( '%s Attributes', 'custom-post-type-maker' ) ),
			/* translators: %s: singular post type name */
			'insert_into_item'         => $s( __( 'Insert into %s', 'custom-post-type-maker' ) ),
			/* translators: %s: singular post type name */
			'uploaded_to_this_item'    => $s( __( 'Uploaded to this %s', 'custom-post-type-maker' ) ),
			'featured_image'           => __( 'Featured image', 'custom-post-type-maker' ),
			'set_featured_image'       => __( 'Set featured image', 'custom-post-type-maker' ),
			'remove_featured_image'    => __( 'Remove featured image', 'custom-post-type-maker' ),
			'use_featured_image'       => __( 'Use as featured image', 'custom-post-type-maker' ),
			/* translators: %s: plural post type name */
			'filter_items_list'        => $p( __( 'Filter %s list', 'custom-post-type-maker' ) ),
			'filter_by_date'           => __( 'Filter by date', 'custom-post-type-maker' ),
			/* translators: %s: plural post type name */
			'items_list_navigation'    => $p( __( '%s list navigation', 'custom-post-type-maker' ) ),
			/* translators: %s: plural post type name */
			'items_list'               => $p( __( '%s list', 'custom-post-type-maker' ) ),
			/* translators: %s: singular post type name */
			'item_published'           => $s( __( '%s published.', 'custom-post-type-maker' ) ),
			/* translators: %s: singular post type name */
			'item_published_privately' => $s( __( '%s published privately.', 'custom-post-type-maker' ) ),
			/* translators: %s: singular post type name */
			'item_reverted_to_draft'   => $s( __( '%s reverted to draft.', 'custom-post-type-maker' ) ),
			/* translators: %s: singular post type name */
			'item_trashed'             => $s( __( '%s trashed.', 'custom-post-type-maker' ) ),
			/* translators: %s: singular post type name */
			'item_scheduled'           => $s( __( '%s scheduled.', 'custom-post-type-maker' ) ),
			/* translators: %s: singular post type name */
			'item_updated'             => $s( __( '%s updated.', 'custom-post-type-maker' ) ),
			/* translators: %s: singular post type name */
			'item_link'                => $s( __( '%s Link', 'custom-post-type-maker' ) ),
			/* translators: %s: singular post type name */
			'item_link_description'    => $s( __( 'A link to a %s.', 'custom-post-type-maker' ) ),
			/* translators: %s: plural post type name */
			'template_name'            => $p( __( 'Single item: %s', 'custom-post-type-maker' ) ),
		);
	}

	/**
	 * The labels of a custom taxonomy.
	 *
	 * @param  string $plural   Plural label.
	 * @param  string $singular Singular label.
	 * @return array<string, string>
	 */
	private function taxonomy_labels( string $plural, string $singular ): array {
		$s = fn( string $text ): string => sprintf( $text, $singular );
		$p = fn( string $text ): string => sprintf( $text, $plural );

		return array(
			'name'                       => $plural,
			'singular_name'              => $singular,
			'menu_name'                  => $plural,
			/* translators: %s: plural taxonomy name */
			'search_items'               => $p( __( 'Search %s', 'custom-post-type-maker' ) ),
			/* translators: %s: plural taxonomy name */
			'popular_items'              => $p( __( 'Popular %s', 'custom-post-type-maker' ) ),
			/* translators: %s: plural taxonomy name */
			'all_items'                  => $p( __( 'All %s', 'custom-post-type-maker' ) ),
			/* translators: %s: singular taxonomy name */
			'parent_item'                => $s( __( 'Parent %s', 'custom-post-type-maker' ) ),
			/* translators: %s: singular taxonomy name */
			'parent_item_colon'          => $s( __( 'Parent %s:', 'custom-post-type-maker' ) ),
			/* translators: %s: singular taxonomy name */
			'edit_item'                  => $s( __( 'Edit %s', 'custom-post-type-maker' ) ),
			/* translators: %s: singular taxonomy name */
			'view_item'                  => $s( __( 'View %s', 'custom-post-type-maker' ) ),
			/* translators: %s: singular taxonomy name */
			'update_item'                => $s( __( 'Update %s', 'custom-post-type-maker' ) ),
			/* translators: %s: singular taxonomy name */
			'add_new_item'               => $s( __( 'Add New %s', 'custom-post-type-maker' ) ),
			/* translators: %s: singular taxonomy name */
			'new_item_name'              => $s( __( 'New %s Name', 'custom-post-type-maker' ) ),
			/* translators: %s: plural taxonomy name */
			'separate_items_with_commas' => $p( __( 'Separate %s with commas', 'custom-post-type-maker' ) ),
			/* translators: %s: plural taxonomy name */
			'add_or_remove_items'        => $p( __( 'Add or remove %s', 'custom-post-type-maker' ) ),
			/* translators: %s: plural taxonomy name */
			'choose_from_most_used'      => $p( __( 'Choose from the most used %s', 'custom-post-type-maker' ) ),
			/* translators: %s: plural taxonomy name */
			'not_found'                  => $p( __( 'No %s found', 'custom-post-type-maker' ) ),
			/* translators: %s: plural taxonomy name */
			'no_terms'                   => $p( __( 'No %s', 'custom-post-type-maker' ) ),
			/* translators: %s: singular taxonomy name */
			'filter_by_item'             => $s( __( 'Filter by %s', 'custom-post-type-maker' ) ),
			/* translators: %s: plural taxonomy name */
			'items_list_navigation'      => $p( __( '%s list navigation', 'custom-post-type-maker' ) ),
			/* translators: %s: plural taxonomy name */
			'items_list'                 => $p( __( '%s list', 'custom-post-type-maker' ) ),
			'most_used'                  => __( 'Most Used', 'custom-post-type-maker' ),
			/* translators: %s: plural taxonomy name */
			'back_to_items'              => $p( __( '&larr; Go to %s', 'custom-post-type-maker' ) ),
			/* translators: %s: singular taxonomy name */
			'item_link'                  => $s( __( '%s Link', 'custom-post-type-maker' ) ),
			/* translators: %s: singular taxonomy name */
			'item_link_description'      => $s( __( 'A link to a %s.', 'custom-post-type-maker' ) ),
			/* translators: %s: plural taxonomy name */
			'template_name'              => $p( __( '%s Archives', 'custom-post-type-maker' ) ),
		);
	}

	/**
	 * Get all published definitions of one kind.
	 *
	 * @param  string $post_type 'cptm' or 'cptm_tax'.
	 * @return WP_Post[]
	 */
	private function get_definitions( string $post_type ): array {
		return get_posts(
			array(
				'numberposts'      => -1,
				'post_type'        => $post_type,
				'post_status'      => 'publish',
				'suppress_filters' => false,
			)
		);
	}

	/**
	 * Create custom post types and custom taxonomies from the definitions.
	 */
	public function cptm_create_custom_post_types(): void {
		foreach ( $this->get_definitions( 'cptm' ) as $cptm ) {
			$this->register_post_type_definition( get_post_meta( $cptm->ID ) );
		}

		foreach ( $this->get_definitions( 'cptm_tax' ) as $cptm_tax ) {
			$this->register_taxonomy_definition( get_post_meta( $cptm_tax->ID ) );
		}
	}

	/**
	 * Register one custom post type. Reads the meta exactly as 1.2.0 did (C-001).
	 *
	 * @param array $meta All meta of a post type definition.
	 */
	private function register_post_type_definition( array $meta ): void {
		// text (escaped as in 1.2.0, so existing names do not change)
		$name          = esc_html( $this->meta_value( $meta, 'cptm_name' ) ?: 'no_name' );
		$label         = esc_html( $this->meta_value( $meta, 'cptm_label' ) ?: $name );
		$singular_name = esc_html( $this->meta_value( $meta, 'cptm_singular_name' ) ?: $label );
		$description   = $this->meta_value( $meta, 'cptm_description' ) ?: '';

		if ( 'no_name' === $name ) {
			return;
		}

		// A Dashicon slug overrides an uploaded icon (D-004).
		$icon_slug = $this->meta_value( $meta, 'cptm_icon_slug' );
		$icon_url  = $this->meta_value( $meta, 'cptm_icon_url' );
		$menu_icon = $icon_slug ? sanitize_html_class( $icon_slug ) : ( $icon_url ? esc_url_raw( $icon_url ) : null );

		$custom_rewrite_slug = esc_html( $this->meta_value( $meta, 'cptm_custom_rewrite_slug' ) ?: $name );
		$menu_position       = $this->meta_value( $meta, 'cptm_menu_position' ) ? (int) $this->meta_value( $meta, 'cptm_menu_position' ) : null;
		$has_archive         = $this->meta_flag( $meta, 'cptm_has_archive' );
		$archive_slug        = $this->meta_value( $meta, 'cptm_archive_slug' );

		// Rewrite: with_front is always set, also when false (D-005).
		$rewrite = array();
		if ( $this->meta_flag( $meta, 'cptm_rewrite' ) ) {
			$rewrite['slug'] = $custom_rewrite_slug;
		}
		$rewrite['with_front'] = $this->meta_flag( $meta, 'cptm_withfront' );
		if ( $this->meta_flag( $meta, 'cptm_feeds' ) ) {
			$rewrite['feeds'] = true;
		}
		if ( $this->meta_flag( $meta, 'cptm_pages' ) ) {
			$rewrite['pages'] = true;
		}

		// A missing supports key means title + editor.
		$supports_raw = $this->meta_value( $meta, 'cptm_supports' );
		$supports     = $supports_raw ? $this->meta_array( $supports_raw ) : array( 'title', 'editor' );

		$args = array(
			'labels'              => $this->post_type_labels( $label, $singular_name ),
			'description'         => $description,
			'menu_icon'           => $menu_icon,
			'rewrite'             => $rewrite,
			'menu_position'       => $menu_position,
			'public'              => $this->meta_flag( $meta, 'cptm_public' ),
			'show_ui'             => $this->meta_flag( $meta, 'cptm_show_ui' ),
			'has_archive'         => ( $has_archive && $archive_slug ) ? $archive_slug : $has_archive,
			'exclude_from_search' => $this->meta_flag( $meta, 'cptm_exclude_from_search' ),
			'capability_type'     => $this->meta_value( $meta, 'cptm_capability_type' ) ?: 'post',
			'hierarchical'        => $this->meta_flag( $meta, 'cptm_hierarchical' ),
			'show_in_menu'        => $this->meta_flag( $meta, 'cptm_show_in_menu' ),
			'query_var'           => $this->meta_flag( $meta, 'cptm_query_var' ),
			'show_in_rest'        => $this->meta_flag( $meta, 'cptm_show_in_rest' ),
			// A missing key means true (D-002).
			'publicly_queryable'  => $this->meta_flag( $meta, 'cptm_publicly_queryable', true ),
			'supports'            => $supports,
			'taxonomies'          => $this->meta_array( $this->meta_value( $meta, 'cptm_builtin_taxonomies' ) ),
		);

		// Settings added in 1.3.0: pass them only when set, so older definitions keep the WordPress defaults.
		foreach ( array(
			'cptm_show_in_nav_menus' => 'show_in_nav_menus',
			'cptm_show_in_admin_bar' => 'show_in_admin_bar',
			'cptm_can_export'        => 'can_export',
			'cptm_delete_with_user'  => 'delete_with_user',
		) as $key => $arg ) {
			$value = $this->meta_optional_flag( $meta, $key );
			if ( null !== $value ) {
				$args[ $arg ] = $value;
			}
		}
		foreach ( array(
			'cptm_rest_base'      => 'rest_base',
			'cptm_rest_namespace' => 'rest_namespace',
		) as $key => $arg ) {
			$value = $this->meta_value( $meta, $key );
			if ( $value ) {
				$args[ $arg ] = $value;
			}
		}

		register_post_type( $name, $args );
	}

	/**
	 * Register one custom taxonomy. Reads the meta exactly as 1.2.0 did (C-001).
	 *
	 * @param array $meta All meta of a taxonomy definition.
	 */
	private function register_taxonomy_definition( array $meta ): void {
		$name          = esc_html( $this->meta_value( $meta, 'cptm_tax_name' ) ?: 'no_name' );
		$label         = esc_html( $this->meta_value( $meta, 'cptm_tax_label' ) ?: $name );
		$singular_name = esc_html( $this->meta_value( $meta, 'cptm_tax_singular_name' ) ?: $label );
		$rewrite_slug  = esc_html( $this->meta_value( $meta, 'cptm_tax_custom_rewrite_slug' ) ?: $name );

		if ( 'no_name' === $name ) {
			return;
		}

		$args = array(
			'label'             => $label,
			'labels'            => $this->taxonomy_labels( $label, $singular_name ),
			'rewrite'           => $this->meta_flag( $meta, 'cptm_tax_rewrite' ) ? array( 'slug' => $rewrite_slug ) : false,
			'show_ui'           => $this->meta_flag( $meta, 'cptm_tax_show_ui' ),
			'hierarchical'      => $this->meta_flag( $meta, 'cptm_tax_hierarchical' ),
			'query_var'         => $this->meta_flag( $meta, 'cptm_tax_query_var' ),
			'show_in_rest'      => $this->meta_flag( $meta, 'cptm_tax_show_in_rest' ),
			'show_admin_column' => $this->meta_flag( $meta, 'cptm_tax_show_admin_column' ),
		);

		// Settings added in 1.3.0: pass them only when set.
		foreach ( array(
			'cptm_tax_public'             => 'public',
			'cptm_tax_publicly_queryable' => 'publicly_queryable',
			'cptm_tax_show_in_nav_menus'  => 'show_in_nav_menus',
			'cptm_tax_show_tagcloud'      => 'show_tagcloud',
			'cptm_tax_show_in_quick_edit' => 'show_in_quick_edit',
		) as $key => $arg ) {
			$value = $this->meta_optional_flag( $meta, $key );
			if ( null !== $value ) {
				$args[ $arg ] = $value;
			}
		}
		$rest_base = $this->meta_value( $meta, 'cptm_tax_rest_base' );
		if ( $rest_base ) {
			$args['rest_base'] = $rest_base;
		}
		$default_term = $this->meta_value( $meta, 'cptm_tax_default_term' );
		if ( $default_term ) {
			$args['default_term'] = array( 'name' => $default_term );
		}

		register_taxonomy( $name, $this->meta_array( $this->meta_value( $meta, 'cptm_tax_post_types' ) ), $args );
	}

	/**
	 * Create admin meta boxes
	 */
	public function cptm_create_meta_boxes(): void {
		// add options meta box
		add_meta_box(
			'cptm_options',
			__( 'Options', 'custom-post-type-maker' ),
			array( $this, 'cptm_meta_box' ),
			'cptm',
			'advanced',
			'high'
		);
		add_meta_box(
			'cptm_tax_options',
			__( 'Options', 'custom-post-type-maker' ),
			array( $this, 'cptm_tax_meta_box' ),
			'cptm_tax',
			'advanced',
			'high'
		);
	}

	/**
	 * Print a section heading row.
	 *
	 * @param string $title Section title.
	 */
	private function section_row( string $title ): void {
		?>
		<tr>
			<td colspan="2" class="section">
				<h3><?php echo esc_html( $title ); ?></h3>
			</td>
		</tr>
		<?php
	}

	/**
	 * Print the label cell of a row.
	 *
	 * @param string   $id       Field ID.
	 * @param string   $label    Field label.
	 * @param string[] $help     Help paragraphs (plain text).
	 * @param bool     $top      Align the label to the top.
	 * @param bool     $required Mark the field as required.
	 */
	private function label_cell( string $id, string $label, array $help, bool $top = false, bool $required = false ): void {
		?>
		<td class="label<?php echo $top ? ' top' : ''; ?>">
			<label for="<?php echo esc_attr( $id ); ?>"><?php if ( $required ) { ?><span class="required">*</span> <?php } ?><?php echo esc_html( $label ); ?></label>
			<?php foreach ( $help as $paragraph ) { ?>
				<p><?php echo esc_html( $paragraph ); ?></p>
			<?php } ?>
		</td>
		<?php
	}

	/**
	 * Print a text input row.
	 *
	 * @param string   $id       Field ID and name.
	 * @param string   $label    Field label.
	 * @param string[] $help     Help paragraphs.
	 * @param string   $value    Current value (raw).
	 * @param bool     $required Mark the field as required.
	 */
	private function text_row( string $id, string $label, array $help, string $value, bool $required = false ): void {
		?>
		<tr>
			<?php $this->label_cell( $id, $label, $help, false, $required ); ?>
			<td>
				<input type="text" name="<?php echo esc_attr( $id ); ?>" id="<?php echo esc_attr( $id ); ?>" class="widefat" tabindex="<?php echo (int) $this->tab++; ?>" value="<?php echo esc_attr( $value ); ?>" />
			</td>
		</tr>
		<?php
	}

	/**
	 * Print a True / False select row (settings from 1.2.0 and earlier).
	 *
	 * @param string   $id      Field ID and name.
	 * @param string   $label   Field label.
	 * @param string[] $help    Help paragraphs.
	 * @param string   $value   Current value: '1', '0' or '' (not saved yet).
	 * @param bool     $default The default value, listed first.
	 */
	private function bool_row( string $id, string $label, array $help, string $value, bool $default ): void {
		$options = $default ? array( '1', '0' ) : array( '0', '1' );
		?>
		<tr>
			<?php $this->label_cell( $id, $label, $help ); ?>
			<td>
				<select name="<?php echo esc_attr( $id ); ?>" id="<?php echo esc_attr( $id ); ?>" tabindex="<?php echo (int) $this->tab++; ?>">
					<?php foreach ( $options as $i => $option ) { ?>
						<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $value, $option ); ?>><?php echo '1' === $option ? esc_html__( 'True', 'custom-post-type-maker' ) : esc_html__( 'False', 'custom-post-type-maker' ); ?><?php if ( 0 === $i ) { ?> (<?php esc_html_e( 'default', 'custom-post-type-maker' ); ?>)<?php } ?></option>
					<?php } ?>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Print a WordPress default / True / False select row (settings added in 1.3.0).
	 *
	 * @param string   $id    Field ID and name.
	 * @param string   $label Field label.
	 * @param string[] $help  Help paragraphs.
	 * @param string   $value Current value: '', '1' or '0'.
	 */
	private function optional_bool_row( string $id, string $label, array $help, string $value ): void {
		?>
		<tr>
			<?php $this->label_cell( $id, $label, $help ); ?>
			<td>
				<select name="<?php echo esc_attr( $id ); ?>" id="<?php echo esc_attr( $id ); ?>" tabindex="<?php echo (int) $this->tab++; ?>">
					<option value="" <?php selected( $value, '' ); ?>><?php esc_html_e( 'WordPress default', 'custom-post-type-maker' ); ?></option>
					<option value="1" <?php selected( $value, '1' ); ?>><?php esc_html_e( 'True', 'custom-post-type-maker' ); ?></option>
					<option value="0" <?php selected( $value, '0' ); ?>><?php esc_html_e( 'False', 'custom-post-type-maker' ); ?></option>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Print a checkbox list row.
	 *
	 * @param string                $id      Field name (without []).
	 * @param string                $label   Field label.
	 * @param array<string, string> $choices Value => label.
	 * @param string[]              $checked Checked values.
	 * @param string[]              $help    Help paragraphs.
	 * @param string[]              $defaults Values marked "(default)".
	 */
	private function checkbox_row( string $id, string $label, array $choices, array $checked, array $help = array(), array $defaults = array() ): void {
		?>
		<tr>
			<?php $this->label_cell( $id, $label, $help ? $help : array( "\u{00a0}" ), true ); ?>
			<td>
				<?php
				foreach ( $choices as $value => $choice ) {
					$field_id = $id . '_' . str_replace( '-', '_', $value );
					?>
					<input type="checkbox" tabindex="<?php echo (int) $this->tab++; ?>" name="<?php echo esc_attr( $id ); ?>[]" id="<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $checked, true ) ); ?> /> <label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $choice ); ?><?php if ( in_array( $value, $defaults, true ) ) { ?> <span class="default">(<?php esc_html_e( 'default', 'custom-post-type-maker' ); ?>)</span><?php } ?></label><br />
				<?php } ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Create custom post meta box
	 *
	 * @param WP_Post $post WordPress $post object
	 */
	public function cptm_meta_box( WP_Post $post ): void {
		global $pagenow;

		$meta     = get_post_meta( $post->ID );
		$v        = fn( string $key ): string => (string) $this->meta_value( $meta, $key );
		$is_new   = 'post-new.php' === $pagenow;
		$icon_url = $v( 'cptm_icon_url' );
		$icon     = $v( 'cptm_icon_slug' );

		$supports = $is_new ? array( 'title', 'editor', 'excerpt' ) : $this->meta_array( $this->meta_value( $meta, 'cptm_supports' ) );
		$taxs     = $this->meta_array( $this->meta_value( $meta, 'cptm_builtin_taxonomies' ) );

		$this->tab = 1;
		wp_nonce_field( 'cptm_meta_box_nonce_action', 'cptm_meta_box_nonce_field' );
		?>
		<table class="cptm">
			<?php
			$this->text_row( 'cptm_name', __( 'Custom Post Type Name', 'custom-post-type-maker' ), array( __( 'The post type name. Used to retrieve custom post type content. Lower-case letters, numbers, dashes and underscores only, at most 20 characters.', 'custom-post-type-maker' ), __( 'e.g. movies', 'custom-post-type-maker' ) ), $v( 'cptm_name' ), true );
			$this->text_row( 'cptm_label', __( 'Label', 'custom-post-type-maker' ), array( __( 'A plural descriptive name for the post type.', 'custom-post-type-maker' ), __( 'e.g. Movies', 'custom-post-type-maker' ) ), $v( 'cptm_label' ) );
			$this->text_row( 'cptm_singular_name', __( 'Singular Name', 'custom-post-type-maker' ), array( __( 'A singular descriptive name for the post type.', 'custom-post-type-maker' ), __( 'e.g. Movie', 'custom-post-type-maker' ) ), $v( 'cptm_singular_name' ) );
			?>
			<tr>
				<?php $this->label_cell( 'cptm_description', __( 'Description', 'custom-post-type-maker' ), array( __( 'A short descriptive summary of what the post type is.', 'custom-post-type-maker' ) ), true ); ?>
				<td>
					<textarea name="cptm_description" id="cptm_description" class="widefat" tabindex="<?php echo (int) $this->tab++; ?>" rows="4"><?php echo esc_textarea( wp_specialchars_decode( $v( 'cptm_description' ), ENT_QUOTES ) ); ?></textarea>
				</td>
			</tr>
			<?php
			$this->section_row( __( 'Visibility', 'custom-post-type-maker' ) );
			$this->bool_row( 'cptm_public', __( 'Public', 'custom-post-type-maker' ), array( __( 'Whether a post type is intended to be used publicly either via the admin interface or by front-end users.', 'custom-post-type-maker' ) ), $v( 'cptm_public' ), true );

			$this->section_row( __( 'Rewrite Options', 'custom-post-type-maker' ) );
			$this->bool_row( 'cptm_rewrite', __( 'Rewrite', 'custom-post-type-maker' ), array( __( 'Triggers the handling of rewrites for this post type.', 'custom-post-type-maker' ) ), $v( 'cptm_rewrite' ), true );
			$this->bool_row( 'cptm_withfront', __( 'With Front', 'custom-post-type-maker' ), array( __( 'Should the permastruct be prepended with the front base.', 'custom-post-type-maker' ) ), $v( 'cptm_withfront' ), true );
			$this->text_row( 'cptm_custom_rewrite_slug', __( 'Custom Rewrite Slug', 'custom-post-type-maker' ), array( __( 'Customize the permastruct slug.', 'custom-post-type-maker' ), __( 'Default: [Custom Post Type Name]', 'custom-post-type-maker' ) ), $v( 'cptm_custom_rewrite_slug' ) );

			$this->section_row( __( 'Front-end Options', 'custom-post-type-maker' ) );
			$this->bool_row( 'cptm_feeds', __( 'Feeds', 'custom-post-type-maker' ), array( __( 'Should a feed permastruct be built for this post type. Defaults to "has_archive" value.', 'custom-post-type-maker' ) ), $v( 'cptm_feeds' ), false );
			$this->bool_row( 'cptm_pages', __( 'Pages', 'custom-post-type-maker' ), array( __( 'Should the permastruct provide for pagination.', 'custom-post-type-maker' ) ), $v( 'cptm_pages' ), true );
			$this->bool_row( 'cptm_exclude_from_search', __( 'Exclude From Search', 'custom-post-type-maker' ), array( __( 'Whether to exclude posts with this post type from front end search results.', 'custom-post-type-maker' ) ), $v( 'cptm_exclude_from_search' ), false );
			$this->bool_row( 'cptm_has_archive', __( 'Has Archive', 'custom-post-type-maker' ), array( __( 'Enables post type archives.', 'custom-post-type-maker' ) ), $v( 'cptm_has_archive' ), false );
			$this->text_row( 'cptm_archive_slug', __( 'Archive Slug', 'custom-post-type-maker' ), array( __( 'The URL slug of the archive. "Has Archive" must be true.', 'custom-post-type-maker' ), __( 'Default: [Custom Rewrite Slug]', 'custom-post-type-maker' ) ), $v( 'cptm_archive_slug' ) );

			$this->section_row( __( 'Admin Menu Options', 'custom-post-type-maker' ) );
			$this->bool_row( 'cptm_show_ui', __( 'Show UI', 'custom-post-type-maker' ), array( __( 'Whether to generate a default UI for managing this post type in the admin.', 'custom-post-type-maker' ) ), $v( 'cptm_show_ui' ), true );
			$this->text_row( 'cptm_menu_position', __( 'Menu Position', 'custom-post-type-maker' ), array( __( 'The position in the menu order the post type should appear. "Show in Menu" must be true.', 'custom-post-type-maker' ) ), $v( 'cptm_menu_position' ) );
			$this->bool_row( 'cptm_show_in_menu', __( 'Show in Menu', 'custom-post-type-maker' ), array( __( 'Where to show the post type in the admin menu. "Show UI" must be true.', 'custom-post-type-maker' ) ), $v( 'cptm_show_in_menu' ), true );
			$this->optional_bool_row( 'cptm_show_in_nav_menus', __( 'Show in Navigation Menus', 'custom-post-type-maker' ), array( __( 'Makes this post type available for selection in navigation menus.', 'custom-post-type-maker' ) ), $v( 'cptm_show_in_nav_menus' ) );
			$this->optional_bool_row( 'cptm_show_in_admin_bar', __( 'Show in Admin Bar', 'custom-post-type-maker' ), array( __( 'Makes this post type available via the admin bar.', 'custom-post-type-maker' ) ), $v( 'cptm_show_in_admin_bar' ) );
			?>
			<tr>
				<?php $this->label_cell( 'current-cptm-icon', __( 'Icon', 'custom-post-type-maker' ), array( __( 'This icon will be overridden if a Dash Icon is specified in the field below.', 'custom-post-type-maker' ) ) ); ?>
				<td>
					<div class="cptm-icon">
						<div class="current-cptm-icon"><?php if ( $icon_url ) { ?><img src="<?php echo esc_url( $icon_url ); ?>" alt="" /><?php } ?></div>
						<a href="#" class="remove-cptm-icon button-secondary"<?php if ( ! $icon_url ) { ?> style="display: none;"<?php } ?> tabindex="<?php echo (int) $this->tab++; ?>"><?php esc_html_e( 'Remove Icon', 'custom-post-type-maker' ); ?></a>
						<a href="#" class="media-uploader-button button-primary" data-post-id="<?php echo (int) $post->ID; ?>" tabindex="<?php echo (int) $this->tab++; ?>"><?php echo $icon_url ? esc_html__( 'Upload Icon', 'custom-post-type-maker' ) : esc_html__( 'Add icon', 'custom-post-type-maker' ); ?></a>
					</div>
					<input type="hidden" name="cptm_icon_url" id="cptm_icon_url" class="widefat" value="<?php echo esc_url( $icon_url ); ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_icon_slug"><?php esc_html_e( 'Slug Icon', 'custom-post-type-maker' ); ?></label>
					<p>
						<?php
						printf(
							/* translators: %s: link to the Dashicons list */
							esc_html__( 'This uses %s and overrides the uploaded icon above.', 'custom-post-type-maker' ),
							'<a href="https://developer.wordpress.org/resource/dashicons/" target="_blank" rel="noopener">' . esc_html__( 'Dashicons', 'custom-post-type-maker' ) . '</a>'
						);
						?>
					</p>
				</td>
				<td>
					<?php if ( $icon ) { ?><span id="cptm_icon_slug_before" class="dashicons-before <?php echo esc_attr( sanitize_html_class( $icon ) ); ?>"></span><?php } ?>
					<input type="text" name="cptm_icon_slug" id="cptm_icon_slug" class="widefat" tabindex="<?php echo (int) $this->tab++; ?>" value="<?php echo esc_attr( $icon ); ?>" />
				</td>
			</tr>
			<?php
			$this->section_row( __( 'WordPress Integration', 'custom-post-type-maker' ) );
			?>
			<tr>
				<?php $this->label_cell( 'cptm_capability_type', __( 'Capability Type', 'custom-post-type-maker' ), array( __( 'The post type to use to build the read, edit, and delete capabilities.', 'custom-post-type-maker' ) ) ); ?>
				<td>
					<select name="cptm_capability_type" id="cptm_capability_type" tabindex="<?php echo (int) $this->tab++; ?>">
						<option value="post" <?php selected( $v( 'cptm_capability_type' ), 'post' ); ?>><?php esc_html_e( 'Post', 'custom-post-type-maker' ); ?> (<?php esc_html_e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="page" <?php selected( $v( 'cptm_capability_type' ), 'page' ); ?>><?php esc_html_e( 'Page', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<?php
			$this->bool_row( 'cptm_hierarchical', __( 'Hierarchical', 'custom-post-type-maker' ), array( __( 'Whether the post type is hierarchical (e.g. page).', 'custom-post-type-maker' ) ), $v( 'cptm_hierarchical' ), false );
			$this->bool_row( 'cptm_query_var', __( 'Query Var', 'custom-post-type-maker' ), array( __( 'Sets the query_var key for this post type.', 'custom-post-type-maker' ) ), $v( 'cptm_query_var' ), true );
			$this->bool_row( 'cptm_show_in_rest', __( 'Show in REST', 'custom-post-type-maker' ), array( __( 'Sets the show_in_rest key for this post type. The block editor needs it.', 'custom-post-type-maker' ) ), $v( 'cptm_show_in_rest' ), true );
			$this->text_row( 'cptm_rest_base', __( 'REST Base', 'custom-post-type-maker' ), array( __( 'The base of the REST API route.', 'custom-post-type-maker' ), __( 'Default: [Custom Post Type Name]', 'custom-post-type-maker' ) ), $v( 'cptm_rest_base' ) );
			$this->text_row( 'cptm_rest_namespace', __( 'REST Namespace', 'custom-post-type-maker' ), array( __( 'The namespace of the REST API route.', 'custom-post-type-maker' ), __( 'Default: wp/v2', 'custom-post-type-maker' ) ), $v( 'cptm_rest_namespace' ) );
			$this->bool_row( 'cptm_publicly_queryable', __( 'Publicly Queryable', 'custom-post-type-maker' ), array( __( 'Whether the post is visible on the front-end.', 'custom-post-type-maker' ) ), $v( 'cptm_publicly_queryable' ), true );
			$this->optional_bool_row( 'cptm_can_export', __( 'Can Export', 'custom-post-type-maker' ), array( __( 'Whether the content of this post type can be exported with the WordPress export tool.', 'custom-post-type-maker' ) ), $v( 'cptm_can_export' ) );
			$this->optional_bool_row( 'cptm_delete_with_user', __( 'Delete With User', 'custom-post-type-maker' ), array( __( 'Whether to delete the posts of a user when the user is deleted.', 'custom-post-type-maker' ) ), $v( 'cptm_delete_with_user' ) );

			$this->checkbox_row(
				'cptm_supports',
				__( 'Supports', 'custom-post-type-maker' ),
				array(
					'title'           => __( 'Title', 'custom-post-type-maker' ),
					'editor'          => __( 'Editor', 'custom-post-type-maker' ),
					'excerpt'         => __( 'Excerpt', 'custom-post-type-maker' ),
					'trackbacks'      => __( 'Trackbacks', 'custom-post-type-maker' ),
					'custom-fields'   => __( 'Custom Fields', 'custom-post-type-maker' ),
					'comments'        => __( 'Comments', 'custom-post-type-maker' ),
					'revisions'       => __( 'Revisions', 'custom-post-type-maker' ),
					'thumbnail'       => __( 'Featured Image', 'custom-post-type-maker' ),
					'author'          => __( 'Author', 'custom-post-type-maker' ),
					'page-attributes' => __( 'Page Attributes', 'custom-post-type-maker' ),
					'post-formats'    => __( 'Post Formats', 'custom-post-type-maker' ),
				),
				$supports,
				array( __( 'Adds the respective meta boxes when creating content for this Custom Post Type.', 'custom-post-type-maker' ) ),
				array( 'title', 'editor', 'excerpt' )
			);
			$this->checkbox_row(
				'cptm_builtin_taxonomies',
				__( 'Built-in Taxonomies', 'custom-post-type-maker' ),
				array(
					'category' => __( 'Categories', 'custom-post-type-maker' ),
					'post_tag' => __( 'Tags', 'custom-post-type-maker' ),
				),
				$taxs
			);
			?>
		</table>
		<?php
	}

	/**
	 * Create custom post taxonomy meta box
	 *
	 * @param WP_Post $post WordPress $post object
	 */
	public function cptm_tax_meta_box( WP_Post $post ): void {
		$meta       = get_post_meta( $post->ID );
		$v          = fn( string $key ): string => (string) $this->meta_value( $meta, $key );
		$post_types = $this->meta_array( $this->meta_value( $meta, 'cptm_tax_post_types' ) );

		$choices = array(
			'post' => __( 'Posts', 'custom-post-type-maker' ),
			'page' => __( 'Pages', 'custom-post-type-maker' ),
		);
		foreach ( get_post_types( array( 'public' => true, '_builtin' => false ), 'objects' ) as $post_type ) {
			$choices[ $post_type->name ] = $post_type->labels->name;
		}

		$this->tab = 1;
		wp_nonce_field( 'cptm_meta_box_nonce_action', 'cptm_meta_box_nonce_field' );
		?>
		<table class="cptm">
			<?php
			$this->text_row( 'cptm_tax_name', __( 'Custom Taxonomy Name', 'custom-post-type-maker' ), array( __( 'The taxonomy name. Used to retrieve custom taxonomy content. Lower-case letters, numbers, dashes and underscores only, at most 32 characters.', 'custom-post-type-maker' ), __( 'e.g. genres', 'custom-post-type-maker' ) ), $v( 'cptm_tax_name' ), true );
			$this->text_row( 'cptm_tax_label', __( 'Label', 'custom-post-type-maker' ), array( __( 'A plural descriptive name for the taxonomy.', 'custom-post-type-maker' ), __( 'e.g. Genres', 'custom-post-type-maker' ) ), $v( 'cptm_tax_label' ) );
			$this->text_row( 'cptm_tax_singular_name', __( 'Singular Name', 'custom-post-type-maker' ), array( __( 'A singular descriptive name for the taxonomy.', 'custom-post-type-maker' ), __( 'e.g. Genre', 'custom-post-type-maker' ) ), $v( 'cptm_tax_singular_name' ) );
			$this->bool_row( 'cptm_tax_show_ui', __( 'Show UI', 'custom-post-type-maker' ), array( __( 'Whether to generate a default UI for managing this taxonomy in the admin.', 'custom-post-type-maker' ) ), $v( 'cptm_tax_show_ui' ), true );
			$this->optional_bool_row( 'cptm_tax_public', __( 'Public', 'custom-post-type-maker' ), array( __( 'Whether the taxonomy is intended for use publicly either via the admin interface or by front-end users.', 'custom-post-type-maker' ) ), $v( 'cptm_tax_public' ) );
			$this->optional_bool_row( 'cptm_tax_publicly_queryable', __( 'Publicly Queryable', 'custom-post-type-maker' ), array( __( 'Whether the taxonomy is publicly queryable on the front-end.', 'custom-post-type-maker' ) ), $v( 'cptm_tax_publicly_queryable' ) );
			$this->optional_bool_row( 'cptm_tax_show_in_nav_menus', __( 'Show in Navigation Menus', 'custom-post-type-maker' ), array( __( 'Makes this taxonomy available for selection in navigation menus.', 'custom-post-type-maker' ) ), $v( 'cptm_tax_show_in_nav_menus' ) );
			$this->optional_bool_row( 'cptm_tax_show_tagcloud', __( 'Show in Tag Cloud', 'custom-post-type-maker' ), array( __( 'Whether to list the taxonomy in the Tag Cloud widget.', 'custom-post-type-maker' ) ), $v( 'cptm_tax_show_tagcloud' ) );
			$this->optional_bool_row( 'cptm_tax_show_in_quick_edit', __( 'Show in Quick Edit', 'custom-post-type-maker' ), array( __( 'Whether to show the taxonomy in the quick and bulk edit panels.', 'custom-post-type-maker' ) ), $v( 'cptm_tax_show_in_quick_edit' ) );
			$this->bool_row( 'cptm_tax_hierarchical', __( 'Hierarchical', 'custom-post-type-maker' ), array( __( 'Whether the taxonomy is hierarchical (e.g. category).', 'custom-post-type-maker' ) ), $v( 'cptm_tax_hierarchical' ), false );
			$this->bool_row( 'cptm_tax_rewrite', __( 'Rewrite', 'custom-post-type-maker' ), array( __( 'Triggers the handling of rewrites for this taxonomy.', 'custom-post-type-maker' ) ), $v( 'cptm_tax_rewrite' ), true );
			$this->text_row( 'cptm_tax_custom_rewrite_slug', __( 'Custom Rewrite Slug', 'custom-post-type-maker' ), array( __( 'Customize the permastruct slug.', 'custom-post-type-maker' ), __( 'Default: [Custom Taxonomy Name]', 'custom-post-type-maker' ) ), $v( 'cptm_tax_custom_rewrite_slug' ) );
			$this->bool_row( 'cptm_tax_query_var', __( 'Query Var', 'custom-post-type-maker' ), array( __( 'Sets the query_var key for this taxonomy.', 'custom-post-type-maker' ) ), $v( 'cptm_tax_query_var' ), true );
			$this->bool_row( 'cptm_tax_show_in_rest', __( 'Show in REST', 'custom-post-type-maker' ), array( __( 'Sets the show_in_rest key for this taxonomy. The block editor needs it.', 'custom-post-type-maker' ) ), $v( 'cptm_tax_show_in_rest' ), true );
			$this->text_row( 'cptm_tax_rest_base', __( 'REST Base', 'custom-post-type-maker' ), array( __( 'The base of the REST API route.', 'custom-post-type-maker' ), __( 'Default: [Custom Taxonomy Name]', 'custom-post-type-maker' ) ), $v( 'cptm_tax_rest_base' ) );
			$this->bool_row( 'cptm_tax_show_admin_column', __( 'Admin Column', 'custom-post-type-maker' ), array( __( 'Show this taxonomy as a column in the custom post listing.', 'custom-post-type-maker' ) ), $v( 'cptm_tax_show_admin_column' ), true );
			$this->text_row( 'cptm_tax_default_term', __( 'Default Term', 'custom-post-type-maker' ), array( __( 'The name of a term that WordPress creates and assigns to new posts. Leave empty for none.', 'custom-post-type-maker' ) ), $v( 'cptm_tax_default_term' ) );
			$this->checkbox_row( 'cptm_tax_post_types', __( 'Post Types', 'custom-post-type-maker' ), $choices, $post_types );
			?>
		</table>
		<?php
	}

	/**
	 * Check that a save request may write definition meta.
	 *
	 * @param  int $post_id WordPress Post ID
	 * @return bool
	 */
	private function can_save( int $post_id ): bool {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return false;
		}
		$nonce = isset( $_POST['cptm_meta_box_nonce_field'] ) ? sanitize_text_field( wp_unslash( $_POST['cptm_meta_box_nonce_field'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'cptm_meta_box_nonce_action' ) ) {
			return false;
		}
		return current_user_can( self::CAPABILITY );
	}

	/**
	 * A posted scalar value, unslashed. Null when it was not posted.
	 *
	 * @param  string $key Field name.
	 * @return string|null
	 */
	private function posted( string $key ): ?string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in can_save().
		if ( ! isset( $_POST[ $key ] ) || ! is_scalar( $_POST[ $key ] ) ) {
			return null;
		}
		return (string) wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by each caller.
	}

	/**
	 * A posted list of values, unslashed, keys sanitized, limited to $allowed (or all keys when null).
	 *
	 * @param  string        $key     Field name.
	 * @param  string[]|null $allowed Allowed values.
	 * @return string[]
	 */
	private function posted_list( string $key, ?array $allowed ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in can_save().
		$raw = isset( $_POST[ $key ] ) && is_array( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.
		$out = array();
		foreach ( $raw as $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}
			$value = sanitize_key( (string) $value );
			if ( '' !== $value && ( null === $allowed || in_array( $value, $allowed, true ) ) ) {
				$out[] = $value;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Save posted '1'/'0' flags. Other values are ignored.
	 *
	 * @param int      $post_id WordPress Post ID
	 * @param string[] $keys    Field names.
	 * @param bool     $allow_empty Also accept '' (WordPress default) for settings added in 1.3.0.
	 */
	private function save_flags( int $post_id, array $keys, bool $allow_empty = false ): void {
		foreach ( $keys as $key ) {
			$value = $this->posted( $key );
			if ( '1' === $value || '0' === $value || ( $allow_empty && '' === $value ) ) {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}

	/**
	 * Save a posted REST base or archive slug: lower-case letters, numbers, dashes, underscores and slashes. Spaces become dashes.
	 *
	 * @param int    $post_id WordPress Post ID
	 * @param string $key     Field name.
	 */
	private function save_path( int $post_id, string $key ): void {
		$value = $this->posted( $key );
		if ( null !== $value ) {
			$value = (string) preg_replace( '#\s+#', '-', trim( strtolower( $value ) ) );
			$value = trim( (string) preg_replace( '#[^a-z0-9_\-/]#', '', $value ), '/' );
			update_post_meta( $post_id, $key, $value );
		}
	}

	/**
	 * Check a new definition name. Returns an error message, or '' when the name is valid.
	 *
	 * @param  string $name     Sanitized name.
	 * @param  string $current  The name saved before.
	 * @param  bool   $taxonomy True for a taxonomy name.
	 * @return string
	 */
	private function name_error( string $name, string $current, bool $taxonomy ): string {
		$max      = $taxonomy ? self::MAX_TAXONOMY_LENGTH : self::MAX_POST_TYPE_LENGTH;
		$reserved = $taxonomy ? self::RESERVED_TAXONOMIES : self::RESERVED_POST_TYPES;

		if ( '' === $name ) {
			return __( 'The name is required. Use lower-case letters, numbers, dashes and underscores only.', 'custom-post-type-maker' );
		}
		if ( strlen( $name ) > $max ) {
			/* translators: %d: maximum number of characters */
			return sprintf( __( 'The name is too long. Use at most %d characters.', 'custom-post-type-maker' ), $max );
		}
		if ( $name === $current ) {
			return '';
		}
		if ( in_array( $name, $reserved, true ) || ( $taxonomy ? taxonomy_exists( $name ) : post_type_exists( $name ) ) ) {
			/* translators: %s: the name */
			return sprintf( __( 'The name "%s" is reserved or already registered. Use a different name.', 'custom-post-type-maker' ), $name );
		}
		return '';
	}

	/**
	 * Save a posted definition name, or keep the old one and show an error.
	 *
	 * @param int    $post_id  WordPress Post ID
	 * @param string $key      Field name.
	 * @param bool   $taxonomy True for a taxonomy name.
	 */
	private function save_name( int $post_id, string $key, bool $taxonomy ): void {
		$raw = $this->posted( $key );
		if ( null === $raw ) {
			return;
		}
		$name    = sanitize_key( str_replace( ' ', '', $raw ) );
		$current = (string) get_post_meta( $post_id, $key, true );
		$error   = $this->name_error( $name, $current, $taxonomy );
		if ( $error ) {
			set_transient( 'cptm_notice_' . get_current_user_id(), $error, MINUTE_IN_SECONDS );
			return;
		}
		update_post_meta( $post_id, $key, $name );
	}

	/**
	 * Save a post type definition
	 *
	 * @param int $post_id WordPress Post ID
	 */
	public function cptm_save_post( int $post_id ): void {
		if ( ! $this->can_save( $post_id ) ) {
			return;
		}

		$this->save_name( $post_id, 'cptm_name', false );

		foreach ( array( 'cptm_label', 'cptm_singular_name', 'cptm_custom_rewrite_slug' ) as $key ) {
			$value = $this->posted( $key );
			if ( null !== $value ) {
				update_post_meta( $post_id, $key, sanitize_text_field( $value ) );
			}
		}

		// Stored HTML-encoded, as in 1.2.0 (C-001).
		$description = $this->posted( 'cptm_description' );
		if ( null !== $description ) {
			update_post_meta( $post_id, 'cptm_description', esc_textarea( sanitize_textarea_field( $description ) ) );
		}

		$icon_slug = $this->posted( 'cptm_icon_slug' );
		if ( null !== $icon_slug ) {
			update_post_meta( $post_id, 'cptm_icon_slug', sanitize_html_class( $icon_slug ) );
		}

		$icon_url = $this->posted( 'cptm_icon_url' );
		if ( null !== $icon_url ) {
			update_post_meta( $post_id, 'cptm_icon_url', esc_url_raw( $icon_url ) );
		}

		$menu_position = $this->posted( 'cptm_menu_position' );
		if ( null !== $menu_position ) {
			update_post_meta( $post_id, 'cptm_menu_position', is_numeric( trim( $menu_position ) ) ? (string) absint( $menu_position ) : '' );
		}

		$capability_type = $this->posted( 'cptm_capability_type' );
		if ( in_array( $capability_type, array( 'post', 'page' ), true ) ) {
			update_post_meta( $post_id, 'cptm_capability_type', $capability_type );
		}

		$this->save_flags( $post_id, array( 'cptm_public', 'cptm_show_ui', 'cptm_has_archive', 'cptm_exclude_from_search', 'cptm_hierarchical', 'cptm_rewrite', 'cptm_withfront', 'cptm_feeds', 'cptm_pages', 'cptm_query_var', 'cptm_show_in_rest', 'cptm_publicly_queryable', 'cptm_show_in_menu' ) );
		$this->save_flags( $post_id, array( 'cptm_show_in_nav_menus', 'cptm_show_in_admin_bar', 'cptm_can_export', 'cptm_delete_with_user' ), true );
		$this->save_path( $post_id, 'cptm_archive_slug' );
		$this->save_path( $post_id, 'cptm_rest_base' );
		$this->save_path( $post_id, 'cptm_rest_namespace' );

		update_post_meta( $post_id, 'cptm_supports', $this->posted_list( 'cptm_supports', array_map( 'sanitize_key', self::SUPPORTS ) ) );
		update_post_meta( $post_id, 'cptm_builtin_taxonomies', $this->posted_list( 'cptm_builtin_taxonomies', self::BUILTIN_TAXONOMIES ) );

		update_option( 'cptm_plugin_settings_changed', true );
	}

	/**
	 * Save a taxonomy definition
	 *
	 * @param int $post_id WordPress Post ID
	 */
	public function cptm_tax_save_post( int $post_id ): void {
		if ( ! $this->can_save( $post_id ) ) {
			return;
		}

		$this->save_name( $post_id, 'cptm_tax_name', true );

		foreach ( array( 'cptm_tax_label', 'cptm_tax_singular_name', 'cptm_tax_custom_rewrite_slug', 'cptm_tax_default_term' ) as $key ) {
			$value = $this->posted( $key );
			if ( null !== $value ) {
				update_post_meta( $post_id, $key, sanitize_text_field( $value ) );
			}
		}

		$this->save_flags( $post_id, array( 'cptm_tax_show_ui', 'cptm_tax_hierarchical', 'cptm_tax_rewrite', 'cptm_tax_query_var', 'cptm_tax_show_in_rest', 'cptm_tax_show_admin_column' ) );
		$this->save_flags( $post_id, array( 'cptm_tax_public', 'cptm_tax_publicly_queryable', 'cptm_tax_show_in_nav_menus', 'cptm_tax_show_tagcloud', 'cptm_tax_show_in_quick_edit' ), true );
		$this->save_path( $post_id, 'cptm_tax_rest_base' );

		$post_types = array_values( array_filter( $this->posted_list( 'cptm_tax_post_types', null ), 'post_type_exists' ) );
		update_post_meta( $post_id, 'cptm_tax_post_types', $post_types );

		update_option( 'cptm_plugin_settings_changed', true );
	}

	/**
	 * Show the error of the last save, if any.
	 */
	public function cptm_admin_notices(): void {
		$key    = 'cptm_notice_' . get_current_user_id();
		$notice = get_transient( $key );
		if ( $notice ) {
			delete_transient( $key );
			printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html( $notice ) );
		}
	}

	/**
	 * Flush rewrite rules
	 */
	public function cptm_plugin_settings_flush_rewrite(): void {
		if ( get_option( 'cptm_plugin_settings_changed' ) == true ) {
			flush_rewrite_rules();
			update_option( 'cptm_plugin_settings_changed', false );
		}
	}

	/**
	 * Flush rewrite rules on plugin activation
	 */
	public function cptm_plugin_activate_flush_rewrite(): void {
		$this->cptm_create_custom_post_types();
		flush_rewrite_rules();
	}

	/**
	 * Modify existing columns
	 *
	 * @param  array $cols Post columns
	 * @return array       Modified columns
	 */
	public function cptm_change_columns( array $cols ): array {
		return array(
			'cb'                    => '<input type="checkbox" />',
			'title'                 => __( 'Post Type', 'custom-post-type-maker' ),
			'custom_post_type_name' => __( 'Custom Post Type Name', 'custom-post-type-maker' ),
			'label'                 => __( 'Label', 'custom-post-type-maker' ),
			'description'           => __( 'Description', 'custom-post-type-maker' ),
		);
	}

	/**
	 * Make columns sortable
	 *
	 * @return array Sortable array
	 */
	public function cptm_sortable_columns(): array {
		return array(
			'title' => 'title',
		);
	}

	/**
	 * Insert custom column
	 *
	 * @param string $column  Column name
	 * @param int    $post_id WordPress Post ID
	 */
	public function cptm_custom_columns( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'custom_post_type_name':
				echo esc_html( get_post_meta( $post_id, 'cptm_name', true ) );
				break;
			case 'label':
				echo esc_html( get_post_meta( $post_id, 'cptm_label', true ) );
				break;
			case 'description':
				// Stored HTML-encoded (C-001); esc_html() does not encode it twice.
				echo esc_html( get_post_meta( $post_id, 'cptm_description', true ) );
				break;
		}
	}

	/**
	 * Modify existing taxonomy columns
	 *
	 * @param  array $cols Taxonomy columns
	 * @return array       Modified taxonomy columns
	 */
	public function cptm_tax_change_columns( array $cols ): array {
		return array(
			'cb'                    => '<input type="checkbox" />',
			'title'                 => __( 'Taxonomy', 'custom-post-type-maker' ),
			'custom_post_type_name' => __( 'Custom Taxonomy Name', 'custom-post-type-maker' ),
			'label'                 => __( 'Label', 'custom-post-type-maker' ),
		);
	}

	/**
	 * Make taxonomy columns sortable
	 *
	 * @return array Sortable array
	 */
	public function cptm_tax_sortable_columns(): array {
		return array(
			'title' => 'title',
		);
	}

	/**
	 * Insert custom taxonomy columns
	 *
	 * @param string $column  Column name
	 * @param int    $post_id WordPress Post ID
	 */
	public function cptm_tax_custom_columns( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'custom_post_type_name':
				echo esc_html( get_post_meta( $post_id, 'cptm_tax_name', true ) );
				break;
			case 'label':
				echo esc_html( get_post_meta( $post_id, 'cptm_tax_label', true ) );
				break;
		}
	}

	/**
	 * The names of all definitions of one kind, in any status.
	 *
	 * @param  string $post_type 'cptm' or 'cptm_tax'.
	 * @param  string $key       Name meta key.
	 * @return string[]
	 */
	private function definition_names( string $post_type, string $key ): array {
		$names = array();
		foreach ( get_posts( array( 'post_type' => $post_type, 'numberposts' => -1, 'post_status' => 'any', 'fields' => 'ids' ) ) as $id ) {
			$names[] = (string) get_post_meta( $id, $key, true );
		}
		return $names;
	}

	/**
	 * Print the "other registered" table.
	 *
	 * @param string $title       Heading.
	 * @param string $intro       Text under the heading.
	 * @param string $empty       Text when the list is empty.
	 * @param array  $items       Post type or taxonomy objects.
	 * @param bool   $description Show the description column.
	 * @param string $kind        Title column heading.
	 * @param string $name_label  Name column heading.
	 */
	private function other_registered_table( string $title, string $intro, string $empty, array $items, bool $description, string $kind, string $name_label ): void {
		$columns = $description ? 5 : 4;
		?>
		<div id="cptm-cpt-overview" class="hidden">
			<h2><?php echo esc_html( $title ); ?></h2>
			<p><?php echo esc_html( $intro ); ?></p>
			<table class="wp-list-table widefat fixed posts">
				<thead>
					<tr>
						<th scope="col" class="manage-column column-cb check-column"></th>
						<th scope="col" class="manage-column column-title"><span><?php echo esc_html( $kind ); ?></span></th>
						<th scope="col" class="manage-column column-custom_post_type_name"><span><?php echo esc_html( $name_label ); ?></span></th>
						<th scope="col" class="manage-column column-label"><span><?php esc_html_e( 'Label', 'custom-post-type-maker' ); ?></span></th>
						<?php if ( $description ) { ?>
							<th scope="col" class="manage-column column-description"><span><?php esc_html_e( 'Description', 'custom-post-type-maker' ); ?></span></th>
						<?php } ?>
					</tr>
				</thead>
				<tbody id="the-list">
					<?php foreach ( $items as $item ) { ?>
						<tr>
							<th scope="row" class="check-column"></th>
							<td class="post-title page-title column-title"><strong><?php echo esc_html( $item->labels->name ); ?></strong></td>
							<td class="custom_post_type_name column-custom_post_type_name"><?php echo esc_html( $item->name ); ?></td>
							<td class="label column-label"><?php echo esc_html( $item->labels->name ); ?></td>
							<?php if ( $description ) { ?>
								<td class="description column-description"><?php echo esc_html( $item->description ); ?></td>
							<?php } ?>
						</tr>
					<?php } ?>
					<?php if ( ! $items ) { ?>
						<tr class="no-items"><td class="colspanchange" colspan="<?php echo (int) $columns; ?>"><?php echo esc_html( $empty ); ?></td></tr>
					<?php } ?>
				</tbody>
			</table>
			<div class="tablenav bottom">
				<div class="tablenav-pages one-page">
					<span class="displaying-num">
						<?php
						$count = count( $items );
						/* translators: %d: number of items */
						printf( esc_html( _n( '%d item', '%d items', $count, 'custom-post-type-maker' ) ), (int) $count );
						?>
					</span>
					<br class="clear">
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Insert admin footer on the definition lists
	 */
	public function cptm_admin_footer(): void {
		if ( ! $this->is_plugin_screen( 'edit' ) ) {
			return;
		}
		$post_type = get_current_screen()->post_type;
		?>
		<div id="cptm-col-right" class="hidden">

			<div class="wp-box">
				<div class="inner">
					<h2><?php esc_html_e( 'Custom Post Type Maker', 'custom-post-type-maker' ); ?></h2>
					<p class="version"><?php esc_html_e( 'Version', 'custom-post-type-maker' ); ?> <?php echo esc_html( $this->version ); ?></p>
					<h3><?php esc_html_e( 'Useful links', 'custom-post-type-maker' ); ?></h3>
					<ul>
						<li><a class="thickbox" href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=custom-post-type-maker&section=changelog&TB_iframe=true&width=600&height=550' ) ); ?>"><?php esc_html_e( 'Changelog', 'custom-post-type-maker' ); ?></a></li>
						<li><a href="https://wordpress.org/support/plugin/custom-post-type-maker/" target="_blank" rel="noopener"><?php esc_html_e( 'Support Forums', 'custom-post-type-maker' ); ?></a></li>
					</ul>
				</div>
				<div class="footer footer-blue">
					<ul class="left">
						<li><?php esc_html_e( 'Created by', 'custom-post-type-maker' ); ?> <a href="https://www.graffino.com" target="_blank" rel="noopener" title="Graffino">Graffino</a></li>
						<li></li>
						<li><small>Originally by: http://www.bakhuys.com/</small></li>
					</ul>
					<ul class="right">
						<li><a href="https://wordpress.org/plugins/custom-post-type-maker/" target="_blank" rel="noopener"><?php esc_html_e( 'Vote', 'custom-post-type-maker' ); ?></a></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
		if ( 'cptm' === $post_type ) {
			// All public custom post types, without the ones that Custom Post Type Maker created.
			$post_types = get_post_types( array( 'public' => true, '_builtin' => false ), 'objects' );
			foreach ( $this->definition_names( 'cptm', 'cptm_name' ) as $name ) {
				unset( $post_types[ $name ] );
			}
			if ( $post_types ) {
				$this->other_registered_table(
					__( 'Other registered Custom Post Types', 'custom-post-type-maker' ),
					__( 'The Custom Post Types below are registered in WordPress but were not created by the Custom Post Type Maker plugin.', 'custom-post-type-maker' ),
					__( 'No Custom Post Types found.', 'custom-post-type-maker' ),
					$post_types,
					true,
					__( 'Post Type', 'custom-post-type-maker' ),
					__( 'Custom Post Type Name', 'custom-post-type-maker' )
				);
			}
		}
		if ( 'cptm_tax' === $post_type ) {
			// All public custom taxonomies, without the ones that Custom Post Type Maker created.
			$taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => false ), 'objects' );
			foreach ( $this->definition_names( 'cptm_tax', 'cptm_tax_name' ) as $name ) {
				unset( $taxonomies[ $name ] );
			}
			if ( $taxonomies ) {
				$this->other_registered_table(
					__( 'Other registered custom Taxonomies', 'custom-post-type-maker' ),
					__( 'The custom Taxonomies below are registered in WordPress but were not created by the Custom Post Type Maker plugin.', 'custom-post-type-maker' ),
					__( 'No custom Taxonomies found.', 'custom-post-type-maker' ),
					$taxonomies,
					false,
					__( 'Taxonomy', 'custom-post-type-maker' ),
					__( 'Custom Taxonomy Name', 'custom-post-type-maker' )
				);
			}
		}
	}

	/**
	 * Update messages
	 *
	 * @param  array $messages Update messages
	 * @return array           Update messages
	 */
	public function cptm_post_updated_messages( array $messages ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display value.
		$revision = isset( $_GET['revision'] ) ? absint( $_GET['revision'] ) : 0;

		$messages['cptm'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Custom Post Type updated.', 'custom-post-type-maker' ),
			2  => __( 'Custom Post Type updated.', 'custom-post-type-maker' ),
			3  => __( 'Custom Post Type deleted.', 'custom-post-type-maker' ),
			4  => __( 'Custom Post Type updated.', 'custom-post-type-maker' ),
			/* translators: %s: date and time of the revision */
			5  => $revision ? sprintf( __( 'Custom Post Type restored to revision from %s', 'custom-post-type-maker' ), wp_post_revision_title( $revision, false ) ) : false,
			6  => __( 'Custom Post Type published.', 'custom-post-type-maker' ),
			7  => __( 'Custom Post Type saved.', 'custom-post-type-maker' ),
			8  => __( 'Custom Post Type submitted.', 'custom-post-type-maker' ),
			9  => __( 'Custom Post Type scheduled.', 'custom-post-type-maker' ),
			10 => __( 'Custom Post Type draft updated.', 'custom-post-type-maker' ),
		);

		$messages['cptm_tax'] = array(
			0  => '',
			1  => __( 'Custom Taxonomy updated.', 'custom-post-type-maker' ),
			2  => __( 'Custom Taxonomy updated.', 'custom-post-type-maker' ),
			3  => __( 'Custom Taxonomy deleted.', 'custom-post-type-maker' ),
			4  => __( 'Custom Taxonomy updated.', 'custom-post-type-maker' ),
			5  => false,
			6  => __( 'Custom Taxonomy published.', 'custom-post-type-maker' ),
			7  => __( 'Custom Taxonomy saved.', 'custom-post-type-maker' ),
			8  => __( 'Custom Taxonomy submitted.', 'custom-post-type-maker' ),
			9  => __( 'Custom Taxonomy scheduled.', 'custom-post-type-maker' ),
			10 => __( 'Custom Taxonomy draft updated.', 'custom-post-type-maker' ),
		);

		return $messages;
	}

	/**
	 * Prepare attachment for Ajax Upload Request (C-004)
	 *
	 * @param  array       $response   Response
	 * @param  WP_Post     $attachment Attachment
	 * @param  array|false $meta       Attachment meta
	 * @return array                   Modified response
	 */
	public function wp_prepare_attachment_for_js( $response, $attachment, $meta ) {
		// only for image
		if ( ! is_array( $response ) || ( $response['type'] ?? '' ) !== 'image' ) {
			return $response;
		}

		$attachment_url = $response['url'];
		$base_url       = str_replace( wp_basename( $attachment_url ), '', $attachment_url );

		if ( is_array( $meta ) && isset( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $k => $v ) {
				if ( ! isset( $response['sizes'][ $k ] ) ) {
					$response['sizes'][ $k ] = array(
						'height'      => $v['height'],
						'width'       => $v['width'],
						'url'         => $base_url . $v['file'],
						'orientation' => $v['height'] > $v['width'] ? 'portrait' : 'landscape',
					);
				}
			}
		}

		return $response;
	}
}

/**
 * Instantiate the main class
 *
 * @since  1.0.0
 * @access public
 *
 * @var    object $cptm holds the instantiated class {@uses Cptm}
 */
$cptm = new Cptm();
