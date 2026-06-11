<?php

namespace RestApiExplorer\Tests\Func;

use RestApiExplorer\Admin\RouteDiscovery;
use WP_UnitTestCase;

/**
 * Verifies that RouteDiscovery returns well-formed route data.
 *
 * These are functional tests — they run against a live WordPress instance
 * with the plugin loaded, so the REST server is fully initialised.
 */
class RouteDiscoveryTest extends WP_UnitTestCase {

    protected function setUp(): void {
        parent::setUp();
        // Start each test with a cold cache so results are freshly queried.
        RouteDiscovery::clear_cache();
    }

    public function test_get_routes_returns_array(): void {
        $routes = RouteDiscovery::get_routes();
        $this->assertIsArray( $routes );
    }

    public function test_get_routes_is_not_empty(): void {
        $routes = RouteDiscovery::get_routes();
        $this->assertNotEmpty( $routes, 'Expected at least one REST route to be registered.' );
    }

    public function test_each_route_has_required_keys(): void {
        $routes = RouteDiscovery::get_routes();

        foreach ( $routes as $route ) {
            $this->assertArrayHasKey( 'path', $route, 'Route is missing "path" key.' );
            $this->assertArrayHasKey( 'namespace', $route, 'Route is missing "namespace" key.' );
            $this->assertArrayHasKey( 'methods', $route, 'Route is missing "methods" key.' );
            $this->assertArrayHasKey( 'endpoints', $route, 'Route is missing "endpoints" key.' );
        }
    }

    public function test_route_methods_are_strings(): void {
        $routes = RouteDiscovery::get_routes();

        foreach ( $routes as $route ) {
            foreach ( $route['methods'] as $method ) {
                $this->assertIsString( $method );
                $this->assertMatchesRegularExpression(
                    '/^(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)$/',
                    $method,
                    "Unexpected HTTP method: {$method}"
                );
            }
        }
    }

    public function test_routes_are_sorted_by_path(): void {
        $routes = RouteDiscovery::get_routes();
        $paths  = array_column( $routes, 'path' );
        $sorted = $paths;
        sort( $sorted );
        $this->assertSame( $sorted, $paths, 'Routes should be sorted alphabetically by path.' );
    }

    public function test_get_routes_is_cached_on_second_call(): void {
        RouteDiscovery::get_routes(); // primes transient
        $cached = get_transient( 'rae_routes' );
        $this->assertIsArray( $cached, 'Routes should be stored in a transient after first call.' );
    }

    public function test_known_wp_core_routes_present(): void {
        $routes = RouteDiscovery::get_routes();
        $paths  = array_column( $routes, 'path' );

        // WordPress core always registers these routes.
        $this->assertContains( '/wp/v2/posts', $paths, 'Expected WP core /wp/v2/posts route.' );
    }
}
