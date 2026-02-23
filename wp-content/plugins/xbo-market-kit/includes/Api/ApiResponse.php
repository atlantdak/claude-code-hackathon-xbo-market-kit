<?php
declare(strict_types=1);

namespace XboMarketKit\Api;

class ApiResponse {

	public function __construct(
		public readonly bool $success,
		public readonly array $data,
		public readonly string $error_message = '',
		public readonly int $status_code = 200,
	) {}

	public static function success( array $data ): self {
		return new self( true, $data );
	}

	public static function error( string $message, int $status_code = 500 ): self {
		return new self( false, array(), $message, $status_code );
	}
}
