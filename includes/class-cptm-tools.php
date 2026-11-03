<?php
/**
 * The "Post Types → Tools" page: export, import, Custom Post Type UI import, PHP code and the uninstall setting.
 *
 * @package Custom_Post_Types_Maker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tools page.
 */
final class Cptm_Tools {
	/** Page slug. */
	const PAGE = 'cptm-tools';

	/** @var string The page hook (screen ID); it depends on the translated menu title. */
	public static string $hook = '';

	/** Largest import file, in bytes. */
	const MAX_IMPORT_BYTES = 2097152;

	/**
	 * Hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ), 20 );
		add_action( 'admin_post_cptm_export', array( $this, 'handle_export' ) );
		add_action( 'admin_post_cptm_import', array( $this, 'handle_import' ) );
		add_action( 'admin_post_cptm_import_cptui', array( $this, 'handle_import_cptui' ) );
		add_action( 'admin_post_cptm_settings', array( $this, 'handle_settings' ) );
	}

	/**
	 * Add the Tools submenu.
	 */
	public function menu(): void {
		self::$hook = (string) add_submenu_page( 'edit.php?post_type=cptm', __( 'Custom Post Type Maker Tools', 'custom-post-type-maker' ), __( 'Tools', 'custom-post-type-maker' ), Cptm::CAPABILITY, self::PAGE, array( $this, 'render' ) );
	}

	/**
	 * Stop unless the request is allowed.
	 *
	 * @param string $action Nonce action.
	 */
	private function check( string $action ): void {
		if ( ! current_user_can( Cptm::CAPABILITY ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do this.', 'custom-post-type-maker' ), 403 );
		}
		check_admin_referer( $action );
	}

	/**
	 * Store a result for the next page view and go back to the Tools page.
	 *
	 * @param array $result Import result, or array( 'message' => string ).
	 */
	private function back( array $result ): void {
		set_transient( 'cptm_tools_result_' . get_current_user_id(), $result, 5 * MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'edit.php?post_type=cptm&page=' . self::PAGE ) );
		exit;
	}

	/**
	 * Download the export file.
	 */
	public function handle_export(): void {
		$this->check( 'cptm_export' );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified in check(); cast to int below.
		$ids  = isset( $_POST['cptm_ids'] ) && is_array( $_POST['cptm_ids'] ) ? array_map( 'absint', wp_unslash( $_POST['cptm_ids'] ) ) : array();
		$data = Cptm_Definition::export( $ids );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=custom-post-type-maker-' . gmdate( 'Y-m-d' ) . '.json' );
		echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON file download.
		exit;
	}

	/**
	 * The import options of a request.
	 *
	 * @return array
	 */
	private function import_options(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified in check().
		return array(
			'mode'    => isset( $_POST['cptm_mode'] ) && 'replace' === $_POST['cptm_mode'] ? 'replace' : 'skip',
			'publish' => ! empty( $_POST['cptm_publish'] ),
		);
		// phpcs:enable
	}

