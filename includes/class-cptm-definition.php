<?php
/**
 * The definition mapper: stored meta ↔ register arguments ↔ clean input ↔ export.
 *
 * Every path that reads or writes a definition goes through this class: the
 * registration on init, the edit form, the JSON import and export, the Custom
 * Post Type UI import, WP-CLI and the "Get code" export. The stored format is
 * contract C-001 (docs/agents/CONTRACTS.md).
 *
 * @package Custom_Post_Types_Maker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Definition mapper.
 */
final class Cptm_Definition {
	/** Export format version (C-008). */
	const FORMAT = 1;

	/** Max post type name length (WordPress limit). */
	const MAX_POST_TYPE_LENGTH = 20;

	/** Max taxonomy name length (WordPress limit). */
	const MAX_TAXONOMY_LENGTH = 32;

	/** Values allowed in cptm_supports. */
	const SUPPORTS = array( 'title', 'editor', 'excerpt', 'trackbacks', 'custom-fields', 'comments', 'revisions', 'thumbnail', 'author', 'page-attributes', 'post-formats' );

	/** Values allowed in cptm_builtin_taxonomies. */
	const BUILTIN_TAXONOMIES = array( 'category', 'post_tag' );

	/** Values allowed in cptm_template_lock. */
	const TEMPLATE_LOCKS = array( '', 'all', 'insert', 'contentOnly' );

	/** Post type names that WordPress core uses or reserves. */
	const RESERVED_POST_TYPES = array( 'post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation', 'wp_font_family', 'wp_font_face', 'action', 'author', 'order', 'theme', 'cptm', 'cptm_tax' );

	/** Taxonomy names that WordPress core uses or reserves (registered taxonomies and public query vars). */
	const RESERVED_TAXONOMIES = array( 'category', 'post_tag', 'nav_menu', 'link_category', 'post_format', 'wp_theme', 'wp_template_part_area', 'wp_pattern_category', 'attachment', 'attachment_id', 'author', 'author_name', 'calendar', 'cat', 'category_name', 'category__and', 'category__in', 'category__not_in', 'comments_per_page', 'comments_popup', 'custom', 'customize_messenger_channel', 'customized', 'cpage', 'day', 'debug', 'embed', 'error', 'exact', 'feed', 'fields', 'hour', 'm', 'minute', 'monthnum', 'more', 'name', 'nonce', 'nopaging', 'offset', 'order', 'orderby', 'p', 'page', 'page_id', 'paged', 'pagename', 'pb', 'perm', 'post', 'post__in', 'post__not_in', 'post_mime_type', 'post_status', 'post_type', 'posts', 'posts_per_archive_page', 'posts_per_page', 'preview', 'robots', 's', 'search', 'second', 'sentence', 'showposts', 'static', 'status', 'subpost', 'subpost_id', 'tag', 'tag__and', 'tag__in', 'tag__not_in', 'tag_id', 'tag_slug__and', 'tag_slug__in', 'taxonomy', 'tb', 'term', 'terms', 'theme', 'title', 'type', 'types', 'w', 'withcomments', 'withoutcomments', 'year' );

	/**
	 * The meta keys of each kind, grouped by how they are cleaned. Order is the export order.
	 */
	const KEYS = array(
		'cptm'     => array(
			'name'     => 'cptm_name',
			'text'     => array( 'cptm_label', 'cptm_singular_name', 'cptm_custom_rewrite_slug' ),
			'flags'    => array( 'cptm_public', 'cptm_show_ui', 'cptm_has_archive', 'cptm_exclude_from_search', 'cptm_hierarchical', 'cptm_rewrite', 'cptm_withfront', 'cptm_feeds', 'cptm_pages', 'cptm_query_var', 'cptm_show_in_rest', 'cptm_publicly_queryable', 'cptm_show_in_menu' ),
			'optional' => array( 'cptm_show_in_nav_menus', 'cptm_show_in_admin_bar', 'cptm_can_export', 'cptm_delete_with_user', 'cptm_embeddable' ),
			'paths'    => array( 'cptm_archive_slug', 'cptm_rest_base', 'cptm_rest_namespace' ),
			'arrays'   => array( 'cptm_supports', 'cptm_builtin_taxonomies' ),
			'special'  => array( 'cptm_description', 'cptm_icon_slug', 'cptm_icon_url', 'cptm_menu_position', 'cptm_capability_type', 'cptm_template', 'cptm_template_lock' ),
		),
		'cptm_tax' => array(
			'name'     => 'cptm_tax_name',
			'text'     => array( 'cptm_tax_label', 'cptm_tax_singular_name', 'cptm_tax_custom_rewrite_slug', 'cptm_tax_default_term' ),
			'flags'    => array( 'cptm_tax_show_ui', 'cptm_tax_hierarchical', 'cptm_tax_rewrite', 'cptm_tax_query_var', 'cptm_tax_show_in_rest', 'cptm_tax_show_admin_column' ),
			'optional' => array( 'cptm_tax_public', 'cptm_tax_publicly_queryable', 'cptm_tax_show_in_nav_menus', 'cptm_tax_show_tagcloud', 'cptm_tax_show_in_quick_edit', 'cptm_tax_sort' ),
			'paths'    => array( 'cptm_tax_rest_base' ),
			'arrays'   => array( 'cptm_tax_post_types' ),
			'special'  => array(),
		),
	);

