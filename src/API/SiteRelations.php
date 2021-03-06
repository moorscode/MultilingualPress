<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\API;

/**
 * Interface for all site relations API implementations.
 *
 * @package Inpsyde\MultilingualPress\API
 * @since   3.0.0
 */
interface SiteRelations {

	/**
	 * Deletes the relationship between the given sites. If only one site is given, all its relations will be deleted.
	 *
	 * @since 3.0.0
	 *
	 * @param int $site_1 Site ID.
	 * @param int $site_2 Optional. Another site ID. Defaults to 0.
	 *
	 * @return int The number of rows affected.
	 */
	public function delete_relation( int $site_1, int $site_2 = 0 ): int;

	/**
	 * Returns an array with site IDs as keys and arrays with the IDs of all related sites as values.
	 *
	 * @since 3.0.0
	 *
	 * @return int[] The array with site IDs as keys and arrays with the IDs of all related sites as values.
	 */
	public function get_all_relations(): array;

	/**
	 * Returns an array holding the IDs of all sites related to the site with the given (or current) ID.
	 *
	 * @since 3.0.0
	 *
	 * @param int  $site_id      Optional. Site ID. Defaults to 0.
	 * @param bool $include_site Optional. Whether or not to include the given site ID. Defaults to false.
	 *
	 * @return int[] The array holding the IDs of all sites related to the site with the given (or current) ID.
	 */
	public function get_related_site_ids( int $site_id = 0, bool $include_site = false ): array;

	/**
	 * Creates relations between one site and one or more other sites.
	 *
	 * @since 3.0.0
	 *
	 * @param int   $base_site_id Base site ID.
	 * @param int[] $site_ids     An array of site IDs.
	 *
	 * @return int The number of rows affected.
	 */
	public function insert_relations( int $base_site_id, array $site_ids ): int;

	/**
	 * Sets the relations for the site with the given ID.
	 *
	 * @since 3.0.0
	 *
	 * @param int   $base_site_id Base site ID.
	 * @param int[] $site_ids     Site IDs.
	 *
	 * @return int The number of rows affected.
	 */
	public function set_relationships( int $base_site_id, array $site_ids ): int;
}
