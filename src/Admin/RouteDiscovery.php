<?php

namespace RestApiExplorer\Admin;

use RestApiExplorer\Helpers\RouteFormatter;

class RouteDiscovery {

	private const TRANSIENT_KEY = 'rae_routes';
	private const TRANSIENT_TTL = 3600;

	public static function get_routes(): array {
		$cached = get_transient( self::TRANSIENT_KEY );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$routes = self::query_routes();
		set_transient( self::TRANSIENT_KEY, $routes, self::TRANSIENT_TTL );
		return $routes;
	}

	public static function clear_cache(): void {
		delete_transient( self::TRANSIENT_KEY );
	}

	private static function query_routes(): array {
		$server     = rest_get_server();
		$raw_routes = $server->get_routes();
		$namespaces = $server->get_namespaces();

		$formatted = [];
		foreach ( $raw_routes as $path => $endpoints ) {
			$formatted[] = RouteFormatter::format_route( $path, $endpoints, $namespaces );
		}

		usort( $formatted, static fn ( array $a, array $b ) => strcmp( $a['path'], $b['path'] ) );

		return $formatted;
	}
}
