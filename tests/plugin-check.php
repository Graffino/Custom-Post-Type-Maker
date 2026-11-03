<?php
/**
 * Run the static Plugin Check checks on the built plugin and write /results/plugin-check.txt.
 * Runs inside Playground from tests/plugin-check.sh. A runPHP step is used because PHP_CodeSniffer
 * cannot read php://stdin in the Playground WP-CLI step.
 *
 * @package Custom_Post_Types_Maker
 */

use WordPress\Plugin_Check\Checker\Abstract_Check_Runner;

/**
 * A runner with fixed parameters: all static checks, plugin custom-post-type-maker.
 */
final class Cptm_Plugin_Check_Runner extends Abstract_Check_Runner {
	public static function is_plugin_check() {
		return true;
	}
	protected function get_plugin_param() {
		return 'custom-post-type-maker';
	}
	protected function get_check_slugs_param() {
		return array();
	}
	protected function get_check_exclude_slugs_param() {
		return array();
	}
	protected function get_include_experimental_param() {
		return false;
	}
	protected function get_categories_param() {
		return array();
	}
	protected function get_slug_param() {
		return '';
	}
	protected function get_mode_param() {
		return 'update';
	}
	protected function allow_runtime_checks(): bool {
		return false;
	}
}

$runner = new Cptm_Plugin_Check_Runner();
$runner->prepare();
$result = $runner->run();

$lines  = array();
$counts = array(
	'ERROR'   => 0,
	'WARNING' => 0,
);
foreach ( array(
	'ERROR'   => $result->get_errors(),
	'WARNING' => $result->get_warnings(),
) as $type => $files ) {
	foreach ( $files as $file => $file_lines ) {
		foreach ( $file_lines as $line => $columns ) {
			foreach ( $columns as $messages ) {
				foreach ( $messages as $m ) {
					++$counts[ $type ];
					$lines[] = sprintf( '%s %s:%s %s %s', $type, $file, $line, $m['code'] ?? '', wp_strip_all_tags( (string) ( $m['message'] ?? '' ) ) );
				}
			}
		}
	}
}
$text = ( $counts['ERROR'] ? 'FAIL' : 'PASS' ) . " plugin-check: {$counts['ERROR']} errors, {$counts['WARNING']} warnings\n" . implode( "\n", $lines ) . "\n";
file_put_contents( '/results/plugin-check.txt', $text ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- test output.
echo $text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- test output.
