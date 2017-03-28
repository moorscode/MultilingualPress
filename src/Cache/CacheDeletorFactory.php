<?php # -*- coding: utf-8 -*-

declare( strict_types = 1 );

namespace Inpsyde\MultilingualPress\Cache;

/**
 * Factory for cache deletor objects.
 */
class CacheDeletorFactory {

	/**
	 * Creates a new cache deletor object according to the given arguments, and returns it.
	 *
	 * @param Cache $cache Cache object.
	 * @param string    $key   The cache key.
	 *
	 * @return CacheDeletor
	 */
	public static function create( Cache $cache, $key ) {

		return new CacheDeletor( $cache, $key );
	}
}
