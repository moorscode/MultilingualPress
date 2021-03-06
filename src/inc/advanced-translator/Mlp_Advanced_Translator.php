<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\API\SiteRelations;
use Inpsyde\MultilingualPress\Asset\AssetManager;
use Inpsyde\MultilingualPress\Factory\NonceFactory;
use Inpsyde\MultilingualPress\Module\Module;
use Inpsyde\MultilingualPress\Module\ModuleManager;

use function Inpsyde\MultilingualPress\resolve;

/**
 * Advanced translator.
 */
class Mlp_Advanced_Translator {

	/**
	 * @var string
	 */
	private $ajax_action = 'mlp_process_post_data';

	/**
	 * @var Mlp_Translatable_Post_Data_Interface
	 */
	private $basic_data;

	/**
	 * The view class.
	 *
	 * @var Mlp_Advanced_Translator_View
	 */
	private $view;

	/**
	 * Constructor
	 */
	public function __construct() {

		// Quit here if module is turned off
		if ( ! $this->register_setting() ) {
			return;
		}

		if ( wp_doing_ajax() ) {
			add_action( "wp_ajax_{$this->ajax_action}", [ $this, 'process_post_data' ] );
		}

		add_action( 'mlp_post_translator_init', [ $this, 'setup' ] );
		add_filter( 'mlp_external_save_method', '__return_true' );
	}

	/**
	 * Set up the properties.
	 *
	 * @wp-hook mlp_post_translator_init
	 *
	 * @param array $base_data Base data.
	 *
	 * @return void
	 */
	public function setup( array $base_data ) {

		$this->basic_data = $base_data['basic_data'];

		$translation_data = new Mlp_Advanced_Translator_Data(
			null,
			$base_data['basic_data'],
			$base_data['allowed_post_types'],
			resolve( 'multilingualpress.site_relations', SiteRelations::class ),
			resolve( 'multilingualpress.nonce_factory', NonceFactory::class )
		);

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			add_action( 'save_post', [ $translation_data, 'save' ], 10, 2 );
		}

		$this->view = new Mlp_Advanced_Translator_View( $translation_data );
		add_action( 'mlp_translation_meta_box_registered', [ $this, 'register_metabox_view_details' ], 10, 2 );

