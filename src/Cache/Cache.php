<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Cache;

/**
 * Interface for all cache implementations.
 */
interface Cache {

	const DEFAULT_GROUP = 'mlp';

	/**
	 * Adds the given data to the cache unless it is set already, using the key generated from the key base and the
	 * given key fragment(s).
	 *
	 * @param mixed $data          The data to save to the cache.
	 * @param mixed $key_fragments Optional. Fragment(s) to generate the cache key from. Defaults to array().
	 * @param int   $expire        Optional. When to expire the cache, in seconds. Defaults to 0 (no expiration).
	 *
	 * @return bool
	 */
	public function add( $data, array $key_fragments = [], int $expire = 0 ): bool;

	/**
	 * Removes the data from the cache, using the key generated from the key base and the given key fragment(s).
	 *
	 * @param mixed $key_fragments Optional. Fragment(s) to generate the cache key from. Defaults to array().
	 *
	 * @return bool
	 */
	public function delete( array $key_fragments = [] ): bool;

	/**
	 * Removes the data from the cache, using the given key.
	 *
	 * @param string $key The cache key.
	 *
	 * @return bool
	 */
	public function delete_for_key( string $key ): bool;

	/**
	 * Removes all data from the cache.
	 *
	 * @return bool
	 */
	public function flush(): bool;

	/**
	 * Returns the data from the cache, using the key generated from the key base and the given key fragment(s).
	 *
	 * @param mixed $key_fragments Optional. Fragment(s) to generate the cache key from. Defaults to array().
	 * @param bool  $force         Optional. Update the local cache from the persistent cache? Defaults to false.
	 *
	 * @return mixed|bool
	 */
	public function get( array $key_fragments = [], bool $force = false );

	/**
	 * Registers the execution of the given callback for the given action hook(s).
	 *
	 * @param callable        $callback The callback.
	 * @param string|string[] $actions  One or more action hooks.
	 *
	 * @return void
	 */
	public function register_callback_for_action( callable $callback, $actions );

	/**
	 * Registers the deletion of the cached data for the given action hook(s), using the key generated from the key base
	 * and the given key fragment(s).
	 *
	 * @param string|string[] $actions       One or more action hooks.
	 * @param mixed           $key_fragments Optional. Fragment(s) to generate the cache key from. Defaults to array().
	 *
	 * @return void
	 */
	public function register_deletion_action( $actions, array $key_fragments = [] );

	/**
	 * Replaces the original data in the cache with the given data, using the key generated from the key base and the
	 * given key fragment(s).
	 *
	 * @param mixed $data          The data to save to the cache.
	 * @param mixed $key_fragments Optional. Fragment(s) to generate the cache key from. Defaults to array().
	 * @param int   $expire        Optional. When to expire the cache, in seconds. Defaults to 0 (no expiration).
	 *
	 * @return bool
	 */
	public function replace( $data, array $key_fragments = [], int $expire = 0 ): bool;

	/**
	 * Saves the given data to the cache, using the key generated from the key base and the given key fragment(s).
	 *
	 * @param mixed $data          The data to save to the cache.
	 * @param mixed $key_fragments Optional. Fragment(s) to generate the cache key from. Defaults to array().
	 * @param int   $expire        Optional. When to expire the cache, in seconds. Defaults to 0 (no expiration).
	 *
	 * @return bool
	 */
	public function set( $data, array $key_fragments = [], int $expire = 0 ): bool;

	/**
	 * Switches to the specific cache for the site with the given ID.
	 *
	 * @param int $site_id The new site ID.
	 *
	 * @return void
	 */
	public function switch_to_site( int $site_id );
}
