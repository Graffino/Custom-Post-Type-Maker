<?php
/**
 * Smoke tests. Run with tests/run.sh (WordPress Playground). Never run on a real site: it creates and deletes data.
 *
 * @package Custom_Post_Types_Maker
 */

require __DIR__ . '/lib.php';

wp_set_current_user( 1 );
$cptm = $GLOBALS['cptm'];

/* 1. Data saved by 1.2.0 registers as before (C-001). */
$book = cptm_seed(
	'cptm',
	'Books',
	array(
		'cptm_name' => 'book', 'cptm_label' => 'Books', 'cptm_singular_name' => 'Book', 'cptm_description' => 'A &quot;book&quot; type',
		'cptm_icon_slug' => 'dashicons-book', 'cptm_public' => '1', 'cptm_show_ui' => '1', 'cptm_has_archive' => '1',
		'cptm_exclude_from_search' => '0', 'cptm_capability_type' => 'post', 'cptm_hierarchical' => '0', 'cptm_rewrite' => '1',
		'cptm_withfront' => '0', 'cptm_feeds' => '0', 'cptm_pages' => '1', 'cptm_custom_rewrite_slug' => 'books', 'cptm_query_var' => '1',
		'cptm_show_in_rest' => '1', 'cptm_menu_position' => '5', 'cptm_show_in_menu' => '1',
		'cptm_supports' => array( 'title', 'editor', 'thumbnail' ), 'cptm_builtin_taxonomies' => array( 'category' ),
	)
);
$old  = cptm_seed( 'cptm', 'Old', array( 'cptm_name' => 'oldtype', 'cptm_label' => 'Olds', 'cptm_public' => '1', 'cptm_show_ui' => '1', 'cptm_show_in_menu' => '1' ) );
$tax  = cptm_seed(
	'cptm_tax',
	'Genres',
	array(
		'cptm_tax_name' => 'genre', 'cptm_tax_label' => 'Genres', 'cptm_tax_singular_name' => 'Genre', 'cptm_tax_show_ui' => '1',
		'cptm_tax_hierarchical' => '1', 'cptm_tax_rewrite' => '1', 'cptm_tax_custom_rewrite_slug' => 'genres', 'cptm_tax_query_var' => '1',
		'cptm_tax_show_in_rest' => '1', 'cptm_tax_show_admin_column' => '1', 'cptm_tax_post_types' => array( 'book' ),
	)
);
$cptm->cptm_create_custom_post_types();

$o = get_post_type_object( 'book' );
cptm_ok( (bool) $o, 'book is registered' );
cptm_eq( array( 'slug' => 'books', 'with_front' => false, 'pages' => true, 'feeds' => true, 'ep_mask' => 1 ), $o->rewrite, 'book rewrite' );
cptm_eq( true, $o->has_archive, 'book has_archive' );
cptm_eq( 'dashicons-book', $o->menu_icon, 'book menu_icon' );
cptm_eq( 5, $o->menu_position, 'book menu_position' );
cptm_eq( 'A &quot;book&quot; type', $o->description, 'book description stays encoded' );
cptm_eq( array( 'title', 'editor', 'thumbnail', 'autosave' ), array_keys( get_all_post_type_supports( 'book' ) ), 'book supports' );
cptm_eq( array( 'category', 'genre' ), get_object_taxonomies( 'book' ), 'book taxonomies' );
cptm_eq( 'Book', $o->labels->singular_name, 'book singular label' );
$o2 = get_post_type_object( 'oldtype' );
cptm_eq( true, $o2->publicly_queryable, 'missing publicly_queryable means true (D-002)' );
cptm_eq( false, $o2->show_in_rest, 'missing show_in_rest means false' );
cptm_eq( array( 'title', 'editor', 'autosave' ), array_keys( get_all_post_type_supports( 'oldtype' ) ), 'missing supports means title + editor' );
cptm_eq( false, $o2->query_var, 'oldtype query_var' );
$t = get_taxonomy( 'genre' );
cptm_eq( array( 'book' ), $t->object_type, 'genre object types' );
cptm_eq( 'genres', $t->rewrite['slug'], 'genre rewrite slug' );
cptm_eq( true, $t->hierarchical, 'genre hierarchical' );

