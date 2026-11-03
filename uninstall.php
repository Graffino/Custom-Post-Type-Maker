<?php
/**
 * Uninstall: runs when a user deletes the plugin. Removes data only when the user opted in on the Tools page.
 * Never deletes the user's posts or terms.
 *
 * @package Custom_Post_Types_Maker
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! function_exists( 'cptm_uninstall_site' ) ) {
	/**
	 * Remove the plugin data of the current site, if the site opted in.
	 */
	function cptm_uninstall_site(): void {
		if ( ! get_option( 'cptm_delete_data_on_uninstall', false ) ) {
			return;
		}

		// Definitions (cptm and cptm_tax posts with their meta). Content posts are not touched.
		$ids = get_posts(
			array(
				'post_type'   => array( 'cptm', 'cptm_tax' ),
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);
		foreach ( $ids as $id ) {
			wp_delete_post( (int) $id, true );
		}

		// Capabilities granted for custom capability types (D-012).
		$role = get_role( 'administrator' );
		if ( $role ) {
			foreach ( (array) get_option( 'cptm_granted_caps', array() ) as $cap ) {
				$role->remove_cap( (string) $cap );
			}
		}

		foreach ( array( 'cptm_plugin_settings_changed', 'cptm_granted_caps', 'cptm_delete_data_on_uninstall' ) as $option ) {
			delete_option( $option );
		}
		flush_rewrite_rules();
	}
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $cptm_site_id ) {
		switch_to_blog( (int) $cptm_site_id );
		cptm_uninstall_site();
		restore_current_blog();
	}
} else {
	cptm_uninstall_site();
}
