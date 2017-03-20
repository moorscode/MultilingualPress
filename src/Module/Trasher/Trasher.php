<?php # -*- coding: utf-8 -*-

declare( strict_types = 1 );

namespace Inpsyde\MultilingualPress\Module\Trasher;

use Inpsyde\MultilingualPress\API\ContentRelations;

/**
 * Post trasher.
 *
 * @package Inpsyde\MultilingualPress\Module\Trasher
 * @since   3.0.0
 */
class Trasher {

	/**
	 * @var ContentRelations
	 */
	private $content_relations;

	/**
	 * @var TrasherSettingRepository
	 */
	private $setting_repository;

	/**
	 * @var bool
	 */
	private static $trashing_related_posts = false;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @since 3.0.0
	 *
	 * @param TrasherSettingRepository $setting_repository Trasher setting repository object.
	 * @param ContentRelations         $content_relations  Content relations API object.
	 */
	public function __construct( TrasherSettingRepository $setting_repository, ContentRelations $content_relations ) {

		$this->setting_repository = $setting_repository;

		$this->content_relations = $content_relations;
	}

	/**
	 * Trashes all related posts.
	 *
	 * @since   3.0.0
	 * @wp-hook wp_trash_post
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return int The number of related posts trashed.
	 */
	public function trash_related_posts( $post_id ): int {

		if ( self::$trashing_related_posts || ! $this->setting_repository->get_setting( (int) $post_id ) ) {
			return 0;
		}

		// prevent recursions
		self::$trashing_related_posts = true;

		$current_site_id = get_current_blog_id();

		$related_posts = $this->content_relations->get_relations( $current_site_id, (int) $post_id, 'post' );

		unset( $related_posts[ $current_site_id ] );

		if ( ! $related_posts ) {
			return 0;
		}

		$trashed_post = 0;

		array_walk( $related_posts, function ( $post_id, $site_id ) use ( &$trashed_post ) {

			switch_to_blog( $site_id );
			$trashed = wp_trash_post( $post_id );
			restore_current_blog();

			if ( false !== $trashed && ! is_wp_error( $trashed ) ) {
				$trashed_post++;
			}
		} );

		// allow to call again
		self::$trashing_related_posts = false;

		return $trashed_post;
	}
}
