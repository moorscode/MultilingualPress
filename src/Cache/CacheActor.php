<?php # -*- coding: utf-8 -*-

declare( strict_types = 1 );

namespace Inpsyde\MultilingualPress\Cache;

/**
 * Performs a specific action.
 */
class CacheActor {

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var callable
	 */
	private $callback;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @param Cache    $cache    Cache object.
	 * @param callable $callback The callback.
	 */
	public function __construct( Cache $cache, callable $callback ) {

		$this->cache = $cache;

		$this->callback = $callback;
	}

	/**
	 * Executes the injected callback, and passes the injected cache object as well as the original arguments passed to
	 * this method as arguments to the callback.
	 *
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function act( ...$args ) {

		array_unshift( $args, $this->cache );

		return ( $this->callback )( ...$args );
	}
}