	/* ------------------------------------------------------------------ */
	/* Reading stored meta (C-001)                                          */
	/* ------------------------------------------------------------------ */

	/**
	 * One raw meta value, or null when the key does not exist.
	 *
	 * @param  array  $meta Raw meta: get_post_meta( $id ), values are lists of raw strings.
	 * @param  string $key  Meta key.
	 * @return string|null
	 */
	public static function value( array $meta, string $key ): ?string {
		return isset( $meta[ $key ][0] ) ? (string) $meta[ $key ][0] : null;
	}

	/**
	 * A '1'/'0' flag. A missing key gives $default.
	 *
	 * @param  array  $meta    Raw meta.
	 * @param  string $key     Meta key.
	 * @param  bool   $default Value for a missing key.
	 * @return bool
	 */
	public static function flag( array $meta, string $key, bool $default = false ): bool {
		$value = self::value( $meta, $key );
		return null === $value ? $default : '1' === $value;
	}

	/**
	 * A setting added in 1.3.0 or later: '1', '0' or '' (WordPress default). Null means "do not pass the argument" (PATTERN-007).
	 *
	 * @param  array  $meta Raw meta.
	 * @param  string $key  Meta key.
	 * @return bool|null
	 */
	public static function optional_flag( array $meta, string $key ): ?bool {
		$value = self::value( $meta, $key );
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
	public static function list_value( ?string $raw ): array {
		if ( null === $raw || '' === $raw || ! is_serialized( $raw ) ) {
			return array();
		}
		$value = unserialize( $raw, array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- classes are not allowed.
		return is_array( $value ) ? array_values( array_filter( $value, 'is_string' ) ) : array();
	}

	/**
	 * All known meta of a definition as plain values (lists for the array keys). Used for export.
	 *
	 * @param  string $kind 'cptm' or 'cptm_tax'.
	 * @param  int    $id   Definition post ID.
	 * @return array<string, string|string[]>
	 */
	public static function flat( string $kind, int $id ): array {
		$raw  = get_post_meta( $id );
		$flat = array();
		foreach ( self::all_keys( $kind ) as $key ) {
			if ( ! array_key_exists( $key, $raw ) ) {
				continue;
			}
			$flat[ $key ] = in_array( $key, self::KEYS[ $kind ]['arrays'], true ) ? self::list_value( self::value( $raw, $key ) ) : (string) self::value( $raw, $key );
		}
		return $flat;
	}

	/**
	 * Turn plain values back into the raw meta shape that the argument builders read.
	 *
	 * @param  array $flat Plain values.
	 * @return array
	 */
	public static function raw_from_flat( array $flat ): array {
		$raw = array();
		foreach ( $flat as $key => $value ) {
			$raw[ $key ] = array( is_array( $value ) ? serialize( array_values( $value ) ) : (string) $value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- same format as update_post_meta().
		}
		return $raw;
	}

	/**
	 * Every known meta key of a kind.
	 *
	 * @param  string $kind 'cptm' or 'cptm_tax'.
	 * @return string[]
	 */
	public static function all_keys( string $kind ): array {
		$k = self::KEYS[ $kind ];
		return array_merge( array( $k['name'] ), $k['text'], $k['special'], $k['flags'], $k['optional'], $k['paths'], $k['arrays'] );
	}

	/* ------------------------------------------------------------------ */
	/* Registration arguments                                               */
	/* ------------------------------------------------------------------ */

	/**
	 * Whether a capability type is a custom one (not post or page).
	 *
	 * @param  string $capability_type Stored capability type.
	 * @return bool
	 */
	public static function is_custom_capability_type( string $capability_type ): bool {
		return '' !== $capability_type && ! in_array( $capability_type, array( 'post', 'page' ), true );
	}

	/**
	 * The name and arguments of a custom post type. Reads the meta exactly as 1.2.0 did (C-001).
	 *
	 * @param  array $meta Raw meta of a post type definition.
	 * @return array{name: string, args: array}|null Null when the definition has no name.
	 */
	public static function post_type_registration( array $meta ): ?array {
		// Text, escaped as in 1.2.0, so existing names do not change.
		$name          = esc_html( self::value( $meta, 'cptm_name' ) ?: 'no_name' );
		$label         = esc_html( self::value( $meta, 'cptm_label' ) ?: $name );
		$singular_name = esc_html( self::value( $meta, 'cptm_singular_name' ) ?: $label );

		if ( 'no_name' === $name ) {
			return null;
		}

		// A Dashicon slug overrides an uploaded icon (D-004).
		$icon_slug = self::value( $meta, 'cptm_icon_slug' );
		$icon_url  = self::value( $meta, 'cptm_icon_url' );
		$menu_icon = $icon_slug ? sanitize_html_class( $icon_slug ) : ( $icon_url ? esc_url_raw( $icon_url ) : null );

		$custom_rewrite_slug = esc_html( self::value( $meta, 'cptm_custom_rewrite_slug' ) ?: $name );
		$menu_position       = self::value( $meta, 'cptm_menu_position' ) ? (int) self::value( $meta, 'cptm_menu_position' ) : null;
		$has_archive         = self::flag( $meta, 'cptm_has_archive' );
		$archive_slug        = self::value( $meta, 'cptm_archive_slug' );
		$capability_type     = self::value( $meta, 'cptm_capability_type' ) ?: 'post';

		// Rewrite: with_front is always set, also when false (D-005).
		$rewrite = array();
		if ( self::flag( $meta, 'cptm_rewrite' ) ) {
			$rewrite['slug'] = $custom_rewrite_slug;
		}
		$rewrite['with_front'] = self::flag( $meta, 'cptm_withfront' );
		if ( self::flag( $meta, 'cptm_feeds' ) ) {
			$rewrite['feeds'] = true;
		}
		if ( self::flag( $meta, 'cptm_pages' ) ) {
			$rewrite['pages'] = true;
		}

		// A missing supports key means title + editor.
		$supports_raw = self::value( $meta, 'cptm_supports' );

		$args = array(
			'labels'              => self::post_type_labels( $label, $singular_name ),
			'description'         => self::value( $meta, 'cptm_description' ) ?: '',
			'menu_icon'           => $menu_icon,
			'rewrite'             => $rewrite,
			'menu_position'       => $menu_position,
			'public'              => self::flag( $meta, 'cptm_public' ),
			'show_ui'             => self::flag( $meta, 'cptm_show_ui' ),
			'has_archive'         => ( $has_archive && $archive_slug ) ? $archive_slug : $has_archive,
			'exclude_from_search' => self::flag( $meta, 'cptm_exclude_from_search' ),
			'capability_type'     => $capability_type,
			'hierarchical'        => self::flag( $meta, 'cptm_hierarchical' ),
			'show_in_menu'        => self::flag( $meta, 'cptm_show_in_menu' ),
			'query_var'           => self::flag( $meta, 'cptm_query_var' ),
			'show_in_rest'        => self::flag( $meta, 'cptm_show_in_rest' ),
			// A missing key means true (D-002).
			'publicly_queryable'  => self::flag( $meta, 'cptm_publicly_queryable', true ),
			'supports'            => $supports_raw ? self::list_value( $supports_raw ) : array( 'title', 'editor' ),
			'taxonomies'          => self::list_value( self::value( $meta, 'cptm_builtin_taxonomies' ) ),
		);

		// A custom capability type gets its own capabilities (D-012).
		if ( self::is_custom_capability_type( $capability_type ) ) {
			$args['capability_type'] = array( $capability_type, $capability_type . 's' );
			$args['map_meta_cap']    = true;
		}

		// Settings added in 1.3.0 and later: pass them only when set (PATTERN-007).
		foreach ( array(
			'cptm_show_in_nav_menus' => 'show_in_nav_menus',
			'cptm_show_in_admin_bar' => 'show_in_admin_bar',
			'cptm_can_export'        => 'can_export',
			'cptm_delete_with_user'  => 'delete_with_user',
			'cptm_embeddable'        => 'embeddable',
		) as $key => $arg ) {
			$value = self::optional_flag( $meta, $key );
			if ( null !== $value ) {
				$args[ $arg ] = $value;
			}
		}
		foreach ( array(
			'cptm_rest_base'      => 'rest_base',
			'cptm_rest_namespace' => 'rest_namespace',
		) as $key => $arg ) {
			$value = self::value( $meta, $key );
			if ( $value ) {
				$args[ $arg ] = $value;
			}
		}
		$template = self::value( $meta, 'cptm_template' );
		if ( $template ) {
			$decoded = json_decode( $template, true );
			if ( is_array( $decoded ) && $decoded ) {
				$args['template'] = $decoded;
			}
		}
		$template_lock = self::value( $meta, 'cptm_template_lock' );
		if ( $template_lock && in_array( $template_lock, self::TEMPLATE_LOCKS, true ) ) {
			$args['template_lock'] = $template_lock;
		}

		return array(
			'name' => $name,
			'args' => $args,
		);
	}

	/**
	 * The name, object types and arguments of a custom taxonomy. Reads the meta exactly as 1.2.0 did (C-001).
	 *
	 * @param  array $meta Raw meta of a taxonomy definition.
	 * @return array{name: string, object_types: string[], args: array}|null Null when the definition has no name.
	 */
	public static function taxonomy_registration( array $meta ): ?array {
		$name          = esc_html( self::value( $meta, 'cptm_tax_name' ) ?: 'no_name' );
		$label         = esc_html( self::value( $meta, 'cptm_tax_label' ) ?: $name );
		$singular_name = esc_html( self::value( $meta, 'cptm_tax_singular_name' ) ?: $label );
		$rewrite_slug  = esc_html( self::value( $meta, 'cptm_tax_custom_rewrite_slug' ) ?: $name );

		if ( 'no_name' === $name ) {
			return null;
		}

		$args = array(
			'label'             => $label,
			'labels'            => self::taxonomy_labels( $label, $singular_name ),
			'rewrite'           => self::flag( $meta, 'cptm_tax_rewrite' ) ? array( 'slug' => $rewrite_slug ) : false,
			'show_ui'           => self::flag( $meta, 'cptm_tax_show_ui' ),
			'hierarchical'      => self::flag( $meta, 'cptm_tax_hierarchical' ),
			'query_var'         => self::flag( $meta, 'cptm_tax_query_var' ),
			'show_in_rest'      => self::flag( $meta, 'cptm_tax_show_in_rest' ),
			'show_admin_column' => self::flag( $meta, 'cptm_tax_show_admin_column' ),
		);

		foreach ( array(
			'cptm_tax_public'             => 'public',
			'cptm_tax_publicly_queryable' => 'publicly_queryable',
			'cptm_tax_show_in_nav_menus'  => 'show_in_nav_menus',
			'cptm_tax_show_tagcloud'      => 'show_tagcloud',
			'cptm_tax_show_in_quick_edit' => 'show_in_quick_edit',
			'cptm_tax_sort'               => 'sort',
		) as $key => $arg ) {
			$value = self::optional_flag( $meta, $key );
			if ( null !== $value ) {
				$args[ $arg ] = $value;
			}
		}
		$rest_base = self::value( $meta, 'cptm_tax_rest_base' );
		if ( $rest_base ) {
			$args['rest_base'] = $rest_base;
		}
		$default_term = self::value( $meta, 'cptm_tax_default_term' );
		if ( $default_term ) {
			$args['default_term'] = array( 'name' => $default_term );
		}

		return array(
			'name'         => $name,
			'object_types' => self::list_value( self::value( $meta, 'cptm_tax_post_types' ) ),
			'args'         => $args,
		);
	}

	/**
	 * The labels of a custom post type.
	 *
	 * @param  string $plural   Plural label.
	 * @param  string $singular Singular label.
	 * @return array<string, string>
	 */
	public static function post_type_labels( string $plural, string $singular ): array {
		$s = fn( string $text ): string => sprintf( $text, $singular );
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
	public static function taxonomy_labels( string $plural, string $singular ): array {
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

	/* ------------------------------------------------------------------ */
	/* Cleaning input (form, import, WP-CLI)                                */
	/* ------------------------------------------------------------------ */

	/**
	 * Clean definition input. Only keys present in $input are returned (a partial update).
	 *
	 * @param  string $kind         'cptm' or 'cptm_tax'.
	 * @param  array  $input        Meta key => value (strings, or lists for the array keys). Unslashed.
	 * @param  string $current_name The name stored before ('' for a new definition).
	 * @param  array  $opts         'allow_registered' (bool): accept a name another plugin registers (Custom Post Type UI import).
	 *                              'strict_object_types' (bool, default true): keep only post types that exist.
	 * @return array{meta: array, errors: string[]}
	 */
	public static function sanitize( string $kind, array $input, string $current_name = '', array $opts = array() ): array {
		$k      = self::KEYS[ $kind ];
		$meta   = array();
		$errors = array();
		$str    = static fn( $v ): string => is_scalar( $v ) ? (string) $v : '';

		if ( array_key_exists( $k['name'], $input ) ) {
			$name  = sanitize_key( str_replace( ' ', '', $str( $input[ $k['name'] ] ) ) );
			$error = self::name_error( $kind, $name, $current_name, ! empty( $opts['allow_registered'] ) );
			if ( $error ) {
				$errors[] = $error;
			} else {
				$meta[ $k['name'] ] = $name;
			}
		}

		foreach ( $k['text'] as $key ) {
			if ( array_key_exists( $key, $input ) ) {
				$meta[ $key ] = sanitize_text_field( $str( $input[ $key ] ) );
			}
		}

		foreach ( $k['flags'] as $key ) {
			$value = array_key_exists( $key, $input ) ? $str( $input[ $key ] ) : null;
			if ( '1' === $value || '0' === $value ) {
				$meta[ $key ] = $value;
			}
		}

		foreach ( $k['optional'] as $key ) {
			$value = array_key_exists( $key, $input ) ? $str( $input[ $key ] ) : null;
			if ( '1' === $value || '0' === $value || '' === $value ) {
				$meta[ $key ] = $value;
			}
		}

		foreach ( $k['paths'] as $key ) {
			if ( array_key_exists( $key, $input ) ) {
				$meta[ $key ] = self::clean_path( $str( $input[ $key ] ) );
			}
		}

		foreach ( $k['arrays'] as $key ) {
			if ( ! array_key_exists( $key, $input ) ) {
				continue;
			}
			$list = array();
			foreach ( (array) $input[ $key ] as $item ) {
				$item = sanitize_key( $str( $item ) );
				if ( '' !== $item ) {
					$list[] = $item;
				}
			}
			if ( 'cptm_supports' === $key ) {
				$list = array_intersect( $list, self::SUPPORTS );
			} elseif ( 'cptm_builtin_taxonomies' === $key ) {
				$list = array_intersect( $list, self::BUILTIN_TAXONOMIES );
			} elseif ( 'cptm_tax_post_types' === $key && ( $opts['strict_object_types'] ?? true ) ) {
				$list = array_filter( $list, 'post_type_exists' );
			}
			$meta[ $key ] = array_values( array_unique( $list ) );
		}

		if ( 'cptm' === $kind ) {
			// Stored HTML-encoded, as in 1.2.0 (C-001).
			if ( array_key_exists( 'cptm_description', $input ) ) {
				$meta['cptm_description'] = esc_textarea( sanitize_textarea_field( wp_specialchars_decode( $str( $input['cptm_description'] ), ENT_QUOTES ) ) );
			}
			if ( array_key_exists( 'cptm_icon_slug', $input ) ) {
				$meta['cptm_icon_slug'] = sanitize_html_class( $str( $input['cptm_icon_slug'] ) );
			}
			if ( array_key_exists( 'cptm_icon_url', $input ) ) {
				$meta['cptm_icon_url'] = esc_url_raw( $str( $input['cptm_icon_url'] ) );
			}
			if ( array_key_exists( 'cptm_menu_position', $input ) ) {
				$position                   = trim( $str( $input['cptm_menu_position'] ) );
				$meta['cptm_menu_position'] = is_numeric( $position ) ? (string) absint( $position ) : '';
			}
			if ( array_key_exists( 'cptm_capability_type', $input ) ) {
				$type = sanitize_key( $str( $input['cptm_capability_type'] ) );
				if ( '' === $type || strlen( $type ) > self::MAX_POST_TYPE_LENGTH ) {
					$errors[] = __( 'The capability type must be "post", "page", or a custom name of lower-case letters, numbers, dashes and underscores (at most 20 characters).', 'custom-post-type-maker' );
				} else {
					$meta['cptm_capability_type'] = $type;
				}
			}
			if ( array_key_exists( 'cptm_template', $input ) ) {
				$template = self::clean_template( $str( $input['cptm_template'] ) );
				if ( null === $template ) {
					$errors[] = __( 'The block template is not valid. Use a JSON list of blocks, for example [["core/paragraph", {"placeholder": "Text"}]].', 'custom-post-type-maker' );
				} else {
					$meta['cptm_template'] = $template;
				}
			}
			if ( array_key_exists( 'cptm_template_lock', $input ) && in_array( $str( $input['cptm_template_lock'] ), self::TEMPLATE_LOCKS, true ) ) {
				$meta['cptm_template_lock'] = $str( $input['cptm_template_lock'] );
			}
		}

		return array(
			'meta'   => $meta,
			'errors' => $errors,
		);
	}

	/**
	 * A REST base, REST namespace or archive slug: lower-case letters, numbers, dashes, underscores and slashes. Spaces become dashes.
	 *
	 * @param  string $value Input.
	 * @return string
	 */
	public static function clean_path( string $value ): string {
		$value = (string) preg_replace( '#\s+#', '-', trim( strtolower( $value ) ) );
		return trim( (string) preg_replace( '#[^a-z0-9_\-/]#', '', $value ), '/' );
	}

	/**
	 * A block template: a JSON list of [ block name, attributes?, inner blocks? ]. Returns '' for empty input and null when invalid.
	 *
	 * @param  string $json Input.
	 * @return string|null
	 */
	public static function clean_template( string $json ): ?string {
		if ( '' === trim( $json ) ) {
			return '';
		}
		$decoded = json_decode( $json, true );
		if ( ! is_array( $decoded ) || ! array_is_list( $decoded ) ) {
			return null;
		}
		$clean = self::clean_blocks( $decoded, 0 );
		return null === $clean ? null : (string) wp_json_encode( $clean );
	}

	/**
	 * Check a list of template blocks.
	 *
	 * @param  array $blocks Blocks.
	 * @param  int   $depth  Nesting depth.
	 * @return array|null
	 */
	private static function clean_blocks( array $blocks, int $depth ): ?array {
		if ( $depth > 10 ) {
			return null;
		}
		$out = array();
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) || ! isset( $block[0] ) || ! is_string( $block[0] ) || ! preg_match( '#^[a-z0-9-]+/[a-z0-9-]+$#', $block[0] ) ) {
				return null;
			}
			$item = array( $block[0], isset( $block[1] ) && is_array( $block[1] ) ? $block[1] : array() );
			if ( isset( $block[2] ) ) {
				if ( ! is_array( $block[2] ) ) {
					return null;
				}
				$inner = self::clean_blocks( $block[2], $depth + 1 );
				if ( null === $inner ) {
					return null;
				}
				$item[] = $inner;
			}
			$out[] = $item;
		}
		return $out;
	}

	/**
	 * Check a definition name. Returns an error message, or '' when the name is valid (D-010).
	 *
	 * @param  string $kind             'cptm' or 'cptm_tax'.
	 * @param  string $name             Sanitized name.
	 * @param  string $current          The name stored before.
	 * @param  bool   $allow_registered Accept a name that another plugin registers.
	 * @return string
	 */
	public static function name_error( string $kind, string $name, string $current, bool $allow_registered = false ): string {
		$taxonomy = 'cptm_tax' === $kind;
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
		$registered = $taxonomy ? taxonomy_exists( $name ) : post_type_exists( $name );
		if ( in_array( $name, $reserved, true ) || ( $registered && ! $allow_registered ) ) {
			/* translators: %s: the name */
			return sprintf( __( 'The name "%s" is reserved or already registered. Use a different name.', 'custom-post-type-maker' ), $name );
		}
		return '';
	}

	/* ------------------------------------------------------------------ */
	/* Writing                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * Write clean meta to a definition, then apply the side effects: capability grants (D-012), the icon size (C-007) and the rewrite flush flag.
	 *
	 * @param int   $id   Definition post ID.
	 * @param array $meta Clean meta from sanitize().
	 */
	public static function save( int $id, array $meta ): void {
		if ( ! empty( $meta['cptm_icon_url'] ) ) {
			$meta['cptm_icon_url'] = self::icon_url( $meta['cptm_icon_url'] );
		}
		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
		$capability_type = (string) get_post_meta( $id, 'cptm_capability_type', true );
		if ( 'cptm' === get_post_type( $id ) && self::is_custom_capability_type( $capability_type ) ) {
			self::grant_capabilities( $capability_type );
		}
		update_option( 'cptm_plugin_settings_changed', true );
	}

	/**
	 * Add the primitive capabilities of a custom capability type to the Administrator role (D-012).
	 *
	 * @param string $capability_type Custom capability type, singular.
	 */
	public static function grant_capabilities( string $capability_type ): void {
		$role = get_role( 'administrator' );
		if ( ! $role ) {
			return;
		}
		$caps = (array) get_post_type_capabilities(
			(object) array(
				'capability_type' => array( $capability_type, $capability_type . 's' ),
				'map_meta_cap'    => true,
				'capabilities'    => array(),
			)
		);
		// Meta capabilities are mapped by WordPress; "read" belongs to every role already.
		$primitive = array_values( array_unique( array_diff( array_values( $caps ), array( 'read', "edit_{$capability_type}", "read_{$capability_type}", "delete_{$capability_type}" ) ) ) );
		foreach ( $primitive as $cap ) {
			$role->add_cap( $cap );
		}
		$granted = (array) get_option( 'cptm_granted_caps', array() );
		update_option( 'cptm_granted_caps', array_values( array_unique( array_merge( $granted, $primitive ) ) ), false );
	}

	/**
	 * The 16×16 icon URL for an uploaded image, made on demand (C-007). Returns the input when no icon can be made.
	 *
	 * @param  string $url Image URL from the icon picker.
	 * @return string
	 */
	public static function icon_url( string $url ): string {
		if ( defined( 'CPTM_DONT_GENERATE_ICON' ) ) {
			return $url;
		}
		$id = attachment_url_to_postid( $url );
		if ( ! $id || ! wp_attachment_is_image( $id ) ) {
			return $url;
		}
		$metadata = wp_get_attachment_metadata( $id );
		if ( ! is_array( $metadata ) ) {
			return $url;
		}
		if ( empty( $metadata['sizes']['cptm_icon'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$size = image_make_intermediate_size( (string) get_attached_file( $id ), 16, 16, true );
			if ( ! $size ) {
				return $url;
			}
			$metadata['sizes']['cptm_icon'] = $size;
			wp_update_attachment_metadata( $id, $metadata );
		}
		$full = (string) wp_get_attachment_url( $id );
		return str_replace( wp_basename( $full ), $metadata['sizes']['cptm_icon']['file'], $full );
	}

	/* ------------------------------------------------------------------ */
	/* Finding, exporting and importing (C-008)                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Definitions of one kind.
	 *
	 * @param  string $kind   'cptm' or 'cptm_tax'.
	 * @param  string $status 'publish' or 'any'.
	 * @return WP_Post[]
	 */
	public static function posts( string $kind, string $status = 'any' ): array {
		return get_posts(
			array(
				'numberposts'      => -1,
				'post_type'        => $kind,
				'post_status'      => 'any' === $status ? array( 'publish', 'draft', 'pending', 'private', 'future' ) : $status,
				'suppress_filters' => false,
				'orderby'          => 'title',
				'order'            => 'ASC',
			)
		);
	}

	/**
	 * Find a definition by name.
	 *
	 * @param  string $kind 'cptm' or 'cptm_tax'.
	 * @param  string $name Post type or taxonomy name.
	 * @return int Post ID, or 0.
	 */
	public static function find( string $kind, string $name ): int {
		foreach ( self::posts( $kind ) as $post ) {
			if ( get_post_meta( $post->ID, self::KEYS[ $kind ]['name'], true ) === $name ) {
				return $post->ID;
			}
		}
		return 0;
	}

	/**
	 * Export definitions in the C-008 format.
	 *
	 * @param  int[]|null $ids Definition post IDs, or null for all.
	 * @return array
	 */
	public static function export( ?array $ids = null ): array {
		$data = array(
			'generator'  => 'custom-post-type-maker',
			'format'     => self::FORMAT,
			'post_types' => array(),
			'taxonomies' => array(),
		);
		foreach ( array(
			'cptm'     => 'post_types',
			'cptm_tax' => 'taxonomies',
		) as $kind => $group ) {
			foreach ( self::posts( $kind ) as $post ) {
				if ( null !== $ids && ! in_array( $post->ID, $ids, true ) ) {
					continue;
				}
				$data[ $group ][] = array(
					'title'  => $post->post_title,
					'status' => $post->post_status,
					'meta'   => self::flat( $kind, $post->ID ),
				);
			}
		}
		return $data;
	}

	/**
	 * Import definitions in the C-008 format.
	 *
	 * @param  array $data    Decoded export data.
	 * @param  array $opts    'mode': 'skip' (default) or 'replace' for an existing name;
	 *                        'publish' (bool, default false): publish new definitions, else save them as drafts;
	 *                        'allow_registered' (bool): accept names that another plugin registers.
	 * @return array{created: string[], updated: string[], skipped: string[], errors: string[]}
	 */
	public static function import( array $data, array $opts = array() ): array {
		$result = array(
			'created' => array(),
			'updated' => array(),
			'skipped' => array(),
			'errors'  => array(),
		);
		if ( ! isset( $data['post_types'] ) && ! isset( $data['taxonomies'] ) ) {
			$result['errors'][] = __( 'The file is not a Custom Post Type Maker export.', 'custom-post-type-maker' );
			return $result;
		}
		$mode    = ( $opts['mode'] ?? 'skip' ) === 'replace' ? 'replace' : 'skip';
		$publish = ! empty( $opts['publish'] );

		// Post types first, so taxonomies can link to them.
		foreach ( array(
			'post_types' => 'cptm',
			'taxonomies' => 'cptm_tax',
		) as $group => $kind ) {
			foreach ( (array) ( $data[ $group ] ?? array() ) as $item ) {
				if ( ! is_array( $item ) || ! isset( $item['meta'] ) || ! is_array( $item['meta'] ) ) {
					$result['errors'][] = __( 'One item has no settings and was ignored.', 'custom-post-type-maker' );
					continue;
				}
				$name_key = self::KEYS[ $kind ]['name'];
				$name     = sanitize_key( is_scalar( $item['meta'][ $name_key ] ?? null ) ? (string) $item['meta'][ $name_key ] : '' );
				$existing = '' !== $name ? self::find( $kind, $name ) : 0;

				if ( $existing && 'skip' === $mode ) {
					$result['skipped'][] = $name;
					continue;
				}

				$input = array_intersect_key( $item['meta'], array_flip( self::all_keys( $kind ) ) );
				$clean = self::sanitize(
					$kind,
					$input,
					$existing ? $name : '',
					array(
						'allow_registered'    => ! empty( $opts['allow_registered'] ),
						'strict_object_types' => false,
					)
				);
				if ( $clean['errors'] ) {
					/* translators: 1: name, 2: error message */
					$result['errors'][] = sprintf( __( '%1$s: %2$s', 'custom-post-type-maker' ), $name ?: '?', implode( ' ', $clean['errors'] ) );
					continue;
				}

				$title = sanitize_text_field( is_scalar( $item['title'] ?? null ) ? (string) $item['title'] : '' );
				$title = $title ?: (string) ( $clean['meta'][ 'cptm' === $kind ? 'cptm_label' : 'cptm_tax_label' ] ?? $name );

				if ( $existing ) {
					// Replace: remove the old settings first, so the result equals the imported item.
					foreach ( self::all_keys( $kind ) as $key ) {
						if ( $key !== $name_key ) {
							delete_post_meta( $existing, $key );
						}
					}
					wp_update_post(
						array(
							'ID'         => $existing,
							'post_title' => $title,
						)
					);
					self::save( $existing, $clean['meta'] );
					$result['updated'][] = $name;
				} else {
					$id = wp_insert_post(
						array(
							'post_type'   => $kind,
							'post_status' => $publish ? 'publish' : 'draft',
							'post_title'  => $title,
						),
						true
					);
					if ( is_wp_error( $id ) ) {
						$result['errors'][] = $name . ': ' . $id->get_error_message();
						continue;
					}
					self::save( $id, $clean['meta'] );
					$result['created'][] = $name;
				}
			}
		}
		return $result;
	}

	/**
	 * Map the settings of Custom Post Type UI to the C-008 format. Reads its options only; never changes them.
	 *
	 * @return array
	 */
	public static function from_cptui(): array {
		$bool = static fn( $v ): string => ( 'true' === $v || true === $v || '1' === $v || 1 === $v ) ? '1' : '0';
		$opt  = static fn( $v ): string => ( null === $v || '' === $v ) ? '' : ( ( 'true' === $v || true === $v ) ? '1' : '0' );
		$data = array(
			'generator'  => 'custom-post-type-ui',
			'format'     => self::FORMAT,
			'post_types' => array(),
			'taxonomies' => array(),
		);

		foreach ( (array) get_option( 'cptui_post_types', array() ) as $pt ) {
			if ( ! is_array( $pt ) || empty( $pt['name'] ) ) {
				continue;
			}
			$icon = (string) ( $pt['menu_icon'] ?? '' );
			$meta = array(
				'cptm_name'                => $pt['name'],
				'cptm_label'               => $pt['label'] ?? '',
				'cptm_singular_name'       => $pt['singular_label'] ?? '',
				'cptm_description'         => $pt['description'] ?? '',
				'cptm_public'              => $bool( $pt['public'] ?? 'true' ),
				'cptm_publicly_queryable'  => $bool( $pt['publicly_queryable'] ?? 'true' ),
				'cptm_show_ui'             => $bool( $pt['show_ui'] ?? 'true' ),
				'cptm_show_in_menu'        => $bool( $pt['show_in_menu'] ?? 'true' ),
				'cptm_show_in_nav_menus'   => $opt( $pt['show_in_nav_menus'] ?? '' ),
				'cptm_show_in_rest'        => $bool( $pt['show_in_rest'] ?? 'true' ),
				'cptm_rest_base'           => $pt['rest_base'] ?? '',
				'cptm_rest_namespace'      => $pt['rest_namespace'] ?? '',
				'cptm_has_archive'         => $bool( $pt['has_archive'] ?? 'false' ),
				'cptm_archive_slug'        => $pt['has_archive_string'] ?? '',
				'cptm_exclude_from_search' => $bool( $pt['exclude_from_search'] ?? 'false' ),
				'cptm_capability_type'     => $pt['capability_type'] ?? 'post',
				'cptm_hierarchical'        => $bool( $pt['hierarchical'] ?? 'false' ),
				'cptm_can_export'          => $opt( $pt['can_export'] ?? '' ),
				'cptm_delete_with_user'    => $opt( $pt['delete_with_user'] ?? '' ),
				'cptm_rewrite'             => $bool( $pt['rewrite'] ?? 'true' ),
				'cptm_custom_rewrite_slug' => $pt['rewrite_slug'] ?? '',
				'cptm_withfront'           => $bool( $pt['rewrite_withfront'] ?? 'true' ),
				'cptm_feeds'               => '0',
				'cptm_pages'               => '1',
				'cptm_query_var'           => $bool( $pt['query_var'] ?? 'true' ),
				'cptm_menu_position'       => (string) ( $pt['menu_position'] ?? '' ),
				'cptm_icon_slug'           => str_starts_with( $icon, 'dashicons-' ) ? $icon : '',
				'cptm_icon_url'            => str_starts_with( $icon, 'http' ) ? $icon : '',
				'cptm_supports'            => array_values( array_filter( (array) ( $pt['supports'] ?? array() ), 'is_string' ) ),
				'cptm_builtin_taxonomies'  => array_values( array_intersect( (array) ( $pt['taxonomies'] ?? array() ), self::BUILTIN_TAXONOMIES ) ),
			);
			$data['post_types'][] = array(
				'title' => $pt['label'] ?? $pt['name'],
				'meta'  => $meta,
			);
		}

		foreach ( (array) get_option( 'cptui_taxonomies', array() ) as $tax ) {
			if ( ! is_array( $tax ) || empty( $tax['name'] ) ) {
				continue;
			}
			$meta = array(
				'cptm_tax_name'                => $tax['name'],
				'cptm_tax_label'               => $tax['label'] ?? '',
				'cptm_tax_singular_name'       => $tax['singular_label'] ?? '',
				'cptm_tax_public'              => $opt( $tax['public'] ?? '' ),
				'cptm_tax_publicly_queryable'  => $opt( $tax['publicly_queryable'] ?? '' ),
				'cptm_tax_hierarchical'        => $bool( $tax['hierarchical'] ?? 'false' ),
				'cptm_tax_show_ui'             => $bool( $tax['show_ui'] ?? 'true' ),
				'cptm_tax_show_in_nav_menus'   => $opt( $tax['show_in_nav_menus'] ?? '' ),
				'cptm_tax_query_var'           => $bool( $tax['query_var'] ?? 'true' ),
				'cptm_tax_rewrite'             => $bool( $tax['rewrite'] ?? 'true' ),
				'cptm_tax_custom_rewrite_slug' => $tax['rewrite_slug'] ?? '',
				'cptm_tax_show_admin_column'   => $bool( $tax['show_admin_column'] ?? 'false' ),
				'cptm_tax_show_in_rest'        => $bool( $tax['show_in_rest'] ?? 'true' ),
				'cptm_tax_rest_base'           => $tax['rest_base'] ?? '',
				'cptm_tax_show_tagcloud'       => $opt( $tax['show_tagcloud'] ?? '' ),
				'cptm_tax_show_in_quick_edit'  => $opt( $tax['show_in_quick_edit'] ?? '' ),
				'cptm_tax_sort'                => $opt( $tax['sort'] ?? '' ),
				'cptm_tax_default_term'        => is_string( $tax['default_term'] ?? null ) ? explode( ',', $tax['default_term'] )[0] : '',
				'cptm_tax_post_types'          => array_values( array_filter( (array) ( $tax['object_types'] ?? array() ), 'is_string' ) ),
			);
			$data['taxonomies'][] = array(
				'title' => $tax['label'] ?? $tax['name'],
				'meta'  => $meta,
			);
		}

		return $data;
	}

	/* ------------------------------------------------------------------ */
	/* PHP code export                                                      */
	/* ------------------------------------------------------------------ */

	/**
	 * PHP code that registers the same post type or taxonomy without the plugin.
	 *
	 * @param  string $kind 'cptm' or 'cptm_tax'.
	 * @param  array  $raw  Raw meta.
	 * @return string '' when the definition has no name.
	 */
	public static function code( string $kind, array $raw ): string {
		if ( 'cptm' === $kind ) {
			$reg = self::post_type_registration( $raw );
			if ( ! $reg ) {
				return '';
			}
			$call = sprintf( "register_post_type( %s, %s );", var_export( $reg['name'], true ), self::export_value( $reg['args'], 2 ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- code generation.
		} else {
			$reg = self::taxonomy_registration( $raw );
			if ( ! $reg ) {
				return '';
			}
			$call = sprintf( "register_taxonomy( %s, %s, %s );", var_export( $reg['name'], true ), self::export_value( $reg['object_types'], 2 ), self::export_value( $reg['args'], 2 ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- code generation.
		}
		return "add_action(\n\t'init',\n\tfunction () {\n\t\t" . $call . "\n\t}\n);\n";
	}

	/**
	 * A PHP literal for a value, in WordPress coding style.
	 *
	 * @param  mixed $value  Value.
	 * @param  int   $indent Tabs before nested lines.
	 * @return string
	 */
	public static function export_value( $value, int $indent = 0 ): string {
		if ( is_array( $value ) ) {
			if ( ! $value ) {
				return 'array()';
			}
			$pad   = str_repeat( "\t", $indent + 1 );
			$list  = array_is_list( $value );
			$lines = array();
			foreach ( $value as $k => $v ) {
				$lines[] = $pad . ( $list ? '' : var_export( $k, true ) . ' => ' ) . self::export_value( $v, $indent + 1 ) . ','; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- code generation.
			}
			return "array(\n" . implode( "\n", $lines ) . "\n" . str_repeat( "\t", $indent ) . ')';
		}
		if ( null === $value ) {
			return 'null';
		}
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}
		return var_export( $value, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- code generation.
	}
}
