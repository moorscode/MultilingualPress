<?php # -*- coding: utf-8 -*-

declare( strict_types = 1 );

namespace Inpsyde\MultilingualPress\Core\Exception;

/**
 * Exception to be thrown when a value or factory callback is to be resolved with no container instance available.
 *
 * @package Inpsyde\MultilingualPress\Core\Exception
 * @since   3.0.0
 */
class CannotResolveName extends \Exception {

	/**
	 * Returns a new exception object.
	 *
	 * @since 3.0.0
	 *
	 * @param string $name The name of the value or factory callback.
	 *
	 * @return CannotResolveName Exception object.
	 */
	public static function for_name( string $name ): CannotResolveName {

		return new static( sprintf(
			'Cannot resolve "%s". MultilingualPress has not yet been initialized.',
			$name
		) );
	}
}
