<?php
/*
Plugin Name: Custom Post Type Maker
Plugin URI: https://github.com/Graffino/custom-post-type-maker
Description: Custom Post Type Maker lets you create Custom Post Types and custom Taxonomies in a user friendly way.
Version: 1.2.0
Author: Graffino
Author URI: http://www.graffino.com/
Text Domain: custom-post-type-maker
Domain Path: /lang


Originally by: http://www.bakhuys.com/

Released under the GPL v.2, http://www.gnu.org/copyleft/gpl.html

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

// phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralText
// phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralDomain
// phpcs:disable WordPress.PHP.YodaConditions.NotYoda
// phpcs:disable Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace
// phpcs:disable Squiz.PHP.DisallowMultipleAssignments.Found

*/
/**
 * @author    Zeno Popovici <zeno@graffino.com>
 * @copyright Copyright (c) 2018, Graffino
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @package   Custom_Post_Types_Maker
 * @version   1.1.15
 */

//avoid direct calls to this file
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * Main class to run the plugin
 *
 * @since 1.0.0
 */
class Cptm {
	/** @var string $dir Plugin dir */
	private $dir;
	/** @var string $path Plugin path */
	private $path;
	/** @var string $version Plugin version */
	private $version;

	/**
	 * Constructor
	 */
	function __construct() {
		// vars
		$this->dir     = plugins_url( '', __FILE__ );
		$this->path    = plugin_dir_path( __FILE__ );
		$this->version = '1.2.0';

		// actions
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'cptm_create_custom_post_types' ) );
		add_action( 'admin_menu', array( $this, 'cptm_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'cptm_styles' ) );
		add_action( 'add_meta_boxes', array( $this, 'cptm_create_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'cptm_save_post' ) );
		add_action( 'admin_init', array( $this, 'cptm_plugin_settings_flush_rewrite' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'cptm_custom_columns' ), 10, 2 );
		add_action( 'manage_posts_custom_column', array( $this, 'cptm_tax_custom_columns' ), 10, 2 );
		add_action( 'admin_footer', array( $this, 'cptm_admin_footer' ) );
		add_action( 'wp_prepare_attachment_for_js', array( $this, 'wp_prepare_attachment_for_js' ), 10, 3 );

		// filters
		add_filter( 'manage_cptm_posts_columns', array( $this, 'cptm_change_columns' ) );
		add_filter( 'manage_edit-cptm_sortable_columns', array( $this, 'cptm_sortable_columns' ) );
		add_filter( 'manage_cptm_tax_posts_columns', array( $this, 'cptm_tax_change_columns' ) );
		add_filter( 'manage_edit-cptm_tax_sortable_columns', array( $this, 'cptm_tax_sortable_columns' ) );
		add_filter( 'post_updated_messages', array( $this, 'cptm_post_updated_messages' ) );

		// hooks
		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
		register_activation_hook( __FILE__, array( $this, 'cptm_plugin_activate_flush_rewrite' ) );

		// set textdomain
		load_plugin_textdomain( 'custom-post-type-maker', false, basename( dirname( __FILE__ ) ) . '/lang' );
	}

	/**
	 * Initialize plugin
	 */
	public function init() {
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
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'_builtin'        => false,
				'capability_type' => 'page',
				'hierarchical'    => false,
				'rewrite'         => false,
				'query_var'       => 'cptm',
				'supports'        => array(
					'title',
				),
				'show_in_menu'    => false,
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
				'labels'          => $labels,
				'public'          => false,
				'show_ui'         => true,
				'_builtin'        => false,
				'capability_type' => 'page',
				'hierarchical'    => false,
				'rewrite'         => false,
				'query_var'       => 'cptm_tax',
				'supports'        => array(
					'title',
				),
				'show_in_menu'    => false,
			)
		);

		// Add image size for the Custom Post Type icon
		if ( function_exists( 'add_image_size' ) && ! defined( 'CPTM_DONT_GENERATE_ICON' ) ) {
			add_image_size( 'cptm_icon', 16, 16, true );
		}
	}

	/**
	 * Add admin menu items
	 */
	public function cptm_admin_menu() {
		// add cptm page to options menu
		add_menu_page( __( 'CPT Maker', 'custom-post-type-maker' ), __( 'Post Types', 'custom-post-type-maker' ), 'manage_options', 'edit.php?post_type=cptm', '', 'dashicons-layout' );
		add_submenu_page( 'edit.php?post_type=cptm', __( 'Taxonomies', 'custom-post-type-maker' ), __( 'Taxonomies', 'custom-post-type-maker' ), 'manage_options', 'edit.php?post_type=cptm_tax' );
	}

	/**
	 * Register admin styles
	 *
	 * @param string $hook WordPress hook
	 */
	public function cptm_styles( $hook ) {
		// register overview style
		if ( 'edit.php' == $hook && isset( $_GET['post_type'] ) && ( 'cptm' == $_GET['post_type'] || 'cptm_tax' == $_GET['post_type'] ) ) {
			wp_register_style( 'cptm_admin_styles', $this->dir . '/css/overview.css' );
			wp_enqueue_style( 'cptm_admin_styles' );

			wp_register_script( 'cptm_admin_js', $this->dir . '/js/overview.js', 'jquery', '0.0.1', true );
			wp_enqueue_script( 'cptm_admin_js' );

			wp_enqueue_script( array( 'jquery', 'thickbox' ) );
			wp_enqueue_style( array( 'thickbox' ) );
		}

		// register add / edit style
		if ( ( 'post-new.php' == $hook && isset( $_GET['post_type'] ) && 'cptm' == $_GET['post_type'] ) || ( 'post.php' == $hook && isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == 'cptm' ) || ( $hook == 'post-new.php' && isset( $_GET['post_type'] ) && 'cptm_tax' == $_GET['post_type'] ) || ( 'post.php' == $hook && isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == 'cptm_tax' ) ) {
			wp_register_style( 'cptm_add_edit_styles', $this->dir . '/css/add-edit.css' );
			wp_enqueue_style( 'cptm_add_edit_styles' );

			wp_register_script( 'cptm_admin__add_edit_js', $this->dir . '/js/add-edit.js', 'jquery', '0.0.1', true );
			wp_enqueue_script( 'cptm_admin__add_edit_js' );

			wp_enqueue_media();
		}
	}

	/**
	 * Create custom post types
	 */
	public function cptm_create_custom_post_types() {
		// vars
		$cptms     = array();
		$cptm_taxs = array();

		// query custom post types
		$get_cptm        = array(
			'numberposts'      => -1,
			'post_type'        => 'cptm',
			'post_status'      => 'publish',
			'suppress_filters' => false,
		);
		$cptm_post_types = get_posts( $get_cptm );

		// create array of post meta
		if ( $cptm_post_types ) {
			foreach ( $cptm_post_types as $cptm ) {
				$cptm_meta = get_post_meta( $cptm->ID, '', true );

				// text
				$cptm_name          = ( array_key_exists( 'cptm_name', $cptm_meta ) && $cptm_meta['cptm_name'][0] ? esc_html( $cptm_meta['cptm_name'][0] ) : 'no_name' );
				$cptm_label         = ( array_key_exists( 'cptm_label', $cptm_meta ) && $cptm_meta['cptm_label'][0] ? esc_html( $cptm_meta['cptm_label'][0] ) : $cptm_name );
				$cptm_singular_name = ( array_key_exists( 'cptm_singular_name', $cptm_meta ) && $cptm_meta['cptm_singular_name'][0] ? esc_html( $cptm_meta['cptm_singular_name'][0] ) : $cptm_label );
				$cptm_description   = ( array_key_exists( 'cptm_description', $cptm_meta ) && $cptm_meta['cptm_description'][0] ? $cptm_meta['cptm_description'][0] : '' );

				// Custom post icon (uploaded)
				$cptm_icon_url = ( array_key_exists( 'cptm_icon_url', $cptm_meta ) && $cptm_meta['cptm_icon_url'][0] ? $cptm_meta['cptm_icon_url'][0] : false );

				// Custom post icon (dashicons)
				$cptm_icon_slug = ( array_key_exists( 'cptm_icon_slug', $cptm_meta ) && $cptm_meta['cptm_icon_slug'][0] ? $cptm_meta['cptm_icon_slug'][0] : false );

				// If DashIcon is set ignore uploaded
				if ( ! empty( $cptm_icon_slug ) ) {
					$cptm_icon_name = $cptm_icon_slug;
				} else {
					$cptm_icon_name = $cptm_icon_url;
				}

				$cptm_custom_rewrite_slug = ( array_key_exists( 'cptm_custom_rewrite_slug', $cptm_meta ) && $cptm_meta['cptm_custom_rewrite_slug'][0] ? esc_html( $cptm_meta['cptm_custom_rewrite_slug'][0] ) : $cptm_name );
				$cptm_menu_position       = ( array_key_exists( 'cptm_menu_position', $cptm_meta ) && $cptm_meta['cptm_menu_position'][0] ? (int) $cptm_meta['cptm_menu_position'][0] : null );

				// dropdown
				$cptm_public              = ( array_key_exists( 'cptm_public', $cptm_meta ) && $cptm_meta['cptm_public'][0] == '1' ? true : false );
				$cptm_show_ui             = ( array_key_exists( 'cptm_show_ui', $cptm_meta ) && $cptm_meta['cptm_show_ui'][0] == '1' ? true : false );
				$cptm_has_archive         = ( array_key_exists( 'cptm_has_archive', $cptm_meta ) && $cptm_meta['cptm_has_archive'][0] == '1' ? true : false );
				$cptm_exclude_from_search = ( array_key_exists( 'cptm_exclude_from_search', $cptm_meta ) && $cptm_meta['cptm_exclude_from_search'][0] == '1' ? true : false );
				$cptm_capability_type     = ( array_key_exists( 'cptm_capability_type', $cptm_meta ) && $cptm_meta['cptm_capability_type'][0] ? $cptm_meta['cptm_capability_type'][0] : 'post' );
				$cptm_hierarchical        = ( array_key_exists( 'cptm_hierarchical', $cptm_meta ) && $cptm_meta['cptm_hierarchical'][0] == '1' ? true : false );
				$cptm_rewrite             = ( array_key_exists( 'cptm_rewrite', $cptm_meta ) && $cptm_meta['cptm_rewrite'][0] == '1' ? true : false );
				$cptm_withfront           = ( array_key_exists( 'cptm_withfront', $cptm_meta ) && $cptm_meta['cptm_withfront'][0] == '1' ? true : false );
				$cptm_feeds               = ( array_key_exists( 'cptm_feeds', $cptm_meta ) && $cptm_meta['cptm_feeds'][0] == '1' ? true : false );
				$cptm_pages               = ( array_key_exists( 'cptm_pages', $cptm_meta ) && $cptm_meta['cptm_pages'][0] == '1' ? true : false );
				$cptm_query_var           = ( array_key_exists( 'cptm_query_var', $cptm_meta ) && $cptm_meta['cptm_query_var'][0] == '1' ? true : false );
				$cptm_show_in_rest        = ( array_key_exists( 'cptm_show_in_rest', $cptm_meta ) && $cptm_meta['cptm_show_in_rest'][0] == '1' ? true : false );

				// If it doesn't exist, it must be set to true ( fix for existing installs )
				if ( ! array_key_exists( 'cptm_publicly_queryable', $cptm_meta ) ) {
					$cptm_publicly_queryable = true;
				} elseif ( $cptm_meta['cptm_publicly_queryable'][0] == '1' ) {
					$cptm_publicly_queryable = true;
				} else {
					$cptm_publicly_queryable = false;
				}

				$cptm_show_in_menu = ( array_key_exists( 'cptm_show_in_menu', $cptm_meta ) && $cptm_meta['cptm_show_in_menu'][0] == '1' ? true : false );

				// checkbox
				$cptm_supports           = ( array_key_exists( 'cptm_supports', $cptm_meta ) && $cptm_meta['cptm_supports'][0] ? $cptm_meta['cptm_supports'][0] : 'a:2:{i:0;s:5:"title";i:1;s:6:"editor";}' );
				$cptm_builtin_taxonomies = ( array_key_exists( 'cptm_builtin_taxonomies', $cptm_meta ) && $cptm_meta['cptm_builtin_taxonomies'][0] ? $cptm_meta['cptm_builtin_taxonomies'][0] : 'a:0:{}' );

				$cptm_rewrite_options = array();
				if ( $cptm_rewrite ) {
					$cptm_rewrite_options['slug'] = _x( $cptm_custom_rewrite_slug, 'URL Slug', 'custom-post-type-maker' );
				}

				$cptm_rewrite_options['with_front'] = $cptm_withfront;

				if ( $cptm_feeds ) {
					$cptm_rewrite_options['feeds'] = $cptm_feeds;
				}
				if ( $cptm_pages ) {
					$cptm_rewrite_options['pages'] = $cptm_pages;
				}

				$cptms[] = array(
					'cptm_id'                  => $cptm->ID,
					'cptm_name'                => $cptm_name,
					'cptm_label'               => $cptm_label,
					'cptm_singular_name'       => $cptm_singular_name,
					'cptm_description'         => $cptm_description,
					'cptm_icon_name'           => $cptm_icon_name,
					'cptm_custom_rewrite_slug' => $cptm_custom_rewrite_slug,
					'cptm_menu_position'       => $cptm_menu_position,
					'cptm_public'              => (bool) $cptm_public,
					'cptm_show_ui'             => (bool) $cptm_show_ui,
					'cptm_has_archive'         => (bool) $cptm_has_archive,
					'cptm_exclude_from_search' => (bool) $cptm_exclude_from_search,
					'cptm_capability_type'     => $cptm_capability_type,
					'cptm_hierarchical'        => (bool) $cptm_hierarchical,
					'cptm_rewrite'             => $cptm_rewrite_options,
					'cptm_query_var'           => (bool) $cptm_query_var,
					'cptm_show_in_rest'        => (bool) $cptm_show_in_rest,
					'cptm_publicly_queryable'  => (bool) $cptm_publicly_queryable,
					'cptm_show_in_menu'        => (bool) $cptm_show_in_menu,
					'cptm_supports'            => unserialize( $cptm_supports ),
					'cptm_builtin_taxonomies'  => unserialize( $cptm_builtin_taxonomies ),
				);

				// register custom post types
				if ( is_array( $cptms ) ) {
					foreach ( $cptms as $cptm_post_type ) {

						$labels = array(
							'name'               => __( $cptm_post_type['cptm_label'], 'custom-post-type-maker' ),
							'singular_name'      => __( $cptm_post_type['cptm_singular_name'], 'custom-post-type-maker' ),
							'add_new'            => __( 'Add New', 'custom-post-type-maker' ),
							'add_new_item'       => __( 'Add New ' . $cptm_post_type['cptm_singular_name'], 'custom-post-type-maker' ),
							'edit_item'          => __( 'Edit ' . $cptm_post_type['cptm_singular_name'], 'custom-post-type-maker' ),
							'new_item'           => __( 'New ' . $cptm_post_type['cptm_singular_name'], 'custom-post-type-maker' ),
							'view_item'          => __( 'View ' . $cptm_post_type['cptm_singular_name'], 'custom-post-type-maker' ),
							'search_items'       => __( 'Search ' . $cptm_post_type['cptm_label'], 'custom-post-type-maker' ),
							'not_found'          => __( 'No ' . $cptm_post_type['cptm_label'] . ' found', 'custom-post-type-maker' ),
							'not_found_in_trash' => __( 'No ' . $cptm_post_type['cptm_label'] . ' found in Trash', 'custom-post-type-maker' ),
						);

						$args = array(
							'labels'              => $labels,
							'description'         => $cptm_post_type['cptm_description'],
							'menu_icon'           => $cptm_post_type['cptm_icon_name'],
							'rewrite'             => $cptm_post_type['cptm_rewrite'],
							'menu_position'       => $cptm_post_type['cptm_menu_position'],
							'public'              => $cptm_post_type['cptm_public'],
							'show_ui'             => $cptm_post_type['cptm_show_ui'],
							'has_archive'         => $cptm_post_type['cptm_has_archive'],
							'exclude_from_search' => $cptm_post_type['cptm_exclude_from_search'],
							'capability_type'     => $cptm_post_type['cptm_capability_type'],
							'hierarchical'        => $cptm_post_type['cptm_hierarchical'],
							'show_in_menu'        => $cptm_post_type['cptm_show_in_menu'],
							'query_var'           => $cptm_post_type['cptm_query_var'],
							'show_in_rest'        => $cptm_post_type['cptm_show_in_rest'],
							'publicly_queryable'  => $cptm_post_type['cptm_publicly_queryable'],
							'_builtin'            => false,
							'supports'            => $cptm_post_type['cptm_supports'],
							'taxonomies'          => $cptm_post_type['cptm_builtin_taxonomies'],
						);
						if ( $cptm_post_type['cptm_name'] != 'no_name' ) {
							register_post_type( $cptm_post_type['cptm_name'], $args );
						}
					}
				}
			}
		}

		// query custom taxonomies
		$get_cptm_tax    = array(
			'numberposts'      => -1,
			'post_type'        => 'cptm_tax',
			'post_status'      => 'publish',
			'suppress_filters' => false,
		);
		$cptm_taxonomies = get_posts( $get_cptm_tax );

		// create array of post meta
		if ( $cptm_taxonomies ) {
			foreach ( $cptm_taxonomies as $cptm_tax ) {
				$cptm_meta = get_post_meta( $cptm_tax->ID, '', true );

				// text
				$cptm_tax_name                = ( array_key_exists( 'cptm_tax_name', $cptm_meta ) && $cptm_meta['cptm_tax_name'][0] ? esc_html( $cptm_meta['cptm_tax_name'][0] ) : 'no_name' );
				$cptm_tax_label               = ( array_key_exists( 'cptm_tax_label', $cptm_meta ) && $cptm_meta['cptm_tax_label'][0] ? esc_html( $cptm_meta['cptm_tax_label'][0] ) : $cptm_tax_name );
				$cptm_tax_singular_name       = ( array_key_exists( 'cptm_tax_singular_name', $cptm_meta ) && $cptm_meta['cptm_tax_singular_name'][0] ? esc_html( $cptm_meta['cptm_tax_singular_name'][0] ) : $cptm_tax_label );
				$cptm_tax_custom_rewrite_slug = ( array_key_exists( 'cptm_tax_custom_rewrite_slug', $cptm_meta ) && $cptm_meta['cptm_tax_custom_rewrite_slug'][0] ? esc_html( $cptm_meta['cptm_tax_custom_rewrite_slug'][0] ) : $cptm_tax_name );

				// dropdown
				$cptm_tax_show_ui           = ( array_key_exists( 'cptm_tax_show_ui', $cptm_meta ) && $cptm_meta['cptm_tax_show_ui'][0] == '1' ? true : false );
				$cptm_tax_hierarchical      = ( array_key_exists( 'cptm_tax_hierarchical', $cptm_meta ) && $cptm_meta['cptm_tax_hierarchical'][0] == '1' ? true : false );
				$cptm_tax_rewrite           = ( array_key_exists( 'cptm_tax_rewrite', $cptm_meta ) && $cptm_meta['cptm_tax_rewrite'][0] == '1' ? array( 'slug' => _x( $cptm_tax_custom_rewrite_slug, 'URL Slug', 'custom-post-type-maker' ) ) : false );
				$cptm_tax_query_var         = ( array_key_exists( 'cptm_tax_query_var', $cptm_meta ) && $cptm_meta['cptm_tax_query_var'][0] == '1' ? true : false );
				$cptm_tax_show_in_rest      = ( array_key_exists( 'cptm_tax_show_in_rest', $cptm_meta ) && $cptm_meta['cptm_tax_show_in_rest'][0] == '1' ? true : false );
				$cptm_tax_show_admin_column = ( array_key_exists( 'cptm_tax_show_admin_column', $cptm_meta ) && $cptm_meta['cptm_tax_show_admin_column'][0] == '1' ? true : false );

				// checkbox
				$cptm_tax_post_types = ( array_key_exists( 'cptm_tax_post_types', $cptm_meta ) && $cptm_meta['cptm_tax_post_types'][0] ? $cptm_meta['cptm_tax_post_types'][0] : 'a:0:{}' );

				$cptm_taxs[] = array(
					'cptm_tax_id'                  => $cptm_tax->ID,
					'cptm_tax_name'                => $cptm_tax_name,
					'cptm_tax_label'               => $cptm_tax_label,
					'cptm_tax_singular_name'       => $cptm_tax_singular_name,
					'cptm_tax_custom_rewrite_slug' => $cptm_tax_custom_rewrite_slug,
					'cptm_tax_show_ui'             => (bool) $cptm_tax_show_ui,
					'cptm_tax_hierarchical'        => (bool) $cptm_tax_hierarchical,
					'cptm_tax_rewrite'             => $cptm_tax_rewrite,
					'cptm_tax_query_var'           => (bool) $cptm_tax_query_var,
					'cptm_tax_show_in_rest'        => (bool) $cptm_tax_show_in_rest,
					'cptm_tax_show_admin_column'   => (bool) $cptm_tax_show_admin_column,
					'cptm_tax_builtin_taxonomies'  => unserialize( $cptm_tax_post_types ),
				);

				// register custom post types
				if ( is_array( $cptm_taxs ) ) {
					foreach ( $cptm_taxs as $cptm_taxonomy ) {

						$labels = array(
							'name'                       => _x( $cptm_taxonomy['cptm_tax_label'], 'taxonomy general name', 'custom-post-type-maker' ),
							'singular_name'              => _x( $cptm_taxonomy['cptm_tax_singular_name'], 'taxonomy singular name' ),
							'search_items'               => __( 'Search ' . $cptm_taxonomy['cptm_tax_label'], 'custom-post-type-maker' ),
							'popular_items'              => __( 'Popular ' . $cptm_taxonomy['cptm_tax_label'], 'custom-post-type-maker' ),
							'all_items'                  => __( $cptm_taxonomy['cptm_tax_label'], 'custom-post-type-maker' ),
							'parent_item'                => __( 'Parent ' . $cptm_taxonomy['cptm_tax_singular_name'], 'custom-post-type-maker' ),
							'parent_item_colon'          => __( 'Parent ' . $cptm_taxonomy['cptm_tax_singular_name'], 'custom-post-type-maker' . ':' ),
							'edit_item'                  => __( 'Edit ' . $cptm_taxonomy['cptm_tax_singular_name'], 'custom-post-type-maker' ),
							'update_item'                => __( 'Update ' . $cptm_taxonomy['cptm_tax_singular_name'], 'custom-post-type-maker' ),
							'add_new_item'               => __( 'Add New ' . $cptm_taxonomy['cptm_tax_singular_name'], 'custom-post-type-maker' ),
							'new_item_name'              => __( 'New ' . $cptm_taxonomy['cptm_tax_singular_name'], 'custom-post-type-maker' . ' Name' ),
							'separate_items_with_commas' => __( 'Seperate ' . $cptm_taxonomy['cptm_tax_label'], 'custom-post-type-maker' . ' with commas' ),
							'add_or_remove_items'        => __( 'Add or remove ' . $cptm_taxonomy['cptm_tax_label'], 'custom-post-type-maker' ),
							'choose_from_most_used'      => __( 'Choose from the most used ' . $cptm_taxonomy['cptm_tax_label'], 'custom-post-type-maker' ),
							'menu_name'                  => __( $cptm_taxonomy['cptm_tax_label'], 'custom-post-type-maker' ),
						);

						$args = array(
							'label'             => $cptm_taxonomy['cptm_tax_label'],
							'labels'            => $labels,
							'rewrite'           => $cptm_taxonomy['cptm_tax_rewrite'],
							'show_ui'           => $cptm_taxonomy['cptm_tax_show_ui'],
							'hierarchical'      => $cptm_taxonomy['cptm_tax_hierarchical'],
							'query_var'         => $cptm_taxonomy['cptm_tax_query_var'],
							'show_in_rest'      => $cptm_taxonomy['cptm_tax_show_in_rest'],
							'show_admin_column' => $cptm_taxonomy['cptm_tax_show_admin_column'],
						);

						if ( $cptm_taxonomy['cptm_tax_name'] != 'no_name' ) {
							register_taxonomy( $cptm_taxonomy['cptm_tax_name'], $cptm_taxonomy['cptm_tax_builtin_taxonomies'], $args );
						}
					}
				}
			}
		}
	}

	/**
	 * Create admin meta boxes
	 */
	public function cptm_create_meta_boxes() {
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
	 * Create custom post meta box
	 *
	 * @param  object $post WordPress $post object
	 */
	public function cptm_meta_box( $post ) {
		// get post meta values
		$values = get_post_custom( $post->ID );

		// text fields
		$cptm_name          = isset( $values['cptm_name'] ) ? esc_attr( $values['cptm_name'][0] ) : '';
		$cptm_label         = isset( $values['cptm_label'] ) ? esc_attr( $values['cptm_label'][0] ) : '';
		$cptm_singular_name = isset( $values['cptm_singular_name'] ) ? esc_attr( $values['cptm_singular_name'][0] ) : '';
		$cptm_description   = isset( $values['cptm_description'] ) ? esc_attr( $values['cptm_description'][0] ) : '';

		// Custom post icon (uploaded)
		$cptm_icon_url = isset( $values['cptm_icon_url'] ) ? esc_attr( $values['cptm_icon_url'][0] ) : '';

		// Custom post icon (dashicons)
		$cptm_icon_slug = isset( $values['cptm_icon_slug'] ) ? esc_attr( $values['cptm_icon_slug'][0] ) : '';

		// If DashIcon is set ignore uploaded
		if ( ! empty( $cptm_icon_slug ) ) {
			$cptm_icon_name = $cptm_icon_slug;
		} else {
			$cptm_icon_name = $cptm_icon_url;
		}

		$cptm_custom_rewrite_slug = isset( $values['cptm_custom_rewrite_slug'] ) ? esc_attr( $values['cptm_custom_rewrite_slug'][0] ) : '';
		$cptm_menu_position       = isset( $values['cptm_menu_position'] ) ? esc_attr( $values['cptm_menu_position'][0] ) : '';

		// select fields
		$cptm_public              = isset( $values['cptm_public'] ) ? esc_attr( $values['cptm_public'][0] ) : '';
		$cptm_show_ui             = isset( $values['cptm_show_ui'] ) ? esc_attr( $values['cptm_show_ui'][0] ) : '';
		$cptm_has_archive         = isset( $values['cptm_has_archive'] ) ? esc_attr( $values['cptm_has_archive'][0] ) : '';
		$cptm_exclude_from_search = isset( $values['cptm_exclude_from_search'] ) ? esc_attr( $values['cptm_exclude_from_search'][0] ) : '';
		$cptm_capability_type     = isset( $values['cptm_capability_type'] ) ? esc_attr( $values['cptm_capability_type'][0] ) : '';
		$cptm_hierarchical        = isset( $values['cptm_hierarchical'] ) ? esc_attr( $values['cptm_hierarchical'][0] ) : '';
		$cptm_rewrite             = isset( $values['cptm_rewrite'] ) ? esc_attr( $values['cptm_rewrite'][0] ) : '';
		$cptm_withfront           = isset( $values['cptm_withfront'] ) ? esc_attr( $values['cptm_withfront'][0] ) : '';
		$cptm_feeds               = isset( $values['cptm_feeds'] ) ? esc_attr( $values['cptm_feeds'][0] ) : '';
		$cptm_pages               = isset( $values['cptm_pages'] ) ? esc_attr( $values['cptm_pages'][0] ) : '';
		$cptm_query_var           = isset( $values['cptm_query_var'] ) ? esc_attr( $values['cptm_query_var'][0] ) : '';
		$cptm_show_in_rest        = isset( $values['cptm_show_in_rest'] ) ? esc_attr( $values['cptm_show_in_rest'][0] ) : '';
		$cptm_publicly_queryable  = isset( $values['cptm_publicly_queryable'] ) ? esc_attr( $values['cptm_publicly_queryable'][0] ) : '';
		$cptm_show_in_menu        = isset( $values['cptm_show_in_menu'] ) ? esc_attr( $values['cptm_show_in_menu'][0] ) : '';

		// checkbox fields
		$cptm_supports                 = isset( $values['cptm_supports'] ) ? unserialize( $values['cptm_supports'][0] ) : array();
		$cptm_supports_title           = ( isset( $values['cptm_supports'] ) && in_array( 'title', $cptm_supports ) ? 'title' : '' );
		$cptm_supports_editor          = ( isset( $values['cptm_supports'] ) && in_array( 'editor', $cptm_supports ) ? 'editor' : '' );
		$cptm_supports_excerpt         = ( isset( $values['cptm_supports'] ) && in_array( 'excerpt', $cptm_supports ) ? 'excerpt' : '' );
		$cptm_supports_trackbacks      = ( isset( $values['cptm_supports'] ) && in_array( 'trackbacks', $cptm_supports ) ? 'trackbacks' : '' );
		$cptm_supports_custom_fields   = ( isset( $values['cptm_supports'] ) && in_array( 'custom-fields', $cptm_supports ) ? 'custom-fields' : '' );
		$cptm_supports_comments        = ( isset( $values['cptm_supports'] ) && in_array( 'comments', $cptm_supports ) ? 'comments' : '' );
		$cptm_supports_revisions       = ( isset( $values['cptm_supports'] ) && in_array( 'revisions', $cptm_supports ) ? 'revisions' : '' );
		$cptm_supports_featured_image  = ( isset( $values['cptm_supports'] ) && in_array( 'thumbnail', $cptm_supports ) ? 'thumbnail' : '' );
		$cptm_supports_author          = ( isset( $values['cptm_supports'] ) && in_array( 'author', $cptm_supports ) ? 'author' : '' );
		$cptm_supports_page_attributes = ( isset( $values['cptm_supports'] ) && in_array( 'page-attributes', $cptm_supports ) ? 'page-attributes' : '' );
		$cptm_supports_post_formats    = ( isset( $values['cptm_supports'] ) && in_array( 'post-formats', $cptm_supports ) ? 'post-formats' : '' );

		$cptm_builtin_taxonomies            = isset( $values['cptm_builtin_taxonomies'] ) ? unserialize( $values['cptm_builtin_taxonomies'][0] ) : array();
		$cptm_builtin_taxonomies_categories = ( isset( $values['cptm_builtin_taxonomies'] ) && in_array( 'category', $cptm_builtin_taxonomies ) ? 'category' : '' );
		$cptm_builtin_taxonomies_tags       = ( isset( $values['cptm_builtin_taxonomies'] ) && in_array( 'post_tag', $cptm_builtin_taxonomies ) ? 'post_tag' : '' );

		// nonce
		wp_nonce_field( 'cptm_meta_box_nonce_action', 'cptm_meta_box_nonce_field' );

		// set defaults if new Custom Post Type is being created
		global $pagenow;
		$cptm_supports_title   = $pagenow === 'post-new.php' ? 'title' : $cptm_supports_title;
		$cptm_supports_editor  = $pagenow === 'post-new.php' ? 'editor' : $cptm_supports_editor;
		$cptm_supports_excerpt = $pagenow === 'post-new.php' ? 'excerpt' : $cptm_supports_excerpt;
		?>
		<table class="cptm">
			<tr>
				<td class="label">
					<label for="cptm_name"><span class="required">*</span> <?php _e( 'Custom Post Type Name', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'The post type name. Used to retrieve custom post type content. Must be all in lower-case and without any spaces.', 'custom-post-type-maker' ); ?></p>
					<p><?php _e( 'e.g. movies', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<input type="text" name="cptm_name" id="cptm_name" class="widefat" tabindex="1" value="<?php echo $cptm_name; ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_label"><?php _e( 'Label', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'A plural descriptive name for the post type.', 'custom-post-type-maker' ); ?></p>
					<p><?php _e( 'e.g. Movies', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<input type="text" name="cptm_label" id="cptm_label" class="widefat" tabindex="2" value="<?php echo $cptm_label; ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_singular_name"><?php _e( 'Singular Name', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'A singular descriptive name for the post type.', 'custom-post-type-maker' ); ?></p>
					<p><?php _e( 'e.g. Movie', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<input type="text" name="cptm_singular_name" id="cptm_singular_name" class="widefat" tabindex="3" value="<?php echo $cptm_singular_name; ?>" />
				</td>
			</tr>
			<tr>
				<td class="label top">
					<label for="cptm_description"><?php _e( 'Description', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'A short descriptive summary of what the post type is.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<textarea name="cptm_description" id="cptm_description" class="widefat" tabindex="4" rows="4"><?php echo $cptm_description; ?></textarea>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="section">
					<h3><?php _e( 'Visibility', 'custom-post-type-maker' ); ?></h3>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_public"><?php _e( 'Public', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Whether a post type is intended to be used publicly either via the admin interface or by front-end users.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_public" id="cptm_public" tabindex="5">
						<option value="1" <?php selected( $cptm_public, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="0" <?php selected( $cptm_public, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="section">
					<h3><?php _e( 'Rewrite Options', 'custom-post-type-maker' ); ?></h3>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_rewrite"><?php _e( 'Rewrite', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Triggers the handling of rewrites for this post type.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_rewrite" id="cptm_rewrite" tabindex="6">
						<option value="1" <?php selected( $cptm_rewrite, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="0" <?php selected( $cptm_rewrite, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_withfront"><?php _e( 'With Front', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Should the permastruct be prepended with the front base.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_withfront" id="cptm_withfront" tabindex="7">
						<option value="1" <?php selected( $cptm_withfront, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="0" <?php selected( $cptm_withfront, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_custom_rewrite_slug"><?php _e( 'Custom Rewrite Slug', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Customize the permastruct slug.', 'custom-post-type-maker' ); ?></p>
					<p><?php _e( 'Default: [Custom Post Type Name]', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<input type="text" name="cptm_custom_rewrite_slug" id="cptm_custom_rewrite_slug" class="widefat" tabindex="8" value="<?php echo $cptm_custom_rewrite_slug; ?>" />
				</td>
			</tr>
			<tr>
				<td colspan="2" class="section">
					<h3><?php _e( 'Front-end Options', 'custom-post-type-maker' ); ?></h3>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_feeds"><?php _e( 'Feeds', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Should a feed permastruct be built for this post type. Defaults to "has_archive" value.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_feeds" id="cptm_feeds" tabindex="9">
						<option value="0" <?php selected( $cptm_feeds, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="1" <?php selected( $cptm_feeds, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_pages"><?php _e( 'Pages', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Should the permastruct provide for pagination.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_pages" id="cptm_pages" tabindex="10">
						<option value="1" <?php selected( $cptm_pages, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="0" <?php selected( $cptm_pages, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_exclude_from_search"><?php _e( 'Exclude From Search', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Whether to exclude posts with this post type from front end search results.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_exclude_from_search" id="cptm_exclude_from_search" tabindex="11">
						<option value="0" <?php selected( $cptm_exclude_from_search, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="1" <?php selected( $cptm_exclude_from_search, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_has_archive"><?php _e( 'Has Archive', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Enables post type archives.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_has_archive" id="cptm_has_archive" tabindex="12">
						<option value="0" <?php selected( $cptm_has_archive, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="1" <?php selected( $cptm_has_archive, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="section">
					<h3><?php _e( 'Admin Menu Options', 'custom-post-type-maker' ); ?></h3>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_show_ui"><?php _e( 'Show UI', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Whether to generate a default UI for managing this post type in the admin.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_show_ui" id="cptm_show_ui" tabindex="13">
						<option value="1" <?php selected( $cptm_show_ui, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="0" <?php selected( $cptm_show_ui, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_menu_position"><?php _e( 'Menu Position', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'The position in the menu order the post type should appear. "Show in Menu" must be true.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<input type="text" name="cptm_menu_position" id="cptm_menu_position" class="widefat" tabindex="14" value="<?php echo $cptm_menu_position; ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_show_in_menu"><?php _e( 'Show in Menu', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Where to show the post type in the admin menu. "Show UI" must be true.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_show_in_menu" id="cptm_show_in_menu" tabindex="15">
						<option value="1" <?php selected( $cptm_show_in_menu, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="0" <?php selected( $cptm_show_in_menu, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="current-cptm-icon"><?php _e( 'Icon', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'This icon will be overriden if a Dash Icon is specified in the field below.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<div class="cptm-icon">
						<div class="current-cptm-icon">
						<?php if ( $cptm_icon_url ) { ?><img src="<?php echo $cptm_icon_url; ?>" /><?php } ?></div>
						<a href="/" class="remove-cptm-icon button-secondary"<?php if ( ! $cptm_icon_url ) { ?> style="display: none;"<?php } ?> tabindex="16">Remove Icon</a>
						<a  href="/"class="media-uploader-button button-primary" data-post-id="<?php echo $post->ID; ?>" tabindex="17"><?php if ( ! $cptm_icon_url ) { ?><?php _e( 'Add icon', 'custom-post-type-maker' ); ?><?php } else { ?><?php _e( 'Upload Icon', 'custom-post-type-maker' ); ?><?php } ?></a>
					</div>
					<input type="hidden" name="cptm_icon_url" id="cptm_icon_url" class="widefat" value="<?php echo $cptm_icon_url; ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_icon_slug"><?php _e( 'Slug Icon', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'This uses (<a href="https://developer.WordPress.org/resource/dashicons/">Dash Icons</a>) and <strong>overrides</strong> the uploaded icon above.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<?php if ( $cptm_icon_slug ) { ?><span id="cptm_icon_slug_before" class="dashicons-before <?php echo $cptm_icon_slug; ?>"><?php } ?></span>
					<input type="text" name="cptm_icon_slug" id="cptm_icon_slug" class="widefat" tabindex="18" value="<?php echo $cptm_icon_slug; ?>" />
				</td>
			</tr>
			<tr>
				<td colspan="2" class="section">
					<h3><?php _e( 'WordPress Integration', 'custom-post-type-maker' ); ?></h3>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_capability_type"><?php _e( 'Capability Type', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'The post type to use to build the read, edit, and delete capabilities.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_capability_type" id="cptm_capability_type" tabindex="18">
						<option value="post" <?php selected( $cptm_capability_type, 'post' ); ?>><?php _e( 'Post', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="page" <?php selected( $cptm_capability_type, 'page' ); ?>><?php _e( 'Page', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_hierarchical"><?php _e( 'Hierarchical', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Whether the post type is hierarchical (e.g. page).', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_hierarchical" id="cptm_hierarchical" tabindex="19">
						<option value="0" <?php selected( $cptm_hierarchical, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="1" <?php selected( $cptm_hierarchical, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_query_var"><?php _e( 'Query Var', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Sets the query_var key for this post type.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_query_var" id="cptm_query_var" tabindex="20">
						<option value="1" <?php selected( $cptm_query_var, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="0" <?php selected( $cptm_query_var, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_show_in_rest"><?php _e( 'Show in REST', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Sets the show_in_rest key for this post type.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_show_in_rest" id="cptm_show_in_rest" tabindex="21">
						<option value="1" <?php selected( $cptm_show_in_rest, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="0" <?php selected( $cptm_show_in_rest, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_publicly_queryable"><?php _e( 'Publicly Queryable', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Whether the post is visible on the front-end.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_publicly_queryable" id="cptm_publicly_queryable" tabindex="22">
						<option value="1" <?php selected( $cptm_publicly_queryable, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="0" <?php selected( $cptm_publicly_queryable, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label top">
					<label for="cptm_supports"><?php _e( 'Supports', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Adds the respective meta boxes when creating content for this Custom Post Type.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<input type="checkbox" tabindex="23" name="cptm_supports[]" id="cptm_supports_title" value="title" <?php checked( $cptm_supports_title, 'title' ); ?> /> <label for="cptm_supports_title"><?php _e( 'Title', 'custom-post-type-maker' ); ?> <span class="default">(<?php _e( 'default', 'custom-post-type-maker' ); ?>)</span></label><br />
					<input type="checkbox" tabindex="24" name="cptm_supports[]" id="cptm_supports_editor" value="editor" <?php checked( $cptm_supports_editor, 'editor' ); ?> /> <label for="cptm_supports_editor"><?php _e( 'Editor', 'custom-post-type-maker' ); ?> <span class="default">(<?php _e( 'default', 'custom-post-type-maker' ); ?>)</span></label><br />
					<input type="checkbox" tabindex="25" name="cptm_supports[]" id="cptm_supports_excerpt" value="excerpt" <?php checked( $cptm_supports_excerpt, 'excerpt' ); ?> /> <label for="cptm_supports_excerpt"><?php _e( 'Excerpt', 'custom-post-type-maker' ); ?> <span class="default">(<?php _e( 'default', 'custom-post-type-maker' ); ?>)</span></label><br />
					<input type="checkbox" tabindex="26" name="cptm_supports[]" id="cptm_supports_trackbacks" value="trackbacks" <?php checked( $cptm_supports_trackbacks, 'trackbacks' ); ?> /> <label for="cptm_supports_trackbacks"><?php _e( 'Trackbacks', 'custom-post-type-maker' ); ?></label><br />
					<input type="checkbox" tabindex="27" name="cptm_supports[]" id="cptm_supports_custom_fields" value="custom-fields" <?php checked( $cptm_supports_custom_fields, 'custom-fields' ); ?> /> <label for="cptm_supports_custom_fields"><?php _e( 'Custom Fields', 'custom-post-type-maker' ); ?></label><br />
					<input type="checkbox" tabindex="28" name="cptm_supports[]" id="cptm_supports_comments" value="comments" <?php checked( $cptm_supports_comments, 'comments' ); ?> /> <label for="cptm_supports_comments"><?php _e( 'Comments', 'custom-post-type-maker' ); ?></label><br />
					<input type="checkbox" tabindex="29" name="cptm_supports[]" id="cptm_supports_revisions" value="revisions" <?php checked( $cptm_supports_revisions, 'revisions' ); ?> /> <label for="cptm_supports_revisions"><?php _e( 'Revisions', 'custom-post-type-maker' ); ?></label><br />
					<input type="checkbox" tabindex="30" name="cptm_supports[]" id="cptm_supports_featured_image" value="thumbnail" <?php checked( $cptm_supports_featured_image, 'thumbnail' ); ?> /> <label for="cptm_supports_featured_image"><?php _e( 'Featured Image', 'custom-post-type-maker' ); ?></label><br />
					<input type="checkbox" tabindex="31" name="cptm_supports[]" id="cptm_supports_author" value="author" <?php checked( $cptm_supports_author, 'author' ); ?> /> <label for="cptm_supports_author"><?php _e( 'Author', 'custom-post-type-maker' ); ?></label><br />
					<input type="checkbox" tabindex="32" name="cptm_supports[]" id="cptm_supports_page_attributes" value="page-attributes" <?php checked( $cptm_supports_page_attributes, 'page-attributes' ); ?> /> <label for="cptm_supports_page_attributes"><?php _e( 'Page Attributes', 'custom-post-type-maker' ); ?></label><br />
					<input type="checkbox" tabindex="33" name="cptm_supports[]" id="cptm_supports_post_formats" value="post-formats" <?php checked( $cptm_supports_post_formats, 'post-formats' ); ?> /> <label for="cptm_supports_post_formats"><?php _e( 'Post Formats', 'custom-post-type-maker' ); ?></label><br />
				</td>
			</tr>
			<tr>
				<td class="label top">
					<label for="cptm_builtin_taxonomies"><?php _e( 'Built-in Taxonomies', 'custom-post-type-maker' ); ?></label>
					<p>&nbsp;</p>
				</td>
				<td>
					<input type="checkbox" tabindex="34" name="cptm_builtin_taxonomies[]" id="cptm_builtin_taxonomies_categories" value="category" <?php checked( $cptm_builtin_taxonomies_categories, 'category' ); ?> /> <label for="cptm_builtin_taxonomies_categories"><?php _e( 'Categories', 'custom-post-type-maker' ); ?></label><br />
					<input type="checkbox" tabindex="35" name="cptm_builtin_taxonomies[]" id="cptm_builtin_taxonomies_tags" value="post_tag" <?php checked( $cptm_builtin_taxonomies_tags, 'post_tag' ); ?> /> <label for="cptm_builtin_taxonomies_tags"><?php _e( 'Tags', 'custom-post-type-maker' ); ?></label><br />
				</td>
			</tr>
		</table>

		<?php
	}

	/**
	 * Create custom post taxonomy meta box
	 *
	 * @param  object $post WordPress $post object
	 */
	public function cptm_tax_meta_box( $post ) {
		// get post meta values
		$values = get_post_custom( $post->ID );

		// text fields
		$cptm_tax_name                = isset( $values['cptm_tax_name'] ) ? esc_attr( $values['cptm_tax_name'][0] ) : '';
		$cptm_tax_label               = isset( $values['cptm_tax_label'] ) ? esc_attr( $values['cptm_tax_label'][0] ) : '';
		$cptm_tax_singular_name       = isset( $values['cptm_tax_singular_name'] ) ? esc_attr( $values['cptm_tax_singular_name'][0] ) : '';
		$cptm_tax_custom_rewrite_slug = isset( $values['cptm_tax_custom_rewrite_slug'] ) ? esc_attr( $values['cptm_tax_custom_rewrite_slug'][0] ) : '';

		// select fields
		$cptm_tax_show_ui           = isset( $values['cptm_tax_show_ui'] ) ? esc_attr( $values['cptm_tax_show_ui'][0] ) : '';
		$cptm_tax_hierarchical      = isset( $values['cptm_tax_hierarchical'] ) ? esc_attr( $values['cptm_tax_hierarchical'][0] ) : '';
		$cptm_tax_rewrite           = isset( $values['cptm_tax_rewrite'] ) ? esc_attr( $values['cptm_tax_rewrite'][0] ) : '';
		$cptm_tax_query_var         = isset( $values['cptm_tax_query_var'] ) ? esc_attr( $values['cptm_tax_query_var'][0] ) : '';
		$cptm_tax_show_in_rest      = isset( $values['cptm_tax_show_in_rest'] ) ? esc_attr( $values['cptm_tax_show_in_rest'][0] ) : '';
		$cptm_tax_show_admin_column = isset( $values['cptm_tax_show_admin_column'] ) ? esc_attr( $values['cptm_tax_show_admin_column'][0] ) : '';

		$cptm_tax_post_types      = isset( $values['cptm_tax_post_types'] ) ? unserialize( $values['cptm_tax_post_types'][0] ) : array();
		$cptm_tax_post_types_post = ( isset( $values['cptm_tax_post_types'] ) && in_array( 'post', $cptm_tax_post_types ) ? 'post' : '' );
		$cptm_tax_post_types_page = ( isset( $values['cptm_tax_post_types'] ) && in_array( 'page', $cptm_tax_post_types ) ? 'page' : '' );

		// nonce
		wp_nonce_field( 'cptm_meta_box_nonce_action', 'cptm_meta_box_nonce_field' );
		?>
		<table class="cptm">
			<tr>
				<td class="label">
					<label for="cptm_tax_name"><span class="required">*</span> <?php _e( 'Custom Taxonomy Name', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'The taxonomy name (use lowercase only). Used to retrieve custom taxonomy content. Must be all in lower-case and without any spaces.', 'custom-post-type-maker' ); ?></p>
					<p><?php _e( 'e.g. movies', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<input type="text" name="cptm_tax_name" id="cptm_tax_name" class="widefat" tabindex="1" value="<?php echo $cptm_tax_name; ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_tax_label"><?php _e( 'Label', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'A plural descriptive name for the taxonomy.', 'custom-post-type-maker' ); ?></p>
					<p><?php _e( 'e.g. Movies', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<input type="text" name="cptm_tax_label" id="cptm_tax_label" class="widefat" tabindex="2" value="<?php echo $cptm_tax_label; ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_tax_singular_name"><?php _e( 'Singular Name', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'A singular descriptive name for the taxonomy.', 'custom-post-type-maker' ); ?></p>
					<p><?php _e( 'e.g. Movie', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<input type="text" name="cptm_tax_singular_name" id="cptm_tax_singular_name" class="widefat" tabindex="3" value="<?php echo $cptm_tax_singular_name; ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_tax_show_ui"><?php _e( 'Show UI', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Whether to generate a default UI for managing this taxonomy in the admin.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_tax_show_ui" id="cptm_tax_show_ui" tabindex="4">
						<option value="1" <?php selected( $cptm_tax_show_ui, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="0" <?php selected( $cptm_tax_show_ui, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_tax_hierarchical"><?php _e( 'Hierarchical', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Whether the taxonomy is hierarchical (e.g. page).', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_tax_hierarchical" id="cptm_tax_hierarchical" tabindex="5">
						<option value="0" <?php selected( $cptm_tax_hierarchical, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="1" <?php selected( $cptm_tax_hierarchical, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_tax_rewrite"><?php _e( 'Rewrite', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Triggers the handling of rewrites for this taxonomy.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_tax_rewrite" id="cptm_tax_rewrite" tabindex="6">
						<option value="1" <?php selected( $cptm_tax_rewrite, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="0" <?php selected( $cptm_tax_rewrite, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_tax_custom_rewrite_slug"><?php _e( 'Custom Rewrite Slug', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Customize the permastruct slug.', 'custom-post-type-maker' ); ?></p>
					<p><?php _e( 'Default: [Custom Taxonomy Name]', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<input type="text" name="cptm_tax_custom_rewrite_slug" id="cptm_tax_custom_rewrite_slug" class="widefat" tabindex="7" value="<?php echo $cptm_tax_custom_rewrite_slug; ?>" />
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_tax_query_var"><?php _e( 'Query Var', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Sets the query_var key for this taxonomy.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_tax_query_var" id="cptm_tax_query_var" tabindex="8">
						<option value="1" <?php selected( $cptm_tax_query_var, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="0" <?php selected( $cptm_tax_query_var, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label">
					<label for="cptm_tax_show_in_rest"><?php _e( 'Show in REST', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Sets the show_in_rest key for this taxonomy.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_tax_show_in_rest" id="cptm_tax_show_in_rest" tabindex="9">
						<option value="1" <?php selected( $cptm_tax_show_in_rest, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="0" <?php selected( $cptm_tax_show_in_rest, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			<tr>
				<td class="label">
					<label for="cptm_tax_show_admin_column"><?php _e( 'Admin Column', 'custom-post-type-maker' ); ?></label>
					<p><?php _e( 'Show this taxonomy as a column in the custom post listing.', 'custom-post-type-maker' ); ?></p>
				</td>
				<td>
					<select name="cptm_tax_show_admin_column" id="cptm_tax_show_admin_column" tabindex="10">
						<option value="1" <?php selected( $cptm_tax_show_admin_column, '1' ); ?>><?php _e( 'True', 'custom-post-type-maker' ); ?> (<?php _e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="0" <?php selected( $cptm_tax_show_admin_column, '0' ); ?>><?php _e( 'False', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label top">
					<label for="cptm_tax_post_types"><?php _e( 'Post Types', 'custom-post-type-maker' ); ?></label>
					<p>&nbsp;</p>
				</td>
				<td>
					<input type="checkbox" tabindex="11" name="cptm_tax_post_types[]" id="cptm_tax_post_types_post" value="post" <?php checked( $cptm_tax_post_types_post, 'post' ); ?> /> <label for="cptm_tax_post_types_post"><?php _e( 'Posts', 'custom-post-type-maker' ); ?></label><br />
					<input type="checkbox" tabindex="12" name="cptm_tax_post_types[]" id="cptm_tax_post_types_page" value="page" <?php checked( $cptm_tax_post_types_page, 'page' ); ?> /> <label for="cptm_tax_post_types_page"><?php _e( 'Pages', 'custom-post-type-maker' ); ?></label><br />
					<?php
					$post_types = get_post_types(
						array(
							'public'   => true,
							'_builtin' => false,
						)
					);

					$i = 13;
					foreach ( $post_types as $post_type ) {
						$checked = in_array( $post_type, $cptm_tax_post_types ) ? 'checked="checked"' : '';
						?>
						<input type="checkbox" tabindex="<?php echo $i; ?>" name="cptm_tax_post_types[]" id="cptm_tax_post_types_<?php echo $post_type; ?>" value="<?php echo $post_type; ?>" <?php echo $checked; ?> /> <label for="cptm_tax_post_types_<?php echo $post_type; ?>"><?php echo ucfirst( $post_type ); ?></label><br />
						<?php
						$i++;
					}
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save custom post
	 *
	 * @param  int $post_id WordPress Post ID
	 */
	public function cptm_save_post( $post_id ) {
		// verify if this is an auto save routine.
		// If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// if our nonce isn't there, or we can't verify it, bail
		if ( ! isset( $_POST['cptm_meta_box_nonce_field'] ) || ! wp_verify_nonce( $_POST['cptm_meta_box_nonce_field'], 'cptm_meta_box_nonce_action' ) ) {
			return;
		}

		// update custom post type meta values
		if ( isset( $_POST['cptm_name'] ) ) {
			update_post_meta( $post_id, 'cptm_name', sanitize_text_field( strtolower( str_replace( ' ', '', $_POST['cptm_name'] ) ) ) );
		}

		if ( isset( $_POST['cptm_label'] ) ) {
			update_post_meta( $post_id, 'cptm_label', sanitize_text_field( $_POST['cptm_label'] ) );
		}

		if ( isset( $_POST['cptm_singular_name'] ) ) {
			update_post_meta( $post_id, 'cptm_singular_name', sanitize_text_field( $_POST['cptm_singular_name'] ) );
		}

		if ( isset( $_POST['cptm_description'] ) ) {
			update_post_meta( $post_id, 'cptm_description', esc_textarea( $_POST['cptm_description'] ) );
		}

		if ( isset( $_POST['cptm_icon_slug'] ) ) {
			update_post_meta( $post_id, 'cptm_icon_slug', esc_textarea( $_POST['cptm_icon_slug'] ) );
		}

		if ( isset( $_POST['cptm_icon_url'] ) ) {
			update_post_meta( $post_id, 'cptm_icon_url', esc_textarea( $_POST['cptm_icon_url'] ) );
		}

		if ( isset( $_POST['cptm_public'] ) ) {
			update_post_meta( $post_id, 'cptm_public', esc_attr( $_POST['cptm_public'] ) );
		}

		if ( isset( $_POST['cptm_show_ui'] ) ) {
			update_post_meta( $post_id, 'cptm_show_ui', esc_attr( $_POST['cptm_show_ui'] ) );
		}

		if ( isset( $_POST['cptm_has_archive'] ) ) {
			update_post_meta( $post_id, 'cptm_has_archive', esc_attr( $_POST['cptm_has_archive'] ) );
		}

		if ( isset( $_POST['cptm_exclude_from_search'] ) ) {
			update_post_meta( $post_id, 'cptm_exclude_from_search', esc_attr( $_POST['cptm_exclude_from_search'] ) );
		}

		if ( isset( $_POST['cptm_capability_type'] ) ) {
			update_post_meta( $post_id, 'cptm_capability_type', esc_attr( $_POST['cptm_capability_type'] ) );
		}

		if ( isset( $_POST['cptm_hierarchical'] ) ) {
			update_post_meta( $post_id, 'cptm_hierarchical', esc_attr( $_POST['cptm_hierarchical'] ) );
		}

		if ( isset( $_POST['cptm_rewrite'] ) ) {
			update_post_meta( $post_id, 'cptm_rewrite', esc_attr( $_POST['cptm_rewrite'] ) );
		}

		if ( isset( $_POST['cptm_withfront'] ) ) {
			update_post_meta( $post_id, 'cptm_withfront', esc_attr( $_POST['cptm_withfront'] ) );
		}

		if ( isset( $_POST['cptm_feeds'] ) ) {
			update_post_meta( $post_id, 'cptm_feeds', esc_attr( $_POST['cptm_feeds'] ) );
		}

		if ( isset( $_POST['cptm_pages'] ) ) {
			update_post_meta( $post_id, 'cptm_pages', esc_attr( $_POST['cptm_pages'] ) );
		}

		if ( isset( $_POST['cptm_custom_rewrite_slug'] ) ) {
			update_post_meta( $post_id, 'cptm_custom_rewrite_slug', sanitize_text_field( $_POST['cptm_custom_rewrite_slug'] ) );
		}

		if ( isset( $_POST['cptm_query_var'] ) ) {
			update_post_meta( $post_id, 'cptm_query_var', esc_attr( $_POST['cptm_query_var'] ) );
		}

		if ( isset( $_POST['cptm_show_in_rest'] ) ) {
			update_post_meta( $post_id, 'cptm_show_in_rest', esc_attr( $_POST['cptm_show_in_rest'] ) );
		}

		if ( isset( $_POST['cptm_publicly_queryable'] ) ) {
			update_post_meta( $post_id, 'cptm_publicly_queryable', esc_attr( $_POST['cptm_publicly_queryable'] ) );
		}

		if ( isset( $_POST['cptm_menu_position'] ) ) {
			update_post_meta( $post_id, 'cptm_menu_position', sanitize_text_field( $_POST['cptm_menu_position'] ) );
		}

		if ( isset( $_POST['cptm_show_in_menu'] ) ) {
			update_post_meta( $post_id, 'cptm_show_in_menu', esc_attr( $_POST['cptm_show_in_menu'] ) );
		}

		$cptm_supports = isset( $_POST['cptm_supports'] ) ? $_POST['cptm_supports'] : array(); {
			update_post_meta( $post_id, 'cptm_supports', $cptm_supports );
		}

		$cptm_builtin_taxonomies = isset( $_POST['cptm_builtin_taxonomies'] ) ? $_POST['cptm_builtin_taxonomies'] : array();
		update_post_meta( $post_id, 'cptm_builtin_taxonomies', $cptm_builtin_taxonomies );

		// Update taxonomy meta values
		if ( isset( $_POST['cptm_tax_name'] ) ) {
			update_post_meta( $post_id, 'cptm_tax_name', sanitize_text_field( strtolower( str_replace( ' ', '', $_POST['cptm_tax_name'] ) ) ) );
		}

		if ( isset( $_POST['cptm_tax_label'] ) ) {
			update_post_meta( $post_id, 'cptm_tax_label', sanitize_text_field( $_POST['cptm_tax_label'] ) );
		}

		if ( isset( $_POST['cptm_tax_singular_name'] ) ) {
			update_post_meta( $post_id, 'cptm_tax_singular_name', sanitize_text_field( $_POST['cptm_tax_singular_name'] ) );
		}

		if ( isset( $_POST['cptm_tax_show_ui'] ) ) {
			update_post_meta( $post_id, 'cptm_tax_show_ui', esc_attr( $_POST['cptm_tax_show_ui'] ) );
		}

		if ( isset( $_POST['cptm_tax_hierarchical'] ) ) {
			update_post_meta( $post_id, 'cptm_tax_hierarchical', esc_attr( $_POST['cptm_tax_hierarchical'] ) );
		}

		if ( isset( $_POST['cptm_tax_rewrite'] ) ) {
			update_post_meta( $post_id, 'cptm_tax_rewrite', esc_attr( $_POST['cptm_tax_rewrite'] ) );
		}

		if ( isset( $_POST['cptm_tax_custom_rewrite_slug'] ) ) {
			update_post_meta( $post_id, 'cptm_tax_custom_rewrite_slug', sanitize_text_field( $_POST['cptm_tax_custom_rewrite_slug'] ) );
		}

		if ( isset( $_POST['cptm_tax_query_var'] ) ) {
			update_post_meta( $post_id, 'cptm_tax_query_var', esc_attr( $_POST['cptm_tax_query_var'] ) );
		}

		if ( isset( $_POST['cptm_tax_show_in_rest'] ) ) {
			update_post_meta( $post_id, 'cptm_tax_show_in_rest', esc_attr( $_POST['cptm_tax_show_in_rest'] ) );
		}

		if ( isset( $_POST['cptm_tax_show_admin_column'] ) ) {
			update_post_meta( $post_id, 'cptm_tax_show_admin_column', esc_attr( $_POST['cptm_tax_show_admin_column'] ) );
		}

		$cptm_tax_post_types = isset( $_POST['cptm_tax_post_types'] ) ? $_POST['cptm_tax_post_types'] : array();
			update_post_meta( $post_id, 'cptm_tax_post_types', $cptm_tax_post_types );

			// Update plugin saved
			update_option( 'cptm_plugin_settings_changed', true );
	}

	/**
	 * Flush rewrite rules
	 */
	function cptm_plugin_settings_flush_rewrite() {
		if ( get_option( 'cptm_plugin_settings_changed' ) == true ) {
			flush_rewrite_rules();
			update_option( 'cptm_plugin_settings_changed', false );
		}
	}

	/**
	 * Flush rewrite rules on plugin activation
	 */
	function cptm_plugin_activate_flush_rewrite() {
		$this->cptm_create_custom_post_types();
		flush_rewrite_rules();
	}

	/**
	 * Modify existing columns
	 *
	 * @param  array $cols  Post columns
	 * @return object       Modified columns
	 */
	function cptm_change_columns( $cols ) {
		$cols = array(
			'cb'                    => '<input type="checkbox" />',
			'title'                 => __( 'Post Type', 'custom-post-type-maker' ),
			'custom_post_type_name' => __( 'Custom Post Type Name', 'custom-post-type-maker' ),
			'label'                 => __( 'Label', 'custom-post-type-maker' ),
			'description'           => __( 'Description', 'custom-post-type-maker' ),
		);
		return $cols;
	}

	/**
	 * Make columns sortable
	 *
	 * @return array Sortable array
	 */
	function cptm_sortable_columns() {
		return array(
			'title' => 'title',
		);
	}

	/**
	 * Insert custom column
	 *
	 * @param  string $column  Column name
	 * @param  int    $post_id WordPress Post ID
	 */
	function cptm_custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'custom_post_type_name':
				echo get_post_meta( $post_id, 'cptm_name', true );
				break;
			case 'label':
				echo get_post_meta( $post_id, 'cptm_label', true );
				break;
			case 'description':
				echo get_post_meta( $post_id, 'cptm_description', true );
				break;
		}
	}

	/**
	 * Modify existing taxonomy columns
	 *
	 * @param  array $cols Taxonomy columns
	 * @return array       Modified taxonomy columns
	 */
	function cptm_tax_change_columns( $cols ) {
		$cols = array(
			'cb'                    => '<input type="checkbox" />',
			'title'                 => __( 'Taxonomy', 'custom-post-type-maker' ),
			'custom_post_type_name' => __( 'Custom Taxonomy Name', 'custom-post-type-maker' ),
			'label'                 => __( 'Label', 'custom-post-type-maker' ),
		);
		return $cols;
	}

	/**
	 * Make taxonomy columns sortable
	 *
	 * @return array Sortable array
	 */
	function cptm_tax_sortable_columns() {
		return array(
			'title' => 'title',
		);
	}

	/**
	 * Insert custom taxonomy columns
	 *
	 * @param  string $column  Column name
	 * @param  int    $post_id WordPress Post ID
	 */
	function cptm_tax_custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'custom_post_type_name':
				echo get_post_meta( $post_id, 'cptm_tax_name', true );
				break;
			case 'label':
				echo get_post_meta( $post_id, 'cptm_tax_label', true );
				break;
		}
	}

	/**
	 * Insert admin footer
	 */
	function cptm_admin_footer() {
		global $post_type;
		?>
		<div id="cptm-col-right" class="hidden">

			<div class="wp-box">
				<div class="inner">
					<h2><?php _e( 'Custom Post Type Maker', 'custom-post-type-maker' ); ?></h2>
					<p class="version"><?php _e( 'Version', 'custom-post-type-maker' ); ?> <?php echo $this->version; ?></p>
					<h3><?php _e( 'Useful links', 'custom-post-type-maker' ); ?></h3>
					<ul>
						<li><a class="thickbox" href="<?php echo admin_url( 'plugin-install.php' ); ?>?tab=plugin-information&plugin=custom-post-type-maker-2&section=changelog&TB_iframe=true&width=600&height=550"><?php _e( 'Changelog', 'custom-post-type-maker' ); ?></a></li>
						<li><a href="http://WordPress.org/support/plugin/custom-post-type-maker-2" target="_blank"><?php _e( 'Support Forums', 'custom-post-type-maker' ); ?></a></li>
					</ul>
				</div>
				<div class="footer footer-blue">
					<ul class="left">
						<li><?php _e( 'Created by', 'cptm' ); ?> <a href="http://www.graffino.com" target="_blank" title="Graffino">Graffino</a></li>
			<li></li>
			<li><small>Originally by: http://www.bakhuys.com/</small></li>
					</ul>
					<ul class="right">
						<li><a href="http://WordPress.org/extend/plugins/custom-post-type-maker-2/" target="_blank"><?php _e( 'Vote', 'custom-post-type-maker' ); ?></a></li>
					</ul>
				</div>
			</div>
		</div>
		<?php
		if ( 'cptm' == $post_type ) {

			// Get all public Custom Post Types
			$post_types = get_post_types(
				array(
					'public'   => true,
					'_builtin' => false,
				),
				'objects'
			);
			// Get all Custom Post Types created by Custom Post Type Maker
			$cptm_posts = get_posts( array( 'post_type' => 'cptm' ) );
			// Remove all Custom Post Types created by the Custom Post Type Maker plugin
			foreach ( $cptm_posts as $cptm_post ) {
				$values = get_post_custom( $cptm_post->ID );
				unset( $post_types[ $values['cptm_name'][0] ] );
			}

			if ( count( $post_types ) != 0 ) {
				?>
			<div id="cptm-cpt-overview" class="hidden">
				<div id="icon-edit" class="icon32 icon32-posts-cptm"><br></div>
				<h2><?php _e( 'Other registered Custom Post Types', 'custom-post-type-maker' ); ?></h2>
				<p><?php _e( 'The Custom Post Types below are registered in WordPress but were not created by the Custom Post Type Maker plugin.', 'custom-post-type-maker' ); ?></p>
				<table class="wp-list-table widefat fixed posts" cellspacing="0">
					<thead>
						<tr>
							<th scope="col" id="cb" class="manage-column column-cb check-column">
							</th>
							<th scope="col" id="title" class="manage-column column-title">
								<span><?php _e( 'Post Type', 'custom-post-type-maker' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" id="custom_post_type_name" class="manage-column column-custom_post_type_name">
								<span><?php _e( 'Custom Post Type Name', 'custom-post-type-maker' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" id="label" class="manage-column column-label">
								<span><?php _e( 'Label', 'custom-post-type-maker' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" id="description" class="manage-column column-description">
								<span><?php _e( 'Description', 'custom-post-type-maker' ); ?></span><span class="sorting-indicator"></span>
							</th>
						</tr>
					</thead>

					<tfoot>
						<tr>
							<th scope="col" class="manage-column column-cb check-column">
							</th>
							<th scope="col" class="manage-column column-title">
								<span><?php _e( 'Post Type', 'custom-post-type-maker' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" class="manage-column column-custom_post_type_name">
								<span><?php _e( 'Custom Post Type Name', 'custom-post-type-maker' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" class="manage-column column-label">
								<span><?php _e( 'Label', 'custom-post-type-maker' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" class="manage-column column-description">
								<span><?php _e( 'Description', 'custom-post-type-maker' ); ?></span><span class="sorting-indicator"></span>
							</th>
						</tr>
					</tfoot>

					<tbody id="the-list">
						<?php
						// Create list of all other registered Custom Post Types
						foreach ( $post_types as $post_type ) {
							?>
						<tr valign="top">
							<th scope="row" class="check-column">
							</th>
							<td class="post-title page-title column-title">
								<strong><?php echo $post_type->labels->name; ?></strong>
							</td>
							<td class="custom_post_type_name column-custom_post_type_name"><?php echo $post_type->name; ?></td>
							<td class="label column-label"><?php echo $post_type->labels->name; ?></td>
							<td class="description column-description"><?php echo $post_type->description; ?></td>
						</tr>
								<?php
						}

						if ( count( $post_types ) == 0 ) {
							?>
						<tr class="no-items"><td class="colspanchange" colspan="5"><?php _e( 'No Custom Post Types found', 'custom-post-type-maker' ); ?>.</td></tr>
								<?php
						}
						?>
					</tbody>
				</table>

				<div class="tablenav bottom">
					<div class="tablenav-pages one-page">
						<span class="displaying-num">
							<?php
							$count = count( $post_types );
							// Translators: Items
							printf( _n( '%d item', '%d items', $count ), $count );
							?>
						</span>
						<br class="clear">
					</div>
				</div>

			</div>
				<?php
			}
		}
		if ( 'cptm_tax' == $post_type ) {

			// Get all public custom Taxonomies
			$taxonomies = get_taxonomies(
				array(
					'public'   => true,
					'_builtin' => false,
				),
				'objects'
			);
			// Get all custom Taxonomies created by Custom Post Type Maker
			$cptm_tax_posts = get_posts( array( 'post_type' => 'cptm_tax' ) );
			// Remove all custom Taxonomies created by the Custom Post Type Maker plugin
			foreach ( $cptm_tax_posts as $cptm_tax_post ) {
				$values = get_post_custom( $cptm_tax_post->ID );
				unset( $taxonomies[ $values['cptm_tax_name'][0] ] );
			}

			if ( count( $taxonomies ) != 0 ) {
				?>
			<div id="cptm-cpt-overview" class="hidden">
				<div id="icon-edit" class="icon32 icon32-posts-cptm"><br></div>
				<h2><?php _e( 'Other registered custom Taxonomies', 'custom-post-type-maker' ); ?></h2>
				<p><?php _e( 'The custom Taxonomies below are registered in WordPress but were not created by the Custom Post Type Maker plugin.', 'custom-post-type-maker' ); ?></p>
				<table class="wp-list-table widefat fixed posts" cellspacing="0">
					<thead>
						<tr>
							<th scope="col" id="cb" class="manage-column column-cb check-column">
							</th>
							<th scope="col" id="title" class="manage-column column-title">
								<span><?php _e( 'Taxonomy', 'custom-post-type-maker' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" id="custom_post_type_name" class="manage-column column-custom_taxonomy_name">
								<span><?php _e( 'Custom Taxonomy Name', 'custom-post-type-maker' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" id="label" class="manage-column column-label">
								<span><?php _e( 'Label', 'custom-post-type-maker' ); ?></span><span class="sorting-indicator"></span>
							</th>
						</tr>
					</thead>

					<tfoot>
						<tr>
							<th scope="col" class="manage-column column-cb check-column">
							</th>
							<th scope="col" class="manage-column column-title">
								<span><?php _e( 'Taxonomy', 'custom-post-type-maker' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" class="manage-column column-custom_post_type_name">
								<span><?php _e( 'Custom Taxonomy Name', 'custom-post-type-maker' ); ?></span><span class="sorting-indicator"></span>
							</th>
							<th scope="col" class="manage-column column-label">
								<span><?php _e( 'Label', 'custom-post-type-maker' ); ?></span><span class="sorting-indicator"></span>
							</th>
						</tr>
					</tfoot>

					<tbody id="the-list">
						<?php
						// Create list of all other registered Custom Post Types
						foreach ( $taxonomies as $taxonomy ) {
							?>
						<tr valign="top">
							<th scope="row" class="check-column">
							</th>
							<td class="post-title page-title column-title">
								<strong><?php echo $taxonomy->labels->name; ?></strong>
							</td>
							<td class="custom_post_type_name column-custom_post_type_name"><?php echo $taxonomy->name; ?></td>
							<td class="label column-label"><?php echo $taxonomy->labels->name; ?></td>
						</tr>
							<?php
						}

						if ( count( $taxonomies ) == 0 ) {
							?>
						<tr class="no-items"><td class="colspanchange" colspan="4"><?php _e( 'No custom Taxonomies found', 'custom-post-type-maker' ); ?>.</td></tr>
							<?php
						}
						?>
					</tbody>
				</table>

				<div class="tablenav bottom">
					<div class="tablenav-pages one-page">
						<span class="displaying-num">
							<?php
							$count = count( $taxonomies );
							// Translators: Items
							printf( _n( '%d item', '%d items', $count ), $count );
							?>
						</span>
						<br class="clear">
					</div>
				</div>

			</div>
				<?php
			}
		}
	}

	/**
	 * Update messages
	 *
	 * @param  array $messages Update messages
	 * @return array           Update messages
	 */
	function cptm_post_updated_messages( $messages ) {
		global $post, $post_ID;

		$messages['cptm'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Custom Post Type updated.', 'custom-post-type-maker' ),
			2  => __( 'Custom Post Type updated.', 'custom-post-type-maker' ),
			3  => __( 'Custom Post Type deleted.', 'custom-post-type-maker' ),
			4  => __( 'Custom Post Type updated.', 'custom-post-type-maker' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Custom Post Type restored to revision from %s', 'custom-post-type-maker' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Custom Post Type published.', 'custom-post-type-maker' ),
			7  => __( 'Custom Post Type saved.', 'custom-post-type-maker' ),
			8  => __( 'Custom Post Type submitted.', 'custom-post-type-maker' ),
			9  => __( 'Custom Post Type scheduled for.', 'custom-post-type-maker' ),
			10 => __( 'Custom Post Type draft updated.', 'custom-post-type-maker' ),
		);

		return $messages;
	}

	/**
	 * Prepare attachment for Ajax Upload Request
	 * @param  array  $response    Response
	 * @param  string $attachment  File contents
	 * @param  array  $meta        File meta contents
	 *
	 * @return array               Modified response
	 */
	function wp_prepare_attachment_for_js( $response, $attachment, $meta ) {
		// only for image
		if ( $response['type'] != 'image' ) {
			return $response;
		}

		$attachment_url = $response['url'];
		$base_url       = str_replace( wp_basename( $attachment_url ), '', $attachment_url );

		if ( isset( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
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