	/**
	 * Import a file or pasted JSON.
	 */
	public function handle_import(): void {
		$this->check( 'cptm_import' );

		$json = '';
		// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput -- verified in check(); the JSON is validated by Cptm_Definition::import().
		if ( ! empty( $_FILES['cptm_import_file']['tmp_name'] ) && is_uploaded_file( $_FILES['cptm_import_file']['tmp_name'] ) ) {
			if ( (int) $_FILES['cptm_import_file']['size'] > self::MAX_IMPORT_BYTES ) {
				$this->back( array( 'message' => __( 'The file is too large.', 'custom-post-type-maker' ) ) );
			}
			$json = (string) file_get_contents( $_FILES['cptm_import_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local upload.
		} elseif ( isset( $_POST['cptm_import_json'] ) ) {
			$json = (string) wp_unslash( $_POST['cptm_import_json'] );
		}
		// phpcs:enable

		$data = json_decode( $json, true );
		if ( ! is_array( $data ) ) {
			$this->back( array( 'message' => __( 'The import is not valid JSON.', 'custom-post-type-maker' ) ) );
		}
		$this->back( Cptm_Definition::import( $data, $this->import_options() ) );
	}

	/**
	 * Import the settings of Custom Post Type UI.
	 */
	public function handle_import_cptui(): void {
		$this->check( 'cptm_import_cptui' );
		$opts                     = $this->import_options();
		$opts['allow_registered'] = true;
		$this->back( Cptm_Definition::import( Cptm_Definition::from_cptui(), $opts ) );
	}

	/**
	 * Save the uninstall setting.
	 */
	public function handle_settings(): void {
		$this->check( 'cptm_settings' );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in check().
		update_option( 'cptm_delete_data_on_uninstall', ! empty( $_POST['cptm_delete_data_on_uninstall'] ), false );
		$this->back( array( 'message' => __( 'Settings saved.', 'custom-post-type-maker' ) ) );
	}

	/**
	 * Print the last result.
	 */
	private function render_result(): void {
		$key    = 'cptm_tools_result_' . get_current_user_id();
		$result = get_transient( $key );
		if ( ! is_array( $result ) ) {
			return;
		}
		delete_transient( $key );

		if ( isset( $result['message'] ) ) {
			printf( '<div class="notice notice-info is-dismissible"><p>%s</p></div>', esc_html( $result['message'] ) );
			return;
		}
		$lines = array();
		foreach ( array(
			/* translators: %s: list of names */
			'created' => __( 'Created: %s', 'custom-post-type-maker' ),
			/* translators: %s: list of names */
			'updated' => __( 'Replaced: %s', 'custom-post-type-maker' ),
			/* translators: %s: list of names */
			'skipped' => __( 'Skipped (the name exists): %s', 'custom-post-type-maker' ),
		) as $group => $text ) {
			if ( ! empty( $result[ $group ] ) ) {
				$lines[] = sprintf( $text, implode( ', ', $result[ $group ] ) );
			}
		}
		if ( ! $lines && empty( $result['errors'] ) ) {
			$lines[] = __( 'The import contained no definitions.', 'custom-post-type-maker' );
		}
		$class = empty( $result['errors'] ) ? 'notice-success' : 'notice-warning';
		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible">';
		foreach ( array_merge( $lines, (array) ( $result['errors'] ?? array() ) ) as $line ) {
			echo '<p>' . esc_html( $line ) . '</p>';
		}
		if ( ! empty( $result['created'] ) ) {
			echo '<p>' . esc_html__( 'New definitions are drafts unless you selected "Publish". Check them, then publish them to register the types.', 'custom-post-type-maker' ) . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Print the import options.
	 */
	private function render_import_options(): void {
		?>
		<p>
			<label><input type="radio" name="cptm_mode" value="skip" checked /> <?php esc_html_e( 'Skip a definition when the name exists', 'custom-post-type-maker' ); ?></label><br />
			<label><input type="radio" name="cptm_mode" value="replace" /> <?php esc_html_e( 'Replace the existing definition', 'custom-post-type-maker' ); ?></label>
		</p>
		<p><label><input type="checkbox" name="cptm_publish" value="1" /> <?php esc_html_e( 'Publish new definitions (else they are saved as drafts)', 'custom-post-type-maker' ); ?></label></p>
		<?php
	}

	/**
	 * Render the Tools page.
	 */
	public function render(): void {
		if ( ! current_user_can( Cptm::CAPABILITY ) ) {
			return;
		}
		$action = admin_url( 'admin-post.php' );
		$kinds  = array(
			'cptm'     => __( 'Post Types', 'custom-post-type-maker' ),
			'cptm_tax' => __( 'Taxonomies', 'custom-post-type-maker' ),
		);
		$cptui  = Cptm_Definition::from_cptui();
		?>
		<div class="wrap cptm-tools">
			<h1><?php esc_html_e( 'Custom Post Type Maker Tools', 'custom-post-type-maker' ); ?></h1>
			<?php $this->render_result(); ?>

			<div class="card">
				<h2><?php esc_html_e( 'Export', 'custom-post-type-maker' ); ?></h2>
				<p><?php esc_html_e( 'Download your definitions as a JSON file. Import the file on another site, or keep it as a backup.', 'custom-post-type-maker' ); ?></p>
				<form method="post" action="<?php echo esc_url( $action ); ?>">
					<input type="hidden" name="action" value="cptm_export" />
					<?php wp_nonce_field( 'cptm_export' ); ?>
					<?php
					$any = false;
					foreach ( $kinds as $kind => $title ) {
						$posts = Cptm_Definition::posts( $kind );
						if ( ! $posts ) {
							continue;
						}
						$any = true;
						echo '<fieldset><legend><strong>' . esc_html( $title ) . '</strong></legend>';
						foreach ( $posts as $post ) {
							$name = (string) get_post_meta( $post->ID, Cptm_Definition::KEYS[ $kind ]['name'], true );
							printf(
								'<label><input type="checkbox" name="cptm_ids[]" value="%1$d" checked /> %2$s <code>%3$s</code>%4$s</label><br />',
								(int) $post->ID,
								esc_html( $post->post_title ),
								esc_html( $name ),
								'publish' === $post->post_status ? '' : ' (' . esc_html( $post->post_status ) . ')'
							);
						}
						echo '</fieldset>';
					}
					if ( $any ) {
						submit_button( __( 'Download export file', 'custom-post-type-maker' ), 'primary', 'submit', false );
					} else {
						echo '<p>' . esc_html__( 'There are no definitions yet.', 'custom-post-type-maker' ) . '</p>';
					}
					?>
				</form>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'Import', 'custom-post-type-maker' ); ?></h2>
				<p><?php esc_html_e( 'Import a file that Custom Post Type Maker exported, or paste its content.', 'custom-post-type-maker' ); ?></p>
				<form method="post" action="<?php echo esc_url( $action ); ?>" enctype="multipart/form-data">
					<input type="hidden" name="action" value="cptm_import" />
					<?php wp_nonce_field( 'cptm_import' ); ?>
					<p><label for="cptm_import_file"><?php esc_html_e( 'Export file (.json)', 'custom-post-type-maker' ); ?></label><br /><input type="file" name="cptm_import_file" id="cptm_import_file" accept="application/json,.json" /></p>
					<p><label for="cptm_import_json"><?php esc_html_e( 'Or paste the JSON', 'custom-post-type-maker' ); ?></label><br /><textarea name="cptm_import_json" id="cptm_import_json" class="large-text code" rows="5"></textarea></p>
					<?php $this->render_import_options(); ?>
					<?php submit_button( __( 'Import', 'custom-post-type-maker' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>

			<?php if ( $cptui['post_types'] || $cptui['taxonomies'] ) { ?>
				<div class="card">
					<h2><?php esc_html_e( 'Import from Custom Post Type UI', 'custom-post-type-maker' ); ?></h2>
					<p>
						<?php
						printf(
							/* translators: 1: number of post types, 2: number of taxonomies */
							esc_html__( 'Custom Post Type UI has %1$d post types and %2$d taxonomies on this site. Import copies them; it does not change Custom Post Type UI.', 'custom-post-type-maker' ),
							count( $cptui['post_types'] ),
							count( $cptui['taxonomies'] )
						);
						?>
					</p>
					<p><?php esc_html_e( 'Import them as drafts, deactivate Custom Post Type UI, then publish the drafts. Two plugins must not register the same type.', 'custom-post-type-maker' ); ?></p>
					<form method="post" action="<?php echo esc_url( $action ); ?>">
						<input type="hidden" name="action" value="cptm_import_cptui" />
						<?php wp_nonce_field( 'cptm_import_cptui' ); ?>
						<?php $this->render_import_options(); ?>
						<?php submit_button( __( 'Import from Custom Post Type UI', 'custom-post-type-maker' ), 'secondary', 'submit', false ); ?>
					</form>
				</div>
			<?php } ?>

			<div class="card">
				<h2><?php esc_html_e( 'Get code', 'custom-post-type-maker' ); ?></h2>
				<p><?php esc_html_e( 'PHP code that registers all published definitions without this plugin. Paste it in a theme or plugin, then delete the definitions.', 'custom-post-type-maker' ); ?></p>
				<?php
				$code = '';
				foreach ( array_keys( $kinds ) as $kind ) {
					foreach ( Cptm_Definition::posts( $kind, 'publish' ) as $post ) {
						$code .= Cptm_Definition::code( $kind, get_post_meta( $post->ID ) ) . "\n";
					}
				}
				?>
				<textarea class="large-text code cptm-code" rows="12" readonly aria-label="<?php esc_attr_e( 'PHP code', 'custom-post-type-maker' ); ?>"><?php echo esc_textarea( $code ? "<?php\n" . $code : '' ); ?></textarea>
				<p><button type="button" class="button cptm-copy-code"><?php esc_html_e( 'Copy code', 'custom-post-type-maker' ); ?></button> <span class="cptm-copied" aria-live="polite"></span></p>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'Uninstall', 'custom-post-type-maker' ); ?></h2>
				<form method="post" action="<?php echo esc_url( $action ); ?>">
					<input type="hidden" name="action" value="cptm_settings" />
					<?php wp_nonce_field( 'cptm_settings' ); ?>
					<p><label><input type="checkbox" name="cptm_delete_data_on_uninstall" value="1" <?php checked( (bool) get_option( 'cptm_delete_data_on_uninstall', false ) ); ?> /> <?php esc_html_e( 'When I delete the plugin, also delete its definitions and settings.', 'custom-post-type-maker' ); ?></label></p>
					<p class="description"><?php esc_html_e( 'Your posts and terms are never deleted. Without the plugin, their types are no longer registered until you register them in code.', 'custom-post-type-maker' ); ?></p>
					<?php submit_button( __( 'Save', 'custom-post-type-maker' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>
		</div>
		<?php
	}
}
