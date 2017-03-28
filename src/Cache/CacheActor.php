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
	 * @todo With MultilingualPress 3.0.0, add callable type hint.
	 *
	 * @param Cache    $cache    Cache object.
	 * @param callable $callback The callback.
	 */
	public function __construct( Cache $cache, $callback ) {

		$this->cache = $cache;

		$this->callback = $callback;
	}

	/**
	 * Executes the injected callback, and passes the injected cache object as well as the original arguments passed to
	 * this method as arguments to the callback.
	 *
	 * @return mixed
	 */
	public function act() {

		return call_user_func( array( $this, 'callback' ), $this->cache, func_get_args() );
	}
}
