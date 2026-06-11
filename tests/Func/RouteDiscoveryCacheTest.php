<?php
namespace RestApiExplorer\Tests\Func;
use RestApiExplorer\Admin\RouteDiscovery;
use WP_UnitTestCase;
class RouteDiscoveryCacheTest extends WP_UnitTestCase {
    protected function setUp(): void {
        parent::setUp();
        RouteDiscovery::clear_cache();
    }
    public function test_get_routes_returns_cached_transient_when_present(): void {
        $cached = [ [ 'path' => '/cached-route', 'namespace' => 'cached', 'methods' => [ 'GET' ], 'endpoints' => [] ] ];
        set_transient( 'rae_routes', $cached, HOUR_IN_SECONDS );
        $this->assertSame( $cached, RouteDiscovery::get_routes() );
    }
    public function test_get_routes_ignores_non_array_cache_and_rebuilds(): void {
        set_transient( 'rae_routes', 'bad-cache', HOUR_IN_SECONDS );
        $routes = RouteDiscovery::get_routes();
        $this->assertIsArray( $routes );
        $this->assertNotSame( 'bad-cache', $routes );
        $this->assertIsArray( get_transient( 'rae_routes' ) );
    }
    public function test_clear_cache_removes_transient(): void {
        set_transient( 'rae_routes', [ 'x' => 'y' ], HOUR_IN_SECONDS );
        RouteDiscovery::clear_cache();
        $this->assertFalse( get_transient( 'rae_routes' ) );
    }
    public function test_discovered_routes_have_expected_shape_for_each_endpoint(): void {
        $routes = RouteDiscovery::get_routes();
        $this->assertNotEmpty( $routes );
        foreach ( $routes as $route ) {
            foreach ( $route['endpoints'] as $endpoint ) {
                $this->assertArrayHasKey( 'methods', $endpoint );
                $this->assertArrayHasKey( 'args', $endpoint );
                $this->assertArrayHasKey( 'auth_level', $endpoint );
                $this->assertArrayHasKey( 'permission_label', $endpoint );
            }
        }
    }
}
