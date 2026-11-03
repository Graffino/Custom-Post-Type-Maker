<?php
/**
 * Uninstall test. Includes uninstall.php directly: never call delete_plugins() here, because tests/run.sh mounts the repo read-write.
 *
 * @package Custom_Post_Types_Maker
 */

require __DIR__ . '/lib.php';

wp_set_current_user( 1 );
$content = wp_insert_post( array( 'post_type' => 'book', 'post_status' => 'publish', 'post_title' => 'A book' ) );
Cptm_Definition::grant_capabilities( 'gadget' );
$count = count( Cptm_Definition::posts( 'cptm' ) ) + count( Cptm_Definition::posts( 'cptm_tax' ) );
cptm_ok( $count > 0, 'there are definitions before uninstall' );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	define( 'WP_UNINSTALL_PLUGIN', 'custom-post-type-maker/class-cptm.php' );
}
$file = dirname( __DIR__, 2 ) . '/uninstall.php';

delete_option( 'cptm_delete_data_on_uninstall' );
include $file;
cptm_eq( $count, count( Cptm_Definition::posts( 'cptm' ) ) + count( Cptm_Definition::posts( 'cptm_tax' ) ), 'without opt-in, uninstall keeps the definitions' );

update_option( 'cptm_delete_data_on_uninstall', true );
include $file;
cptm_eq( 0, count( Cptm_Definition::posts( 'cptm' ) ) + count( Cptm_Definition::posts( 'cptm_tax' ) ), 'with opt-in, uninstall deletes the definitions' );
cptm_ok( (bool) get_post( $content ), 'uninstall keeps content posts' );
cptm_ok( ! get_role( 'administrator' )->has_cap( 'edit_gadgets' ), 'uninstall removes granted capabilities' );
cptm_eq( false, get_option( 'cptm_granted_caps' ), 'uninstall deletes the plugin options' );

cptm_done( 'uninstall' );
