<?php

namespace RestApiExplorer\Helpers;

class RouteFormatter {

	public static function format_route( string $path, array $endpoints, array $namespaces ): array {
		$namespace        = self::detect_namespace( $path, $namespaces );
		$all_methods      = [];
		$formatted_endpoints = [];

		foreach ( $endpoints as $endpoint ) {
			if ( empty( $endpoint['methods'] ) ) {
				continue;
			}

			$methods     = array_keys( array_filter( (array) $endpoint['methods'] ) );
			$all_methods = array_merge( $all_methods, $methods );

			$formatted_endpoints[] = [
				'methods'          => $methods,
				'args'             => self::format_args( $endpoint['args'] ?? [] ),
				'auth_level'       => self::detect_auth_level( $endpoint ),
				'permission_label' => self::permission_label( $endpoint ),
				'description'      => $endpoint['description'] ?? '',
			];
		}

		return [
			'path'        => $path,
			'namespace'   => $namespace,
			'methods'     => array_values( array_unique( $all_methods ) ),
			'endpoints'   => $formatted_endpoints,
			'description' => self::route_description( $endpoints ),
		];
	}

	private static function detect_namespace( string $path, array $namespaces ): string {
		$matched = '';
		$trimmed = ltrim( $path, '/' );
		foreach ( $namespaces as $ns ) {
			if ( str_starts_with( $trimmed, $ns ) && strlen( $ns ) > strlen( $matched ) ) {
				$matched = $ns;
			}
		}
		return $matched ?: 'core';
	}

	private static function format_args( array $args ): array {
		$formatted = [];
		foreach ( $args as $name => $arg ) {
			if ( ! is_array( $arg ) ) {
				continue;
			}
			$formatted[] = [
				'name'        => $name,
				'type'        => $arg['type'] ?? 'string',
				'required'    => (bool) ( $arg['required'] ?? false ),
				'default'     => $arg['default'] ?? null,
				'description' => $arg['description'] ?? '',
				'enum'        => $arg['enum'] ?? null,
				'minimum'     => $arg['minimum'] ?? null,
				'maximum'     => $arg['maximum'] ?? null,
			];
		}
		return $formatted;
	}

	private static function detect_auth_level( array $endpoint ): string {
		$cb = $endpoint['permission_callback'] ?? null;

		if ( null === $cb ) {
			return 'none';
		}

		if ( is_string( $cb ) ) {
			return match ( $cb ) {
				'__return_true'  => 'public',
				'__return_false' => 'private',
				default          => 'custom',
			};
		}

		if ( $cb instanceof \Closure || is_array( $cb ) ) {
			return 'authenticated';
		}

		return 'custom';
	}

	private static function permission_label( array $endpoint ): string {
		$cb = $endpoint['permission_callback'] ?? null;

		if ( null === $cb ) {
			return 'None';
		}

		if ( is_string( $cb ) ) {
			return match ( $cb ) {
				'__return_true'  => 'Public',
				'__return_false' => 'Blocked',
				default          => $cb,
			};
		}

		if ( is_array( $cb ) && count( $cb ) === 2 ) {
			$class  = is_object( $cb[0] ) ? get_class( $cb[0] ) : (string) $cb[0];
			$method = (string) $cb[1];
			return "{$class}::{$method}()";
		}

		return 'Closure';
	}

	private static function route_description( array $endpoints ): string {
		foreach ( $endpoints as $endpoint ) {
			if ( ! empty( $endpoint['description'] ) ) {
				return (string) $endpoint['description'];
			}
		}
		return '';
	}
}
