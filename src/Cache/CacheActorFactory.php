<?php # -*- coding: utf-8 -*-

declare( strict_types = 1 );

namespace Inpsyde\MultilingualPress\Cache;

/**
 * Factory for cache actor objects.
 */
class CacheActorFactory {

	/**
	 * Creates a new cache actor object according to the given arguments, and returns it.
	 *
	 * @param Cache $cache    Cache object.
	 * @param callable  $callback The callback.
	 *
	 * @return CacheActor
	 */
	public static function create( Cache $cache, callable $callback ) {

		return new CacheActor( $cache, $callback );
	}
}
