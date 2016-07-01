<?php

/**
 * Applies some checks before the main code can run.
 *
 * Inspects the current context (WordPress and PHP),
 * and previous and competing installations.
 *
 *
 * @version 2014.09.03
 * @author  Inpsyde GmbH, toscho
 * @license GPL
 */
class Mlp_Self_Check {

	/**
	 * @type int
	 */
	const INSTALLATION_CONTEXT_OK = 1;

	/**
	 * @type int
	 */
	const WRONG_PAGE_FOR_CHECK = 2;

	/**
	 * @type int
	 */
	const PLUGIN_DEACTIVATED = 3;

	/**
	 * @type int
	 */
	const NEEDS_INSTALLATION = 4;

	/**
	 * @type int
	 */
	const NEEDS_UPGRADE = 5;

	/**
	 * @type int
	 */
	const NO_UPGRADE_NEEDED = 6;

	/**
	 * Path to plugin main file.
	 *
	 * @type string
	 */
	private $plugin_file;

	/**
	 * @var string
	 */
	private $pagenow;

	/**
	 * @param string $plugin_file
	 * @param string $pagenow
	 */
	public function __construct( $plugin_file, $pagenow ) {

		$this->plugin_file = $plugin_file;
		$this->pagenow = $pagenow;
	}

	/**
	 * Check if MultilingualPress was installed correctly.
	 *
	 * @param  string $name
	 * @param  string $base_name
	 * @param  string $wp_version
	 *
	 * @return string
	 */
	public function pre_install_check( $name, $base_name, $wp_version ) {

		// TODO: Remove with MultilingualPress 3.0.0.
		$this->check_php_version();

		if ( ! $this->is_plugin_page() ) {
			return self::WRONG_PAGE_FOR_CHECK;
		}

		$php_version = phpversion();

		$check = new Mlp_Requirements_Check(
			new Mlp_Install_Requirements(),
			Mlp_Semantic_Version_Number_Factory::create( $php_version ),
			Mlp_Semantic_Version_Number_Factory::create( $wp_version ),
			$this->plugin_file
		);

		if ( $check->is_compliant() ) {
			return self::INSTALLATION_CONTEXT_OK;
		}

		$errors = $check->get_error_messages();
		$deactivate = new Mlp_Plugin_Deactivation( $errors, $name, $base_name );

		add_action( 'admin_notices', [ $deactivate, 'deactivate' ], 0 );
		add_action( 'network_admin_notices', [ $deactivate, 'deactivate' ], 0 );

		return self::PLUGIN_DEACTIVATED;
	}

