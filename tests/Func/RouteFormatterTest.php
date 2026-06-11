<?php

namespace RestApiExplorer\Tests\Func;

use RestApiExplorer\Helpers\RouteFormatter;
use WP_UnitTestCase;

/**
 * Verifies that RouteFormatter correctly normalises WP REST route data.
 */
class RouteFormatterTest extends WP_UnitTestCase {

    // -------------------------------------------------------------------------
    // Auth level detection
    // -------------------------------------------------------------------------

    public function test_auth_level_public_for_return_true(): void {
        $route = $this->format_with_callback( '__return_true' );
        $this->assertSame( 'public', $route['endpoints'][0]['auth_level'] );
    }

    public function test_auth_level_private_for_return_false(): void {
        $route = $this->format_with_callback( '__return_false' );
        $this->assertSame( 'private', $route['endpoints'][0]['auth_level'] );
    }

    public function test_auth_level_authenticated_for_closure(): void {
        $route = $this->format_with_callback( static fn () => current_user_can( 'edit_posts' ) );
        $this->assertSame( 'authenticated', $route['endpoints'][0]['auth_level'] );
    }

    public function test_auth_level_authenticated_for_array_callback(): void {
        $route = $this->format_with_callback( [ new \stdClass(), 'some_method' ] );
        $this->assertSame( 'authenticated', $route['endpoints'][0]['auth_level'] );
    }

    public function test_auth_level_custom_for_unknown_string_callback(): void {
        $route = $this->format_with_callback( 'my_custom_permission_check' );
        $this->assertSame( 'custom', $route['endpoints'][0]['auth_level'] );
    }

    public function test_auth_level_none_when_no_callback(): void {
        $route = RouteFormatter::format_route(
            '/wp/v2/posts',
            [ [ 'methods' => [ 'GET' => true ] ] ],
            [ 'wp/v2' ]
        );
        $this->assertSame( 'none', $route['endpoints'][0]['auth_level'] );
    }

    // -------------------------------------------------------------------------
    // Permission label
    // -------------------------------------------------------------------------

    public function test_permission_label_is_public_for_return_true(): void {
        $route = $this->format_with_callback( '__return_true' );
        $this->assertSame( 'Public', $route['endpoints'][0]['permission_label'] );
    }

    public function test_permission_label_is_blocked_for_return_false(): void {
        $route = $this->format_with_callback( '__return_false' );
        $this->assertSame( 'Blocked', $route['endpoints'][0]['permission_label'] );
    }

    public function test_permission_label_is_closure_for_anonymous_function(): void {
        $route = $this->format_with_callback( static fn () => true );
        $this->assertSame( 'Closure', $route['endpoints'][0]['permission_label'] );
    }

    // -------------------------------------------------------------------------
    // Namespace detection
    // -------------------------------------------------------------------------

    public function test_detects_namespace_from_path_prefix(): void {
        $route = RouteFormatter::format_route(
            '/wp/v2/posts',
            [ $this->make_endpoint( '__return_true' ) ],
            [ 'wp/v2', 'wp' ]
        );
        $this->assertSame( 'wp/v2', $route['namespace'] );
    }

    public function test_falls_back_to_core_namespace_when_unmatched(): void {
        $route = RouteFormatter::format_route(
            '/',
            [ $this->make_endpoint( '__return_true' ) ],
            [ 'wp/v2' ]
        );
        $this->assertSame( 'core', $route['namespace'] );
    }

    // -------------------------------------------------------------------------
    // Args normalisation
    // -------------------------------------------------------------------------

    public function test_args_are_normalised_with_correct_fields(): void {
        $route = RouteFormatter::format_route(
            '/wp/v2/posts',
            [
                $this->make_endpoint( '__return_true', [
                    'page' => [
                        'type'        => 'integer',
                        'default'     => 1,
                        'description' => 'Current page.',
                        'required'    => false,
                    ],
                ] ),
            ],
            [ 'wp/v2' ]
        );

        $args = $route['endpoints'][0]['args'];
        $this->assertCount( 1, $args );
        $this->assertSame( 'page', $args[0]['name'] );
        $this->assertSame( 'integer', $args[0]['type'] );
        $this->assertSame( 1, $args[0]['default'] );
        $this->assertFalse( $args[0]['required'] );
        $this->assertSame( 'Current page.', $args[0]['description'] );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function format_with_callback( $callback ): array {
        return RouteFormatter::format_route(
            '/wp/v2/posts',
            [ $this->make_endpoint( $callback ) ],
            [ 'wp/v2' ]
        );
    }

    private function make_endpoint( $permission_callback, array $args = [] ): array {
        return [
            'methods'             => [ 'GET' => true ],
            'args'                => $args,
            'permission_callback' => $permission_callback,
        ];
    }
}