/* 2. Form save: hostile input is cleaned or rejected. */
cptm_form_save(
	$book,
	array(
		'cptm_name' => 'Bad Name<script>', 'cptm_label' => 'Books <b>x</b>', 'cptm_description' => '<script>alert(1)</script> & "q"',
		'cptm_supports' => array( 'title', 'evil<x>' ), 'cptm_builtin_taxonomies' => array( 'nav_menu' ), 'cptm_icon_url' => 'javascript:alert(1)',
		'cptm_public' => 'maybe', 'cptm_menu_position' => '5; DROP', 'cptm_capability_type_select' => 'post',
	)
);
cptm_eq( 'badnamescript', get_post_meta( $book, 'cptm_name', true ), 'name is sanitized with sanitize_key (D-010)' );
cptm_eq( 'Books x', get_post_meta( $book, 'cptm_label', true ), 'label tags are removed' );
cptm_eq( '&amp; &quot;q&quot;', get_post_meta( $book, 'cptm_description', true ), 'description script removed, stored encoded' );
cptm_eq( array( 'title' ), get_post_meta( $book, 'cptm_supports', true ), 'unknown supports value dropped' );
cptm_eq( array(), get_post_meta( $book, 'cptm_builtin_taxonomies', true ), 'unknown taxonomy dropped' );
cptm_eq( '', get_post_meta( $book, 'cptm_icon_url', true ), 'javascript: URL removed' );
cptm_eq( '1', get_post_meta( $book, 'cptm_public', true ), 'invalid flag ignored' );
cptm_eq( '', get_post_meta( $book, 'cptm_menu_position', true ), 'non-numeric menu position cleared' );
foreach ( array( 'post', str_repeat( 'a', 21 ), 'oldtype' ) as $bad ) {
	cptm_form_save( $book, array( 'cptm_name' => $bad ) );
	cptm_eq( 'badnamescript', get_post_meta( $book, 'cptm_name', true ), "name '{$bad}' is rejected" );
}
cptm_ok( (bool) get_transient( 'cptm_notice_1' ), 'a rejected name sets an admin notice' );
// A new request would no longer register the old name; this test runs in one request.
unregister_post_type( 'book' );
cptm_form_save( $book, array( 'cptm_name' => 'book', 'cptm_label' => 'Books', 'cptm_supports' => array( 'title', 'editor', 'thumbnail' ), 'cptm_builtin_taxonomies' => array( 'category' ), 'cptm_archive_slug' => ' All Books ' ) );
cptm_eq( 'book', get_post_meta( $book, 'cptm_name', true ), 'name restored' );
cptm_eq( 'all-books', get_post_meta( $book, 'cptm_archive_slug', true ), 'spaces in a slug become dashes' );
$cptm->cptm_create_custom_post_types(); // As the next request would.

/* 3. Only administrators manage definitions (D-008). */
$editor = wp_insert_user( array( 'user_login' => 'ed', 'user_pass' => wp_generate_password(), 'user_email' => 'ed@example.test', 'role' => 'editor' ) );
foreach ( array( 'cptm', 'cptm_tax' ) as $pt ) {
	$caps = get_post_type_object( $pt )->cap;
	cptm_eq( false, user_can( $editor, $caps->edit_posts ), "editor cannot list {$pt}" );
	cptm_eq( false, user_can( $editor, $caps->create_posts ), "editor cannot create {$pt}" );
}
cptm_eq( false, user_can( $editor, 'edit_post', $book ), 'editor cannot edit a definition' );
cptm_eq( true, user_can( 1, 'edit_post', $book ), 'admin can edit a definition' );
wp_set_current_user( $editor );
cptm_form_save( $book, array( 'cptm_label' => 'Hacked' ) );
wp_set_current_user( 1 );
cptm_eq( 'Books', get_post_meta( $book, 'cptm_label', true ), 'editor save is ignored' );

