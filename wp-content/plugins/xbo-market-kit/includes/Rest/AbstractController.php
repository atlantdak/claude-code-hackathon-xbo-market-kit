<?php
declare(strict_types=1);

namespace XboMarketKit\Rest;

use WP_REST_Controller;
use WP_REST_Response;

abstract class AbstractController extends WP_REST_Controller {

	protected string $namespace = 'xbo/v1';

	protected function success_response( array $data, int $status = 200 ): WP_REST_Response {
		$response = new WP_REST_Response( $data, $status );
		$response->header( 'X-XBO-Cache', 'hit-or-miss' );
		return $response;
	}

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

	protected function get_api_client(): \XboMarketKit\Api\ApiClient {
		return \XboMarketKit\Plugin::instance()->get_api_client();
	}
}
