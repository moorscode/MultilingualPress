<?php # -*- coding: utf-8 -*-

declare( strict_types = 1 );

namespace Inpsyde\MultilingualPress\LanguageManager;

class LanguageListTable extends \WP_List_Table
{
	protected $screen = 'mlp_language_manager';

	// used in inherited method display()
	public $_args = [];

	public $items = [];

	/**
	 *
	 *
	 * @var \Inpsyde\MultilingualPress\API\Languages
	 */
	private $languages;

	/**
	 * LanguageListTable constructor.
	 *
	 * @param \Inpsyde\MultilingualPress\API\Languages $languages
	 */
	public function __construct( \Inpsyde\MultilingualPress\API\Languages $languages )
	{
		$this->_args = [
			'plural' => '',
			'singular' => '',
			'ajax' => false,
			'screen' => null,
		];
		$this->languages = $languages;
		$this->screen = new class {
			public $id = 'foo';
			public function render_screen_reader_content() {
			}
		};
	}

	public function prepare_items()
	{
		$this->items = $this->languages->get_all_languages();
	}

	public function display_rows_or_placeholder() {
		if ( $this->has_items() ) {
			//$this->display_rows();

			print '<pre>' . print_r( $this->items, 1 ) . '</pre>';

		} else {
			/*
			echo '<tr class="no-items"><td class="colspanchange" colspan="' . $this->get_column_count() . '">';
			$this->no_items();
			echo '</td></tr>';
			*/
			echo 'nothing found';
		}
	}
}
