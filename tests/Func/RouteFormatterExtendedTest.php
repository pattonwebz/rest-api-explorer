<?php
namespace RestApiExplorer\Tests\Func;
use RestApiExplorer\Helpers\RouteFormatter;
use WP_UnitTestCase;
class RouteFormatterExtendedTest extends WP_UnitTestCase {
    public function test_uses_longest_matching_namespace(): void {
        $route = RouteFormatter::format_route(
            '/rest-api-explorer/v1/test',
            [ $this->make_endpoint( [ 'GET' => true ], '__return_true' ) ],
            [ 'rest-api-explorer', 'rest-api-explorer/v1' ]
        );
        $this->assertSame( 'rest-api-explorer/v1', $route['namespace'] );
    }
    public function test_aggregates_unique_methods_and_skips_empty_endpoint_methods(): void {
        $route = RouteFormatter::format_route(
            '/wp/v2/posts',
            [
                [ 'methods' => [ 'GET' => true, 'POST' => true ], 'permission_callback' => '__return_true' ],
                [ 'methods' => [], 'permission_callback' => '__return_true' ],
                [ 'methods' => [ 'GET' => true, 'DELETE' => true ], 'permission_callback' => '__return_true' ],
            ],
            [ 'wp/v2' ]
        );
        $this->assertSame( [ 'GET', 'POST', 'DELETE' ], $route['methods'] );
        $this->assertCount( 2, $route['endpoints'] );
    }
    public function test_formats_args_with_expected_defaults_and_skips_non_array_values(): void {
        $route = RouteFormatter::format_route(
            '/wp/v2/items',
            [
                [
                    'methods'             => [ 'GET' => true ],
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'page' => [
                            'type'        => 'integer',
                            'required'    => true,
                            'default'     => 1,
                            'enum'        => [ 1, 2 ],
                            'minimum'     => 1,
                            'maximum'     => 10,
                            'description' => 'Page number',
                        ],
                        'junk' => 'not-an-array',
                    ],
                ],
            ],
            [ 'wp/v2' ]
        );
        $args = $route['endpoints'][0]['args'];
        $this->assertCount( 1, $args );
        $this->assertSame( 'page', $args[0]['name'] );
        $this->assertSame( 'integer', $args[0]['type'] );
        $this->assertTrue( $args[0]['required'] );
        $this->assertSame( [ 1, 2 ], $args[0]['enum'] );
        $this->assertSame( 1, $args[0]['minimum'] );
        $this->assertSame( 10, $args[0]['maximum'] );
    }
    public function test_permission_label_formats_array_callback_with_class_and_method(): void {
        $route = RouteFormatter::format_route(
            '/wp/v2/test',
            [ $this->make_endpoint( [ 'GET' => true ], [ $this, 'dummy_permission' ] ) ],
            [ 'wp/v2' ]
        );
        $this->assertStringContainsString( static::class . '::dummy_permission()', $route['endpoints'][0]['permission_label'] );
    }
    public function test_auth_level_is_custom_for_non_supported_callback_type(): void {
        $route = RouteFormatter::format_route(
            '/wp/v2/test',
            [ $this->make_endpoint( [ 'GET' => true ], new \stdClass() ) ],
            [ 'wp/v2' ]
        );
        $this->assertSame( 'custom', $route['endpoints'][0]['auth_level'] );
        $this->assertSame( 'Closure', $route['endpoints'][0]['permission_label'] );
    }
    public function test_route_description_uses_first_non_empty_description(): void {
        $route = RouteFormatter::format_route(
            '/wp/v2/thing',
            [
                [ 'methods' => [ 'GET' => true ], 'permission_callback' => '__return_true', 'description' => '' ],
                [ 'methods' => [ 'POST' => true ], 'permission_callback' => '__return_true', 'description' => 'Create item' ],
                [ 'methods' => [ 'DELETE' => true ], 'permission_callback' => '__return_true', 'description' => 'Delete item' ],
            ],
            [ 'wp/v2' ]
        );
        $this->assertSame( 'Create item', $route['description'] );
    }
    public function dummy_permission(): bool {
        return true;
    }
    private function make_endpoint( array $methods, $permission_callback ): array {
        return [
            'methods'             => $methods,
            'permission_callback' => $permission_callback,
        ];
    }
}