/* 4. New options reach WordPress. */
cptm_form_save(
	$book,
	array(
		'cptm_template' => '[["core/image"],["core/paragraph",{"placeholder":"Summary"}]]', 'cptm_template_lock' => 'insert',
		'cptm_embeddable' => '0', 'cptm_show_in_nav_menus' => '0', 'cptm_rest_base' => 'books', 'cptm_capability_type_select' => 'custom',
		'cptm_capability_type_custom' => 'book',
	)
);
$reg = Cptm_Definition::post_type_registration( get_post_meta( $book ) );
cptm_eq( array( array( 'core/image', array() ), array( 'core/paragraph', array( 'placeholder' => 'Summary' ) ) ), $reg['args']['template'] ?? null, 'template' );
cptm_eq( 'insert', $reg['args']['template_lock'] ?? null, 'template_lock' );
cptm_eq( false, $reg['args']['embeddable'] ?? null, 'embeddable' );
cptm_eq( false, $reg['args']['show_in_nav_menus'] ?? null, 'show_in_nav_menus' );
cptm_eq( 'books', $reg['args']['rest_base'] ?? null, 'rest_base' );
cptm_eq( array( 'book', 'books' ), $reg['args']['capability_type'], 'custom capability type' );
cptm_eq( true, $reg['args']['map_meta_cap'] ?? null, 'map_meta_cap for a custom capability type' );
$admin = get_role( 'administrator' );
cptm_ok( $admin->has_cap( 'edit_books' ) && $admin->has_cap( 'publish_books' ) && $admin->has_cap( 'delete_others_books' ), 'custom capabilities are granted to administrators (D-012)' );
cptm_ok( in_array( 'edit_books', (array) get_option( 'cptm_granted_caps' ), true ), 'granted capabilities are recorded' );
cptm_ok( ! array_key_exists( 'embeddable', Cptm_Definition::post_type_registration( get_post_meta( $old ) )['args'] ), 'optional settings are not passed for old definitions (PATTERN-007)' );
cptm_form_save( $book, array( 'cptm_template' => '[["not a block"]]' ) );
cptm_ok( '' !== get_post_meta( $book, 'cptm_template', true ), 'an invalid template keeps the old value' );
cptm_form_save( $tax, array( 'cptm_tax_sort' => '1', 'cptm_tax_post_types' => array( 'book', 'nosuchtype' ) ) );
$treg = Cptm_Definition::taxonomy_registration( get_post_meta( $tax ) );
cptm_eq( true, $treg['args']['sort'] ?? null, 'taxonomy sort' );
cptm_eq( array( 'book' ), $treg['object_types'], 'unknown post types are dropped from a taxonomy' );
cptm_form_save( $book, array( 'cptm_capability_type_select' => 'post' ) );

/* 5. Export → import gives the same definitions (C-008). */
$before = Cptm_Definition::export();
cptm_eq( 3, count( $before['post_types'] ) + count( $before['taxonomies'] ), 'export has every definition' );
$json   = wp_json_encode( $before );
foreach ( array( $book, $old, $tax ) as $id ) {
	wp_delete_post( $id, true );
}
// A new request would no longer register the deleted types.
unregister_post_type( 'book' );
unregister_post_type( 'oldtype' );
unregister_taxonomy( 'genre' );
$result = Cptm_Definition::import( json_decode( $json, true ), array( 'publish' => true ) );
cptm_eq( array( 'book', 'oldtype', 'genre' ), $result['created'], 'import creates every definition' );
cptm_eq( array(), $result['errors'], 'import has no errors' );
$after = Cptm_Definition::export();
foreach ( array( 'post_types', 'taxonomies' ) as $group ) {
	foreach ( $before[ $group ] as $i => $item ) {
		cptm_eq( $item['meta'], $after[ $group ][ $i ]['meta'], "round trip keeps the settings of {$item['title']}" );
	}
}
$again = Cptm_Definition::import( json_decode( $json, true ) );
cptm_eq( array( 'book', 'oldtype', 'genre' ), $again['skipped'], 'skip mode skips existing names' );
$bad = Cptm_Definition::import( array( 'post_types' => array( array( 'meta' => array( 'cptm_name' => 'page' ) ) ) ) );
cptm_eq( 1, count( $bad['errors'] ), 'import rejects a reserved name' );
$draft = Cptm_Definition::import( array( 'post_types' => array( array( 'title' => 'Drafty', 'meta' => array( 'cptm_name' => 'drafty' ) ) ) ) );
cptm_eq( 'draft', get_post_status( Cptm_Definition::find( 'cptm', 'drafty' ) ), 'import saves new definitions as drafts by default' );

