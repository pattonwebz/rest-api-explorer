<?php

namespace RestApiExplorer\Rest;

class TestController {

	private const NAMESPACE = 'rest-api-explorer/v1';
	private const ROUTE     = '/test-request';

	public static function register(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ self::class, 'handle' ],
				'permission_callback' => static fn () => current_user_can( 'manage_options' ),
				'args'                => [
					'method'  => [
						'type'     => 'string',
						'enum'     => [ 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ],
						'required' => true,
					],
					'path'    => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
					'params'  => [
						'type'    => 'object',
						'default' => [],
					],
					'body'    => [
						'type'    => 'object',
						'default' => [],
					],
					'headers' => [
						'type'    => 'object',
						'default' => [],
					],
					'auth'    => [
						'type'    => 'object',
						'default' => [],
					],
				],
			]
		);
	}

	public static function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$method  = strtoupper( $request->get_param( 'method' ) );
		$path    = $request->get_param( 'path' );
		$params  = (array) $request->get_param( 'params' );
		$body    = (array) $request->get_param( 'body' );
		$auth    = (array) $request->get_param( 'auth' );
		$custom_headers = (array) $request->get_param( 'headers' );

		// Build target URL
		$base_url = rest_url( ltrim( $path, '/' ) );
		if ( ! empty( $params ) ) {
			$base_url = add_query_arg( $params, $base_url );
		}

		// Build auth + headers
		$request_auth = self::build_request_auth( $auth, $custom_headers );
		$headers      = $request_auth['headers'];

		// Execute request
		$start   = microtime( true );
		$wp_args = [
			'method'    => $method,
			'headers'   => $headers,
			'timeout'   => 30,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
		];

		if ( ! empty( $request_auth['cookies'] ) ) {
			$wp_args['cookies'] = $request_auth['cookies'];
		}

		if ( in_array( $method, [ 'POST', 'PUT', 'PATCH' ], true ) && ! empty( $body ) ) {
			$wp_args['body']    = wp_json_encode( $body );
			$headers['Content-Type'] = 'application/json';
			$wp_args['headers'] = $headers;
		}

		$response = wp_remote_request( $base_url, $wp_args );
		$elapsed  = round( ( microtime( true ) - $start ) * 1000 );

		if ( is_wp_error( $response ) ) {
			return new \WP_REST_Response(
				[
					'success'      => false,
					'error'        => $response->get_error_message(),
					'elapsed_ms'   => $elapsed,
					'request_sent' => self::describe_request( $method, $base_url, $wp_args ),
				],
				200
			);
		}

		$status_code    = wp_remote_retrieve_response_code( $response );
		$status_message = wp_remote_retrieve_response_message( $response );
		$raw_headers    = wp_remote_retrieve_headers( $response );
		$raw_body       = wp_remote_retrieve_body( $response );

		$resp_headers = [];
		foreach ( $raw_headers as $k => $v ) {
			$resp_headers[ $k ] = $v;
		}

		$parsed_body = null;
		$content_type = $resp_headers['content-type'] ?? '';
		if ( str_contains( $content_type, 'application/json' ) || str_contains( $content_type, 'application/javascript' ) ) {
			$parsed_body = json_decode( $raw_body, true );
		}

		return new \WP_REST_Response(
			[
				'success'      => true,
				'status_code'  => $status_code,
				'status_text'  => $status_message,
				'elapsed_ms'   => $elapsed,
				'headers'      => $resp_headers,
				'body'         => $parsed_body ?? $raw_body,
				'body_raw'     => $raw_body,
				'request_sent' => self::describe_request( $method, $base_url, $wp_args ),
			],
			200
		);
	}

	private static function build_request_auth( array $auth, array $custom ): array {
		$headers = [];
		$cookies = [];

		$auth_type = $auth['type'] ?? 'none';

		switch ( $auth_type ) {
			case 'cookie':
				$nonce = wp_create_nonce( 'wp_rest' );
				$headers['X-WP-Nonce'] = $nonce;

				foreach ( $_COOKIE as $cookie_name => $cookie_value ) {
					$cookies[] = new \WP_Http_Cookie(
						[
							'name'  => (string) $cookie_name,
							'value' => wp_unslash( (string) $cookie_value ),
						]
					);
				}
				break;

			case 'basic':
				$username = $auth['username'] ?? '';
				$password = $auth['password'] ?? '';
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				$headers['Authorization'] = 'Basic ' . base64_encode( "{$username}:{$password}" );
				break;

			case 'bearer':
				$token = $auth['token'] ?? '';
				$headers['Authorization'] = "Bearer {$token}";
				break;
		}

		foreach ( $custom as $k => $v ) {
			$headers[ sanitize_text_field( $k ) ] = sanitize_text_field( $v );
		}

		return [
			'headers' => $headers,
			'cookies' => $cookies,
		];
	}

	private static function describe_request( string $method, string $url, array $args ): array {
		$safe_headers = $args['headers'] ?? [];
		// Redact auth header value for display
		if ( isset( $safe_headers['Authorization'] ) ) {
			$safe_headers['Authorization'] = '**redacted**';
		}

		$cookie_names = [];
		if ( ! empty( $args['cookies'] ) && is_array( $args['cookies'] ) ) {
			foreach ( $args['cookies'] as $cookie ) {
				if ( $cookie instanceof \WP_Http_Cookie ) {
					$cookie_names[] = $cookie->name;
				}
			}
		}

		return [
			'method'  => $method,
			'url'     => $url,
			'headers' => $safe_headers,
			'cookies' => $cookie_names,
			'body'    => $args['body'] ?? null,
		];
	}
}
