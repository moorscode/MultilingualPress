<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\API\Languages;
use Inpsyde\MultilingualPress\Common\Nonce\WPNonce;
use Inpsyde\MultilingualPress\Database\Table;
use Inpsyde\MultilingualPress\Database\Table\LanguagesTable;
use Inpsyde\MultilingualPress\Database\WPDBTableInstaller;
use Inpsyde\MultilingualPress\Factory\TypeFactory;
use Inpsyde\MultilingualPress\LanguageManager\LanguageListTable;

use function Inpsyde\MultilingualPress\call_exit;
use function Inpsyde\MultilingualPress\check_admin_referer;
use function Inpsyde\MultilingualPress\resolve;

/**
 * Class Mlp_Language_Manager_Controller
 *
 * Control settings page for Language Manager table.
 *
 * @version 2014.07.17
 * @author  Inpsyde GmbH, toscho
 * @license GPL
 */
class Mlp_Language_Manager_Controller {

	/**
	 * @var Languages
	 */
	private $languages;

	/**
	 * @var Mlp_Language_Manager_Options_Page_Data
	 */
	private $setting;

	/**
	 * @var Mlp_Language_Manager_Page_View
	 */
	private $view;

	/**
	 * @var WPNonce
	 */
	private $nonce;

	/**
	 * @var string
	 */
	private $page_title;

	/**
	 * @var Mlp_Language_Manager_Pagination_Data
	 */
	private $pagination_data;

	/**
	 * @var Mlp_Table_Pagination_View
	 */
	private $pagination_view;

	/**
	 * @var string
	 */
	private $reset_action = 'mlp_reset_language_table';

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 *
	 * @param Mlp_Data_Access $database
	 * @param wpdb            $wpdb
	 */
	public function __construct( Mlp_Data_Access $database, wpdb $wpdb ) {

		$this->wpdb = $wpdb;

		$this->page_title = __( 'Language Manager', 'multilingualpress' );

		$this->pagination_data = new Mlp_Language_Manager_Pagination_Data( $database );

		// TODO: Remove as the data are (to be) defined in other places (e.g., updater, repository, settings page view).
		$this->setting = new Mlp_Language_Manager_Options_Page_Data(
			$this->page_title,
			resolve( 'multilingualpress.type_factory', TypeFactory::class )
		);

		// TODO: Remove as the nonce is defined in the service provider and is to be injected to where it is to be used.
		$this->nonce = new WPNonce( $this->setting->action() );

		// TODO: Remove, or better: refactor into the new ~\LanguageManager\LanguageManagerSettingsPageView class.
		$this->view = new Mlp_Language_Manager_Page_View(
			$this->setting,
			$this,
			$this->pagination_data,
			$this->nonce
		);

		$this->languages = resolve( 'multilingualpress.languages', Languages::class );

		$updater = new Mlp_Language_Updater(
			$this->pagination_data,
			new Mlp_Array_Diff( $this->get_columns() ),
			$this->languages,
			$this->nonce
		);

		add_action( 'admin_post_mlp_update_languages', [ $updater, 'update_languages' ] );

		// TODO: Remove as setting up the (new) settings page has been taken care of in the service provider already.
		add_action( 'network_admin_menu', [ $this, 'register_page' ], 50 );

		add_action( "admin_post_{$this->reset_action}", [ $this, 'reset_table' ] );
	}

	/**
	 * @return void
	 */
	public function register_page() {

		// TODO: Remove as setting up the (new) settings page has been taken care of in the service provider already.
		add_submenu_page(
			'settings.php',
			$this->page_title,
			$this->page_title,
			'manage_network_options',
			'language-manager',
			[ $this->view, 'render' ]
		);
	}

