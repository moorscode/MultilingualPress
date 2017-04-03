<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Common\Nonce\Nonce;
use Inpsyde\MultilingualPress\Common\Type\Setting;

use function Inpsyde\MultilingualPress\nonce_field;

/**
 * Class Mlp_Language_Manager_Page_View
 *
 * @version 2014.07.16
 * @author  Inpsyde GmbH, toscho
 * @license GPL
 */
class Mlp_Language_Manager_Page_View {

	/**
	 * @var Nonce
	 */
	private $nonce;

	/**
	 * @var Mlp_Browsable
	 */
	private $pagination_data;

	/**
	 * @var Setting
	 */
	private $setting;

	/**
	 * @var Mlp_Language_Manager_Controller
	 */
	private $controller;

	/**
	 * @param Setting                         $setting
	 * @param Mlp_Language_Manager_Controller $controller
	 * @param Mlp_Browsable                   $pagination_data
	 * @param Nonce                           $nonce Nonce object.
	 */
	public function __construct(
		Setting $setting,
		Mlp_Language_Manager_Controller $controller,
		Mlp_Browsable $pagination_data,
		Nonce $nonce
	) {

		$this->setting = $setting;

		$this->controller = $controller;

		$this->pagination_data = $pagination_data;

		$this->nonce = $nonce;
	}

	/**
	 * Callback for page output.
	 *
	 */
	public function render() {

		// TODO: Remove this by refactoring things into ~\LanguageManager\LanguageManagerSettingsPageView.
		$current_page = $this->pagination_data->get_current_page();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $this->setting->title() ); ?></h1>

			<?php $this->controller->before_form(); ?>

			<form action="<?php echo esc_url( $this->setting->url() ); ?>" method="post">
				<?php // TODO: Remove action input as the action is already part of the (new) URL. ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( $this->setting->action() ); ?>">
				<input type="hidden" name="paged" value="<?php echo esc_attr( $current_page ); ?>">

				<?php
				// TODO: Remove as the nonce is already handled in the new view class.
				echo nonce_field( $this->nonce );
				?>

				<?php $this->controller->before_table(); ?>
				<?php $this->controller->show_table(); ?>
				<?php $this->controller->after_table(); ?>
				<?php submit_button(
					esc_attr__( 'Save changes', 'multilingualpress' ),
					'primary',
					'save',
					false,
					[ 'style' => 'float:left' ]
				); ?>
			</form>

			<?php $this->controller->after_form(); ?>
		</div>
		<?php
	}
}
