<?php
/**
 * Tiny assertion helpers for the smoke tests. Results go to /results/<suite>.txt (mounted by tests/run.sh).
 *
 * @package Custom_Post_Types_Maker
 */

$GLOBALS['cptm_t'] = array(
	'pass' => 0,
	'fail' => array(),
);

/**
 * Assert a condition.
 *
 * @param bool   $cond Condition.
 * @param string $msg  What is checked.
 */
function cptm_ok( $cond, string $msg ): void {
	if ( $cond ) {
		++$GLOBALS['cptm_t']['pass'];
	} else {
		$GLOBALS['cptm_t']['fail'][] = $msg;
	}
}

/**
 * Assert equality.
 *
 * @param mixed  $expected Expected.
 * @param mixed  $actual   Actual.
 * @param string $msg      What is checked.
 */
function cptm_eq( $expected, $actual, string $msg ): void {
	cptm_ok( $expected === $actual, $msg . ' — expected ' . wp_json_encode( $expected ) . ', got ' . wp_json_encode( $actual ) );
}

/**
 * Write the result file and fail the step on a failed assertion.
 *
 * @param string $suite Suite name.
 */
function cptm_done( string $suite ): void {
	$t    = $GLOBALS['cptm_t'];
	$text = ( $t['fail'] ? 'FAIL' : 'PASS' ) . " {$suite}: {$t['pass']} passed, " . count( $t['fail'] ) . " failed\n";
	foreach ( $t['fail'] as $f ) {
		$text .= "  - {$f}\n";
	}
	file_put_contents( "/results/{$suite}.txt", $text ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- test output.
	echo $text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- test output.
	if ( $t['fail'] ) {
		throw new Exception( "Smoke suite {$suite} failed." );
	}
}

/**
 * Write raw meta as 1.2.0 did (C-001).
 *
 * @param  string $type  'cptm' or 'cptm_tax'.
 * @param  string $title Title.
 * @param  array  $meta  Meta.
 * @return int
 */
function cptm_seed( string $type, string $title, array $meta ): int {
	$id = wp_insert_post(
		array(
			'post_type'   => $type,
			'post_status' => 'publish',
			'post_title'  => $title,
		)
	);
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	return $id;
}

/**
 * Save a definition through the edit form path (save_post_<type> with a posted form).
 *
 * @param int   $id   Definition ID.
 * @param array $post Posted fields (meta keys and form-only fields).
 */
function cptm_form_save( int $id, array $post ): void {
	$_POST = wp_slash(
		array_merge(
			array( 'cptm_meta_box_nonce_field' => wp_create_nonce( 'cptm_meta_box_nonce_action' ) ),
			$post
		)
	);
	wp_update_post( array( 'ID' => $id ) );
	$_POST = array();
}