	/**
	 * @return void
	 */
	private function get_reset_table_link() {

		$request = remove_query_arg( 'msg', wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$url     = add_query_arg( [
			'action'               => $this->reset_action,
			$this->nonce->action() => (string) $this->nonce,
			'_wp_http_referer'     => esc_attr( $request )
		], (string) $this->setting->url() );
		?>
		<p>
			<a href="<?php echo esc_url( $url ); ?>" class="delete submitdelete" style="color:red">
				<?php esc_html_e( 'Reset table to default values', 'multilingualpress' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * @return void
	 */
	public function reset_table() {

		check_admin_referer( $this->nonce );

		$table_prefix = $this->wpdb->base_prefix;

		$table = new LanguagesTable( $table_prefix );

		$installer = new WPDBTableInstaller( $this->wpdb, $table );
		$installer->uninstall();
		$installer->install();

		/**
		 * Runs after having reset the database table.
		 *
		 * @param Table $table Languages table object.
		 */
		do_action( 'mlp_reset_table_done', $table );

		$url = add_query_arg( 'msg', 'resettable', $_REQUEST[ '_wp_http_referer' ] );
		wp_safe_redirect( $url );
		call_exit();
	}

	/**
	 * @return void
	 */
	public function before_form() {

		if ( ! empty( $_GET['msg'] ) ) {
			echo $this->get_update_message();
		}
	}

	/**
	 * Get message text for success notice.
	 *
	 * @return string
	 */
	private function get_update_message() {

		$type  = strtok( $_GET[ 'msg' ], '-' );
		$num   = (int) strtok( '-' );
		$num_f = number_format_i18n( $num );
		$text  = '';

		if ( 'updated' === $type ) {
			$text = sprintf(
				_n(
					'One language changed.',
					'%s languages changed.',
					$num,
					'multilingualpress'
				),
				$num_f
			);
		}
		if ( 'resettable' === $type ) {
			$text = esc_html__(
				'Table reset to default values.',
				'multilingualpress'
			);
		}

		if ( '' === $text )
			return '';

		return '<div class="updated"><p>' . esc_html( $text ) . '</p></div>';
	}

	/**
	 * @return void
	 */
	public function after_form() {

		?>
		<p class="description" style="padding-top:20px;clear:both">
			<?php
			esc_html_e(
				'Languages are sorted descending by priority and ascending by their English name.',
				'multilingualpress'
			);
			?>
		</p>

		<p class="description">
			<?php
			esc_html_e(
				'If you change the priority of a language to a higher value, it will show up on an earlier page.',
				'multilingualpress'
			);
			?>
		</p>
		<?php
		if ( isset( $_GET['msg'] ) && 'resettable' === $_GET['msg'] ) {
			return;
		}

		$this->get_reset_table_link();
	}

	/**
	 * @return void
	 */
	public function before_table() {

		?>
		<div class="tablenav top">
			<?php $this->get_pagination_object()->print_pagination(); ?>
		</div>
		<?php
	}

	/**
	 * @return void
	 */
	public function after_table() {

		?>
		<div class="tablenav bottom">
			<?php $this->get_pagination_object()->print_pagination(); ?>
		</div>
		<?php
	}

	/**
	 * @return Mlp_Table_Pagination_View
	 */
	private function get_pagination_object() {

		if ( ! is_a( $this->pagination_view, 'Mlp_Table_Pagination_View' ) )
			$this->pagination_view = new Mlp_Table_Pagination_View( $this->pagination_data );

		return $this->pagination_view;
	}

	/**
	 * @return void
	 */
	public function show_table() {

		$table = new LanguageListTable( $this->languages );
		$table->prepare_items();
		$table->display();
		/*
		$view = new Mlp_Admin_Table_View (
			$this->languages,
			$this->pagination_data,
			$this->get_columns(),
			'mlp-language-manager-table',
			'languages'
		);
		$view->show_table();
		*/
	}

	/**
	 * @return array
	 */
	private function get_columns() {
		return [
			LanguagesTable::COLUMN_NATIVE_NAME    => [
				'header'     => __( 'Native name', 'multilingualpress' ),
				'type'       => 'input_text',
				'attributes' => [ 'size' => 20 ],
			],
			LanguagesTable::COLUMN_ENGLISH_NAME   => [
				'header'     => __( 'English name', 'multilingualpress' ),
				'type'       => 'input_text',
				'attributes' => [ 'size' => 20 ],
			],
			LanguagesTable::COLUMN_RTL            => [
				'header'     => __( 'RTL', 'multilingualpress' ),
				'type'       => 'input_checkbox',
				'attributes' => [ 'size' => 20 ],
			],
			LanguagesTable::COLUMN_HTTP_CODE      => [
				'header'     => __( 'HTTP', 'multilingualpress' ),
				'type'       => 'input_text',
				'attributes' => [ 'size' => 5 ],
			],
			LanguagesTable::COLUMN_ISO_639_1_CODE => [
				'header'     => __( 'ISO&#160;639-1', 'multilingualpress' ),
				'type'       => 'input_text',
				'attributes' => [ 'size' => 5 ],
			],
			LanguagesTable::COLUMN_ISO_639_2_CODE => [
				'header'     => __( 'ISO&#160;639-2', 'multilingualpress' ),
				'type'       => 'input_text',
				'attributes' => [ 'size' => 5 ],
			],
			LanguagesTable::COLUMN_LOCALE         => [
				'header'     => __( 'Locale', 'multilingualpress' ),
				'type'       => 'input_text',
				'attributes' => [ 'size' => 5 ],
			],
			LanguagesTable::COLUMN_PRIORITY       => [
				'header'     => __( 'Priority', 'multilingualpress' ),
				'type'       => 'input_number',
				'attributes' => [
					'min'  => 1,
					'max'  => 10,
					'size' => 3,
				],
			],
		];
	}
}