		// Disable the checkbox, we can translate auto-drafts.
		add_filter( 'mlp_post_translator_activation_checkbox', '__return_false' );
		add_filter( 'mlp_translation_meta_box_view_callbacks', '__return_empty_array' );
	}

	/**
	 *
	 * @wp-hook mlp_translation_meta_box_registered
	 *
	 * @param WP_Post $post
	 * @param int     $blog_id
	 *
	 * @return void
	 */
	public function register_metabox_view_details( WP_Post $post, $blog_id ) {

		// get the current remote post status
		$remote_post = $this->basic_data->get_remote_post( $post, $blog_id );
		$is_trashed  = isset( $remote_post->post_status ) && $remote_post->post_status == 'trash';

		// set the base
		$base = 'mlp_translation_meta_box_';

		// check if the remote post is trashed
		// if it is so, show the warning
		if ( $is_trashed ) {
			add_action( $base . 'top_' . $blog_id, [ $this->view, 'show_trashed_message' ], 10, 3 );

			return;
		}

		// add the actions if the remote is not trashed
		add_action( $base . 'top_' . $blog_id, [ $this, 'localize_script' ] );

		add_action( $base . 'top_' . $blog_id, [ $this->view, 'blog_id_input' ], 10, 3 );

		if ( post_type_supports( $post->post_type, 'title' ) ) {
			add_action( $base . 'top_' . $blog_id, [ $this->view, 'show_title' ], 10, 3 );
		}

		add_action( $base . 'top_' . $blog_id, [ $this->view, 'show_name' ], 10, 3 );

		if ( post_type_supports( $post->post_type, 'editor' ) ) {
			add_action( $base . 'main_' . $blog_id, [ $this->view, 'show_editor' ], 10, 3 );
		} else {
			remove_action( 'media_buttons', [ $this->view, 'show_copy_button' ], 20 );
		}

		if ( post_type_supports( $post->post_type, 'excerpt' ) ) {
			add_action( $base . 'main_' . $blog_id, [ $this->view, 'show_excerpt' ], 10, 3 );
		}

		if ( post_type_supports( $post->post_type, 'thumbnail' ) ) {
			add_action( $base . 'main_' . $blog_id, [ $this->view, 'show_thumbnail_checkbox' ], 11, 3 );
		}

		$taxonomies = get_object_taxonomies( $post, 'objects' );
		if ( ! empty( $taxonomies ) ) {
			add_action( $base . 'bottom_' . $blog_id, [ $this->view, 'show_taxonomies' ], 10, 3 );
		}
	}

	/**
	 * Register our UI for the module manager.
	 *
	 * @return bool
	 */
	private function register_setting() {

		/** @var ModuleManager $module_manager */
		$module_manager = resolve( 'multilingualpress.module_manager', ModuleManager::class );

		return $module_manager->register_module( new Module( 'advanced_translator', [
			'description' => __(
				'Use the WYSIWYG editor to write all translations on one screen, including thumbnails and taxonomies.',
				'multilingualpress'
			),
			'name'        => __( 'Advanced Translator', 'multilingualpress' ),
			'active'      => true,
		] ) );
	}

	/**
	 * Provides necessary data for the CopyPost JavaScript module.
	 *
	 * @wp-hook admin_enqueue_scripts
	 *
	 * @return void
	 */
	public function localize_script() {

		resolve( 'multilingualpress.asset_manager', AssetManager::class )->add_script_data(
			'multilingualpress-admin',
			'mlpCopyPostSettings', [
				'action' => $this->ajax_action,
				'siteID' => get_current_blog_id(),
			]
		);
	}

	/**
	 * Processes (and filters) a post's data before it is copied to a remote post.
	 *
	 * @wp-hook wp_ajax_{$this->ajax_action}
	 *
	 * @return void
	 */
	public function process_post_data() {

		$current_site_id = get_current_blog_id();

		$current_post_id = (int) filter_input( INPUT_POST, 'current_post_id' );

		$remote_site_id = (int) filter_input( INPUT_POST, 'remote_site_id' );

		if ( ! ( $current_post_id && $remote_site_id ) ) {
			wp_send_json_error();
		}

		$title = filter_input( INPUT_POST, 'title' );
		/**
		 * Filters a post's title for a remote site.
		 *
		 * @param string $title Post title.
		 * @param int    $current_site_id Source site ID.
		 * @param int    $current_post_id Source post ID.
		 * @param int    $remote_site_id  Remote site ID.
		 */
		$title = apply_filters(
			'mlp_process_post_title_for_remote_site',
			$title,
			$current_site_id,
			$current_post_id,
			$remote_site_id
		);
		$title = esc_attr( $title );

		$slug = filter_input( INPUT_POST, 'slug' );
		/**
		 * Filters a post's slug for a remote site.
		 *
		 * @param string $slug            Post slug.
		 * @param int    $current_site_id Source site ID.
		 * @param int    $current_post_id Source post ID.
		 * @param int    $remote_site_id  Remote site ID.
		 */
		$slug = apply_filters(
			'mlp_process_post_slug_for_remote_site',
			$slug,
			$current_site_id,
			$current_post_id,
			$remote_site_id
		);
		$slug = esc_attr( $slug );

		$tmce_content = filter_input( INPUT_POST, 'tinyMceContent' );
		/**
		 * Filters a post's TinyMCE content for a remote site.
		 *
		 * @param string $content         Post content.
		 * @param int    $current_site_id Source site ID.
		 * @param int    $current_post_id Source post ID.
		 * @param int    $remote_site_id  Remote site ID.
		 */
		$tmce_content = (string) apply_filters(
			'mlp_process_post_tmce_content_for_remote_site',
			$tmce_content,
			$current_site_id,
			$current_post_id,
			$remote_site_id
		);

		$content = filter_input( INPUT_POST, 'content' );
		/**
		 * Filters a post's content for a remote site.
		 *
		 * @param string $content         Post content.
		 * @param int    $current_site_id Source site ID.
		 * @param int    $current_post_id Source post ID.
		 * @param int    $remote_site_id  Remote site ID.
		 */
		$content = (string) apply_filters(
			'mlp_process_post_content_for_remote_site',
			$content,
			$current_site_id,
			$current_post_id,
			$remote_site_id
		);

		$excerpt = (string) filter_input( INPUT_POST, 'excerpt' );
		/**
		 * Filters a post's excerpt for a remote site.
		 *
		 * @param string $excerpt         Post excerpt.
		 * @param int    $current_site_id Source site ID.
		 * @param int    $current_post_id Source post ID.
		 * @param int    $remote_site_id  Remote site ID.
		 */
		$excerpt = apply_filters(
			'mlp_process_post_excerpt_for_remote_site',
			$excerpt,
			$current_site_id,
			$current_post_id,
			$remote_site_id
		);

		/**
		 * Filters a post's data for a remote site.
		 *
		 * @param array $data            Post data.
		 * @param int   $current_site_id Source site ID.
		 * @param int   $current_post_id Source post ID.
		 * @param int   $remote_site_id  Remote site ID.
		 */
		$data = (array) apply_filters(
			'mlp_process_post_data_for_remote_site',
			[
				'siteId'         => $remote_site_id,
				'title'          => $title,
				'slug'           => $slug,
				'tinyMceContent' => $tmce_content,
				'content'        => $content,
				'excerpt'        => $excerpt,
			],
			$current_site_id,
			$current_post_id,
			$remote_site_id
		);
		wp_send_json_success( $data );
	}
}
