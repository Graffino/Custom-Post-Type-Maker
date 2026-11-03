<?php
/*
Plugin Name: Custom Post Type Maker
Plugin URI: https://github.com/Graffino/Custom-Post-Type-Maker
Description: Custom Post Type Maker lets you create Custom Post Types and custom Taxonomies in a user friendly way.
Version: 2.0.0
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
 * @version   2.0.0
 */

// Avoid direct calls to this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-cptm-definition.php';
require_once __DIR__ . '/includes/class-cptm-tools.php';
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/includes/class-cptm-cli.php';
}

/**
 * Main class to run the plugin
 *
 * @since 1.0.0
 */
class Cptm {
	/** Capability needed to manage definitions (D-008). */
	const CAPABILITY = 'manage_options';

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
		$this->version = '2.0.0';

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
		add_action( 'wp_initialize_site', array( $this, 'cptm_initialize_site' ) );

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

		new Cptm_Tools();
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

		// The 16x16 cptm_icon size is made on demand for the selected icon only (C-007).
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

		// "Copy code" buttons on the edit screens and the Tools page
		$screen = get_current_screen();
		if ( $this->is_plugin_screen( 'post' ) || ( $screen && Cptm_Tools::$hook === $screen->id ) ) {
			wp_enqueue_script( 'cptm_code_js', $this->dir . '/js/code.js', array( 'jquery' ), $this->version, true );
			wp_enqueue_style( 'cptm_tools_styles', $this->dir . '/css/tools.css', array(), $this->version );
			wp_localize_script( 'cptm_code_js', 'cptmCodeL10n', array( 'copied' => __( 'Copied.', 'custom-post-type-maker' ) ) );
		}
	}

	/**
	 * Create custom post types and custom taxonomies from the published definitions.
	 */
	public function cptm_create_custom_post_types(): void {
		foreach ( Cptm_Definition::posts( 'cptm', 'publish' ) as $cptm ) {
			$reg = Cptm_Definition::post_type_registration( get_post_meta( $cptm->ID ) );
			if ( $reg ) {
				register_post_type( $reg['name'], $reg['args'] );
			}
		}

		foreach ( Cptm_Definition::posts( 'cptm_tax', 'publish' ) as $cptm_tax ) {
			$reg = Cptm_Definition::taxonomy_registration( get_post_meta( $cptm_tax->ID ) );
			if ( $reg ) {
				register_taxonomy( $reg['name'], $reg['object_types'], $reg['args'] );
			}
		}
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
		foreach ( array( 'cptm', 'cptm_tax' ) as $kind ) {
			add_meta_box( 'cptm_code', __( 'Get code', 'custom-post-type-maker' ), array( $this, 'cptm_code_meta_box' ), $kind, 'side', 'low' );
		}
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
	 * The "Get code" meta box: PHP that registers the same post type or taxonomy without the plugin.
	 *
	 * @param WP_Post $post Definition.
	 */
	public function cptm_code_meta_box( WP_Post $post ): void {
		$code = Cptm_Definition::code( $post->post_type, get_post_meta( $post->ID ) );
		if ( '' === $code ) {
			echo '<p>' . esc_html__( 'Save the definition with a name to see its code.', 'custom-post-type-maker' ) . '</p>';
			return;
		}
		?>
		<p><?php esc_html_e( 'Paste this code in a theme or plugin to register the same type without Custom Post Type Maker. Then delete this definition.', 'custom-post-type-maker' ); ?></p>
		<textarea class="widefat code cptm-code" rows="12" readonly aria-label="<?php esc_attr_e( 'PHP code', 'custom-post-type-maker' ); ?>"><?php echo esc_textarea( $code ); ?></textarea>
		<p><button type="button" class="button cptm-copy-code"><?php esc_html_e( 'Copy code', 'custom-post-type-maker' ); ?></button> <span class="cptm-copied" aria-live="polite"></span></p>
		<?php
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
		$v        = fn( string $key ): string => (string) Cptm_Definition::value( $meta, $key );
		$is_new   = 'post-new.php' === $pagenow;
		$icon_url = $v( 'cptm_icon_url' );
		$icon     = $v( 'cptm_icon_slug' );

		$supports = $is_new ? array( 'title', 'editor', 'excerpt' ) : Cptm_Definition::list_value( Cptm_Definition::value( $meta, 'cptm_supports' ) );
		$taxs     = Cptm_Definition::list_value( Cptm_Definition::value( $meta, 'cptm_builtin_taxonomies' ) );

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
			<?php
			$capability_type = $v( 'cptm_capability_type' );
			$is_custom_type  = Cptm_Definition::is_custom_capability_type( $capability_type );
			?>
			<tr>
				<?php $this->label_cell( 'cptm_capability_type_select', __( 'Capability Type', 'custom-post-type-maker' ), array( __( 'The post type to use to build the read, edit, and delete capabilities.', 'custom-post-type-maker' ), __( 'Custom: the post type gets its own capabilities. Administrators get them automatically; give them to other roles with a role editor plugin.', 'custom-post-type-maker' ) ) ); ?>
				<td>
					<select name="cptm_capability_type_select" id="cptm_capability_type_select" tabindex="<?php echo (int) $this->tab++; ?>">
						<option value="post" <?php selected( $capability_type, 'post' ); ?>><?php esc_html_e( 'Post', 'custom-post-type-maker' ); ?> (<?php esc_html_e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="page" <?php selected( $capability_type, 'page' ); ?>><?php esc_html_e( 'Page', 'custom-post-type-maker' ); ?></option>
						<option value="custom" <?php selected( $is_custom_type ); ?>><?php esc_html_e( 'Custom', 'custom-post-type-maker' ); ?></option>
					</select>
					<input type="text" name="cptm_capability_type_custom" id="cptm_capability_type_custom" class="cptm-capability-custom" tabindex="<?php echo (int) $this->tab++; ?>" value="<?php echo esc_attr( $is_custom_type ? $capability_type : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. book', 'custom-post-type-maker' ); ?>" aria-label="<?php esc_attr_e( 'Custom capability type', 'custom-post-type-maker' ); ?>" />
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
			$this->optional_bool_row( 'cptm_embeddable', __( 'Embeddable', 'custom-post-type-maker' ), array( __( 'Whether other sites can embed posts of this type (WordPress 6.8 or later).', 'custom-post-type-maker' ) ), $v( 'cptm_embeddable' ) );

			$this->section_row( __( 'Block Editor', 'custom-post-type-maker' ) );
			?>
			<tr>
				<?php $this->label_cell( 'cptm_template', __( 'Block Template', 'custom-post-type-maker' ), array( __( 'The blocks a new post starts with, as a JSON list. "Show in REST" must be true.', 'custom-post-type-maker' ), __( 'e.g. [["core/image"], ["core/paragraph", {"placeholder": "Summary"}]]', 'custom-post-type-maker' ) ), true ); ?>
				<td>
					<textarea name="cptm_template" id="cptm_template" class="widefat code" tabindex="<?php echo (int) $this->tab++; ?>" rows="4"><?php echo esc_textarea( $v( 'cptm_template' ) ); ?></textarea>
				</td>
			</tr>
			<tr>
				<?php $this->label_cell( 'cptm_template_lock', __( 'Template Lock', 'custom-post-type-maker' ), array( __( 'Whether users can change the blocks of the template.', 'custom-post-type-maker' ) ) ); ?>
				<td>
					<select name="cptm_template_lock" id="cptm_template_lock" tabindex="<?php echo (int) $this->tab++; ?>">
						<option value="" <?php selected( $v( 'cptm_template_lock' ), '' ); ?>><?php esc_html_e( 'Not locked', 'custom-post-type-maker' ); ?> (<?php esc_html_e( 'default', 'custom-post-type-maker' ); ?>)</option>
						<option value="insert" <?php selected( $v( 'cptm_template_lock' ), 'insert' ); ?>><?php esc_html_e( 'Insert: no new blocks, no moves, no removals', 'custom-post-type-maker' ); ?></option>
						<option value="all" <?php selected( $v( 'cptm_template_lock' ), 'all' ); ?>><?php esc_html_e( 'All: blocks cannot be added, moved or removed', 'custom-post-type-maker' ); ?></option>
						<option value="contentOnly" <?php selected( $v( 'cptm_template_lock' ), 'contentOnly' ); ?>><?php esc_html_e( 'Content only: users edit the content of the blocks only', 'custom-post-type-maker' ); ?></option>
					</select>
				</td>
			</tr>
			<?php

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
		$v          = fn( string $key ): string => (string) Cptm_Definition::value( $meta, $key );
		$post_types = Cptm_Definition::list_value( Cptm_Definition::value( $meta, 'cptm_tax_post_types' ) );

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
			$this->optional_bool_row( 'cptm_tax_sort', __( 'Sort', 'custom-post-type-maker' ), array( __( 'Whether to remember the order in which terms are added to posts.', 'custom-post-type-maker' ) ), $v( 'cptm_tax_sort' ) );
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
	 * The posted values of the known keys of a kind, unslashed. Checkbox lists are always present (an empty list when nothing is checked).
	 *
	 * @param  string $kind 'cptm' or 'cptm_tax'.
	 * @return array
	 */
	private function posted_input( string $kind ): array {
		$input = array();
		foreach ( Cptm_Definition::all_keys( $kind ) as $key ) {
			if ( in_array( $key, Cptm_Definition::KEYS[ $kind ]['arrays'], true ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- verified in can_save(); sanitized by Cptm_Definition::sanitize().
				$input[ $key ] = isset( $_POST[ $key ] ) && is_array( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : array();
				continue;
			}
			$value = $this->posted( $key );
			if ( null !== $value ) {
				$input[ $key ] = $value;
			}
		}
		if ( 'cptm' === $kind ) {
			$select = $this->posted( 'cptm_capability_type_select' );
			if ( null !== $select ) {
				$input['cptm_capability_type'] = 'custom' === $select ? (string) $this->posted( 'cptm_capability_type_custom' ) : $select;
			}
		}
		return $input;
	}

	/**
	 * Clean and save the posted definition of a kind. Errors keep the old values and show a notice.
	 *
	 * @param int    $post_id WordPress Post ID
	 * @param string $kind    'cptm' or 'cptm_tax'.
	 */
	private function save_definition( int $post_id, string $kind ): void {
		if ( ! $this->can_save( $post_id ) ) {
			return;
		}
		$current = (string) get_post_meta( $post_id, Cptm_Definition::KEYS[ $kind ]['name'], true );
		$clean   = Cptm_Definition::sanitize( $kind, $this->posted_input( $kind ), $current );
		if ( $clean['errors'] ) {
			set_transient( 'cptm_notice_' . get_current_user_id(), implode( ' ', $clean['errors'] ), MINUTE_IN_SECONDS );
		}
		Cptm_Definition::save( $post_id, $clean['meta'] );
	}

	/**
	 * Save a post type definition
	 *
	 * @param int $post_id WordPress Post ID
	 */
	public function cptm_save_post( int $post_id ): void {
		$this->save_definition( $post_id, 'cptm' );
	}

	/**
	 * Save a taxonomy definition
	 *
	 * @param int $post_id WordPress Post ID
	 */
	public function cptm_tax_save_post( int $post_id ): void {
		$this->save_definition( $post_id, 'cptm_tax' );
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
	 * Flush rewrite rules on plugin activation. On network activation, every site flushes on its next admin visit.
	 *
	 * @param bool $network_wide Whether the plugin is network activated.
	 */
	public function cptm_plugin_activate_flush_rewrite( $network_wide = false ): void {
		if ( is_multisite() && $network_wide ) {
			foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $site_id ) {
				update_blog_option( (int) $site_id, 'cptm_plugin_settings_changed', true );
			}
			return;
		}
		$this->cptm_create_custom_post_types();
		flush_rewrite_rules();
	}

	/**
	 * A new site in a network where the plugin is network active.
	 *
	 * @param WP_Site $site New site.
	 */
	public function cptm_initialize_site( WP_Site $site ): void {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
			update_blog_option( (int) $site->blog_id, 'cptm_plugin_settings_changed', true );
		}
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
	 * Prepare attachment for Ajax Upload Request (C-007)
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
