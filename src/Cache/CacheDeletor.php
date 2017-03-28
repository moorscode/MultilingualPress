<?php # -*- coding: utf-8 -*-

declare( strict_types = 1 );

namespace Inpsyde\MultilingualPress\Cache;

/**
 * Handles deletion of a specific cache entry.
 */
class CacheDeletor {

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var string
	 */
	private $key;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @param Cache  $cache Cache object.
	 * @param string $key   The cache key.
	 */
	public function __construct( Cache $cache, string $key ) {

		$this->cache = $cache;
		$this->key   = $key;
	}

	/**
	 * Removes the data from the injected cache, using the given key.
	 *
	 * @return bool
	 */
	public function delete(): bool {

		return $this->cache->delete_for_key( $this->key );
	}
}
