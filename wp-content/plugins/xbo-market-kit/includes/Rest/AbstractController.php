<?php
/**
 * AbstractController class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Rest;

use WP_REST_Controller;
use WP_REST_Response;

/**
 * Abstract base class for REST API controllers.
 *
 * Provides shared helper methods for building success and error responses
 * and accessing the API client.
 */
abstract class AbstractController extends WP_REST_Controller {

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'xbo/v1'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Build a successful REST response.
	 *
	 * @param array $data   Response data.
	 * @param int   $status HTTP status code.
	 * @return WP_REST_Response REST response object.
	 */
	protected function success_response( array $data, int $status = 200 ): WP_REST_Response {
		$response = new WP_REST_Response( $data, $status );
		$response->header( 'X-XBO-Cache', 'hit-or-miss' );
		return $response;
	}

	/**
	 * Build an error REST response.
	 *
	 * @param string $message Error message.
	 * @param string $code    Error code identifier.
	 * @param int    $status  HTTP status code.
	 * @return WP_REST_Response REST response object.
	 */
	protected function error_response( string $message, string $code = 'xbo_error', int $status = 500 ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'code'    => $code,
				'message' => $message,
				'data'    => array( 'status' => $status ),
			),
			$status
		);
	}

	/**
	 * Get the API client from the plugin singleton.
	 *
	 * @return \XboMarketKit\Api\ApiClient API client instance.
	 */
	protected function get_api_client(): \XboMarketKit\Api\ApiClient {
		return \XboMarketKit\Plugin::instance()->get_api_client();
	}
}