	/**
	 * Checks the current PHP version and displays an admin notice in case it is lower than 5.4.0.
	 *
	 * @return void
	 */
	private function check_php_version() {

		if ( version_compare( PHP_VERSION, '5.4.0', '>=' ) ) {
			return;
		}

		if ( ! is_admin() ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		add_filter( 'site_transient_update_plugins', [ $this, 'remove_mlp_from_update_plugins_transient' ] );

		global $pagenow;
		if ( ! in_array( $pagenow, [ 'index.php', 'plugins.php' ], true ) ) {
			return;
		}

		$callback = [ $this, 'render_php_version_admin_notice' ];
		add_action( 'admin_notices', $callback );
		add_action( 'network_admin_notices', $callback );
	}

	/**
	 * Removes MultilingualPress from updatable plugins.
	 *
	 * @wp-hook site_transient_$transient
	 *
	 * @param mixed $plugins Plugins data.
	 *
	 * @return mixed
	 */
	public function remove_mlp_from_update_plugins_transient( $plugins ) {

		$file = plugin_basename( $this->plugin_file );
		if ( empty( $plugins->response[ $file ]->new_version ) ) {
			return $plugins;
		}

		$new_version = Mlp_Semantic_Version_Number_Factory::create( $plugins->response[ $file ]->new_version );
		if ( version_compare( $new_version, '3.0.0-alpha', '<' ) ) {
			return $plugins;
		}

		remove_action( 'network_admin_notices', [ $this, 'render_php_version_admin_notice' ] );

		add_action( 'network_admin_notices', [ $this, 'render_mlp_3_admin_notice' ] );

		add_action( "after_plugin_row_$file", [ $this, 'render_mlp_3_update_message' ] );

		unset( $plugins->response[ $file ] );

		return $plugins;
	}

	/**
	 * Displays an admin notice informing about the current and the required PHP version.
	 *
	 * @wp-hook admin_notices
	 * @wp-hook network_admin_notices
	 *
	 * @return void
	 */
	public function render_php_version_admin_notice() {

		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php _e( 'MultilingualPress Information', 'multilingual-pres' ); ?></strong><br>
				<?php
				/* translators: %s: current PHP version */
				$message = __(
					'With the upcoming major release, MultilingualPress will be requiring <strong>PHP version 5.4.0</strong> or higher. Currently, you are running <strong>PHP version %s</strong>. Please contact your hoster and update PHP to version 5.4.0 or higher.',
					'multilingual-press'
				);
				printf( $message, PHP_VERSION );
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Displays an admin notice informing about the new version of MultilingualPress, and that it cannot be installed
	 * due to unmet requirements.
	 *
	 * @wp-hook network_admin_notices
	 *
	 * @return void
	 */
	public function render_mlp_3_admin_notice() {

		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php _e( 'MultilingualPress Information', 'multilingual-pres' ); ?></strong><br>
				<?php $this->render_mlp_3_message(); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Displays a notice informing about the new version of MultilingualPress, and that it cannot be installed due to
	 * unmet requirements.
	 *
	 * @wp-hook after_plugin_row_{$file}
	 *
	 * @param string $file Main plugin file.
	 *
	 * @return void
	 */
	public function render_mlp_3_update_message( $file ) {

		$active_class = is_plugin_active_for_network( $file ) ? ' active' : '';

		$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
		$colspan = $wp_list_table->get_column_count();
		?>
		<tr id="multilingualpress-update" class="plugin-update-tr<?php echo esc_attr( $active_class ); ?>"
			data-plugin="<?php echo esc_attr( $file ); ?>" data-slug="multilingualpress">
			<td colspan="<?php echo absint( $colspan ); ?>" class="plugin-update colspanchange">
				<div class="update-message">
					<?php $this->render_mlp_3_message(); ?>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Displays a notice informing about the new version of MultilingualPress, and that it cannot be installed due to
	 * unmet requirements.
	 *
	 * @see render_mlp_3_update_message
	 *
	 * @return void
	 */
	private function render_mlp_3_message() {

		/* translators: %s: current PHP version */
		$message = __(
			'There is a new version of MultilingualPress available. This version, however, requires <strong>PHP version 5.4.0</strong> or higher. Currently, you are running <strong>PHP version %s</strong>. Please contact your hoster and update PHP to version 5.4.0 or higher.',
			'multilingual-press'
		);
		printf( $message, PHP_VERSION );
	}

	/**
	 * Check if we need an upgrade for our tables.
	 *
	 * @param  Mlp_Version_Number_Interface $current_version
	 * @param  Mlp_Version_Number_Interface $last_version
	 *
	 * @return int
	 */
	public function is_current_version( Mlp_Version_Number_Interface $current_version, Mlp_Version_Number_Interface $last_version ) {

		if ( version_compare( $current_version, $last_version, '=<' ) ) {
			return self::NO_UPGRADE_NEEDED;
		}

		$mlp_settings = get_site_option( 'inpsyde_multilingual' );

		if ( empty ( $mlp_settings ) ) {
			return self::NEEDS_INSTALLATION;
		}

		return self::NEEDS_UPGRADE;
	}

	/**
	 * Test if we are on a page where we can run the checks.
	 *
	 * @return bool
	 */
	private function is_plugin_page() {

		if ( ! is_admin() ) {
			return FALSE;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return FALSE;
		}

		return 'plugins.php' === $this->pagenow;
	}
}
