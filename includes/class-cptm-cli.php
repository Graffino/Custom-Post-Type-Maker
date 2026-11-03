<?php
/**
 * WP-CLI commands (C-009).
 *
 * @package Custom_Post_Types_Maker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage Custom Post Type Maker definitions.
 */
final class Cptm_CLI {
	/**
	 * List the definitions.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : table, csv, json or yaml.
	 * ---
	 * default: table
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp cptm list
	 *
	 * @subcommand list
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function list_( array $args, array $assoc_args ): void {
		$rows = array();
		foreach ( array(
			'cptm'     => 'post type',
			'cptm_tax' => 'taxonomy',
		) as $kind => $label ) {
			foreach ( Cptm_Definition::posts( $kind ) as $post ) {
				$rows[] = array(
					'kind'       => $label,
					'name'       => (string) get_post_meta( $post->ID, Cptm_Definition::KEYS[ $kind ]['name'], true ),
					'title'      => $post->post_title,
					'status'     => $post->post_status,
					'registered' => ( 'cptm' === $kind ? post_type_exists( (string) get_post_meta( $post->ID, 'cptm_name', true ) ) : taxonomy_exists( (string) get_post_meta( $post->ID, 'cptm_tax_name', true ) ) ) ? 'yes' : 'no',
					'id'         => $post->ID,
				);
			}
		}
		WP_CLI\Utils\format_items( $assoc_args['format'] ?? 'table', $rows, array( 'kind', 'name', 'title', 'status', 'registered', 'id' ) );
	}

	/**
	 * Export the definitions as JSON (the same format as the Tools page).
	 *
	 * ## OPTIONS
	 *
	 * [--file=<file>]
	 * : Write to this file. Default: standard output.
	 *
	 * ## EXAMPLES
	 *
	 *     wp cptm export --file=definitions.json
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function export( array $args, array $assoc_args ): void {
		$json = (string) wp_json_encode( Cptm_Definition::export(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( empty( $assoc_args['file'] ) ) {
			WP_CLI::line( $json );
			return;
		}
		if ( false === file_put_contents( $assoc_args['file'], $json . "\n" ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- CLI output file.
			WP_CLI::error( 'Cannot write ' . $assoc_args['file'] );
		}
		WP_CLI::success( 'Exported to ' . $assoc_args['file'] );
	}

	/**
	 * Import definitions from a JSON export file.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : The export file.
	 *
	 * [--mode=<mode>]
	 * : What to do when a name exists.
	 * ---
	 * default: skip
	 * options:
	 *   - skip
	 *   - replace
	 * ---
	 *
	 * [--publish]
	 * : Publish new definitions. Default: save them as drafts.
	 *
	 * ## EXAMPLES
	 *
	 *     wp cptm import definitions.json --publish
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function import( array $args, array $assoc_args ): void {
		$file = $args[0];
		if ( ! is_readable( $file ) ) {
			WP_CLI::error( 'Cannot read ' . $file );
		}
		$data = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
		if ( ! is_array( $data ) ) {
			WP_CLI::error( 'The file is not valid JSON.' );
		}
		$this->report(
			Cptm_Definition::import(
				$data,
				array(
					'mode'    => $assoc_args['mode'] ?? 'skip',
					'publish' => ! empty( $assoc_args['publish'] ),
				)
			)
		);
	}

	/**
	 * Print the PHP code that registers a definition without the plugin.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The post type or taxonomy name.
	 *
	 * ## EXAMPLES
	 *
	 *     wp cptm code book
	 *
	 * @param array $args Positional arguments.
	 */
	public function code( array $args ): void {
		foreach ( array( 'cptm', 'cptm_tax' ) as $kind ) {
			$id = Cptm_Definition::find( $kind, $args[0] );
			if ( $id ) {
				WP_CLI::line( Cptm_Definition::code( $kind, get_post_meta( $id ) ) );
				return;
			}
		}
		WP_CLI::error( 'No definition with the name ' . $args[0] );
	}

	/**
	 * Print an import result. Errors make the command fail.
	 *
	 * @param array $result Import result.
	 */
	private function report( array $result ): void {
		foreach ( array( 'created', 'updated', 'skipped' ) as $group ) {
			if ( $result[ $group ] ) {
				WP_CLI::log( ucfirst( $group ) . ': ' . implode( ', ', $result[ $group ] ) );
			}
		}
		foreach ( $result['errors'] as $error ) {
			WP_CLI::warning( $error );
		}
		if ( $result['errors'] ) {
			WP_CLI::error( 'Import finished with errors.' );
		}
		WP_CLI::success( 'Import finished.' );
	}
}

WP_CLI::add_command( 'cptm', 'Cptm_CLI' );