/* 6. Custom Post Type UI import (read only). */
update_option( 'cptui_post_types', array( 'movie' => array( 'name' => 'movie', 'label' => 'Movies', 'singular_label' => 'Movie', 'public' => 'true', 'has_archive' => 'true', 'show_in_rest' => 'true', 'rewrite' => 'true', 'rewrite_slug' => 'films', 'menu_icon' => 'dashicons-video-alt', 'supports' => array( 'title', 'editor' ), 'taxonomies' => array( 'post_tag' ) ) ) );
update_option( 'cptui_taxonomies', array( 'studio' => array( 'name' => 'studio', 'label' => 'Studios', 'singular_label' => 'Studio', 'hierarchical' => 'false', 'object_types' => array( 'movie' ), 'sort' => 'true' ) ) );
$c = Cptm_Definition::import( Cptm_Definition::from_cptui(), array( 'allow_registered' => true ) );
cptm_eq( array( 'movie', 'studio' ), $c['created'], 'CPT UI types are imported' );
$movie = Cptm_Definition::flat( 'cptm', Cptm_Definition::find( 'cptm', 'movie' ) );
cptm_eq( 'films', $movie['cptm_custom_rewrite_slug'], 'CPT UI rewrite slug' );
cptm_eq( 'dashicons-video-alt', $movie['cptm_icon_slug'], 'CPT UI icon' );
cptm_eq( array( 'post_tag' ), $movie['cptm_builtin_taxonomies'], 'CPT UI built-in taxonomies' );
cptm_eq( array( 'movie' ), Cptm_Definition::flat( 'cptm_tax', Cptm_Definition::find( 'cptm_tax', 'studio' ) )['cptm_tax_post_types'], 'CPT UI taxonomy object types' );
cptm_ok( is_array( get_option( 'cptui_post_types' ) ) && isset( get_option( 'cptui_post_types' )['movie'] ), 'CPT UI options are not changed' );

/* 7. Get code: valid PHP with the same arguments. */
$book = Cptm_Definition::find( 'cptm', 'book' );
$code = Cptm_Definition::code( 'cptm', get_post_meta( $book ) );
try {
	token_get_all( "<?php\n" . $code, TOKEN_PARSE );
	cptm_ok( true, 'code parses' );
} catch ( ParseError $e ) {
	cptm_ok( false, 'code parses: ' . $e->getMessage() );
}
$args = Cptm_Definition::post_type_registration( get_post_meta( $book ) )['args'];
cptm_eq( $args, eval( 'return ' . Cptm_Definition::export_value( $args ) . ';' ), 'code has the same arguments' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- test of generated code.
cptm_ok( str_contains( Cptm_Definition::code( 'cptm_tax', get_post_meta( Cptm_Definition::find( 'cptm_tax', 'genre' ) ) ), "register_taxonomy( 'genre', array(" ), 'taxonomy code' );

/* 8. The icon size is made on demand only (C-007). */
if ( function_exists( 'imagecreatetruecolor' ) ) {
	$upload = wp_upload_dir();
	$file   = $upload['path'] . '/cptm-smoke.png';
	$img    = imagecreatetruecolor( 64, 64 );
	imagepng( $img, $file );
	$att = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => 'smoke', 'post_status' => 'inherit' ), $file );
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $file ) );
	cptm_ok( empty( wp_get_attachment_metadata( $att )['sizes']['cptm_icon'] ), 'an upload does not get the cptm_icon size' );
	$icon = Cptm_Definition::icon_url( wp_get_attachment_url( $att ) );
	cptm_ok( str_ends_with( $icon, '-16x16.png' ), 'the icon picker URL becomes the 16x16 size: ' . $icon );
	cptm_ok( ! empty( wp_get_attachment_metadata( $att )['sizes']['cptm_icon'] ), 'the size is stored for the selected image' );
} else {
	cptm_ok( false, 'GD is not available, so the icon test cannot run' );
}

/* 9. Multisite activation (single site here): activation registers and flushes without errors. */
$cptm->cptm_plugin_activate_flush_rewrite( false );
cptm_ok( post_type_exists( 'book' ), 'activation keeps the types registered' );

cptm_done( 'smoke' );
