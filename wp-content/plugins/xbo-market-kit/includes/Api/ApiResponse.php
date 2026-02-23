<?php
/**
 * ApiResponse class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Api;

/**
 * Immutable value object representing an API response.
 *
 * Encapsulates success/error state, response data, and HTTP status code.
 */
class ApiResponse {

	/**
	 * Constructor.
	 *
	 * @param bool   $success       Whether the request was successful.
	 * @param array  $data          Response data array.
	 * @param string $error_message Error message if the request failed.
	 * @param int    $status_code   HTTP status code.
	 */
	public function __construct(
		public readonly bool $success,
		public readonly array $data,
		public readonly string $error_message = '',
		public readonly int $status_code = 200,
	) {}

	/**
	 * Create a successful API response.
	 *
	 * @param array $data Response data.
	 * @return self Successful response instance.
	 */
	public static function success( array $data ): self {
		return new self( true, $data );
	}

	/**
	 * Create an error API response.
	 *
	 * @param string $message     Error message.
	 * @param int    $status_code HTTP status code.
	 * @return self Error response instance.
	 */
	public static function error( string $message, int $status_code = 500 ): self {
		return new self( false, array(), $message, $status_code );
	}
}
