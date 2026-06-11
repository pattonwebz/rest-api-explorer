<?php
namespace RestApiExplorer\Tests\Func;
use RestApiExplorer\Rest\ExportController;
use WP_REST_Request;
use WP_UnitTestCase;
class ExportControllerTest extends WP_UnitTestCase {
    public function test_handle_markdown_returns_expected_filename_and_string_content(): void {
        $request = new WP_REST_Request( 'GET', '/rest-api-explorer/v1/export' );
        $request->set_param( 'format', 'markdown' );
        $response = ExportController::handle( $request );
        $data     = $response->get_data();
        $this->assertSame( 200, $response->get_status() );
        $this->assertSame( 'api-docs.md', $data['filename'] );
        $this->assertIsString( $data['content'] );
        $this->assertStringContainsString( '# REST API Documentation', $data['content'] );
    }
    public function test_handle_postman_returns_expected_collection_shape(): void {
        $request = new WP_REST_Request( 'GET', '/rest-api-explorer/v1/export' );
        $request->set_param( 'format', 'postman' );
        $response = ExportController::handle( $request );
        $data     = $response->get_data();
        $this->assertSame( 200, $response->get_status() );
        $this->assertSame( 'api-collection.postman_collection.json', $data['filename'] );
        $this->assertIsArray( $data['content'] );
        $this->assertArrayHasKey( 'info', $data['content'] );
        $this->assertArrayHasKey( 'item', $data['content'] );
        $this->assertArrayHasKey( 'variable', $data['content'] );
    }
    public function test_group_by_namespace_falls_back_to_core_for_empty_namespace(): void {
        $grouped = $this->invoke_private(
            ExportController::class,
            'group_by_namespace',
            [
                [
                    [ 'namespace' => '', 'path' => '/x', 'endpoints' => [] ],
                    [ 'namespace' => 'wp/v2', 'path' => '/wp/v2/posts', 'endpoints' => [] ],
                ],
            ]
        );
        $this->assertArrayHasKey( 'core', $grouped );
        $this->assertArrayHasKey( 'wp/v2', $grouped );
        $this->assertSame( '/x', $grouped['core'][0]['path'] );
    }
    public function test_to_markdown_renders_args_table_and_curl_example(): void {
        $routes = [
            [
                'path'        => '/wp/v2/posts',
                'namespace'   => 'wp/v2',
                'description' => 'Posts endpoint',
                'endpoints'   => [
                    [
                        'methods'    => [ 'GET' ],
                        'auth_level' => 'public',
                        'args'       => [
                            [
                                'name'        => 'page',
                                'type'        => 'integer',
                                'required'    => false,
                                'default'     => 1,
                                'description' => 'Page number',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $markdown = $this->invoke_private( ExportController::class, 'to_markdown', [ $routes, 'https://example.test/wp-json' ] );
        $this->assertStringContainsString( '## Namespace: wp/v2', $markdown );
        $this->assertStringContainsString( '| `page` | integer | No | `1` | Page number |', $markdown );
        $this->assertStringContainsString( 'curl -s -X GET "https://example.test/wp-json/wp/v2/posts" \\', $markdown );
    }
    public function test_to_postman_adds_query_params_only_for_get_and_delete(): void {
        $routes = [
            [
                'path'        => '/wp/v2/posts',
                'namespace'   => 'wp/v2',
                'description' => 'Posts endpoint',
                'endpoints'   => [
                    [
                        'methods' => [ 'GET' ],
                        'args'    => [
                            [ 'name' => 'page', 'required' => false, 'default' => 2, 'description' => 'Page', 'type' => 'integer' ],
                        ],
                    ],
                    [
                        'methods' => [ 'POST' ],
                        'args'    => [
                            [ 'name' => 'title', 'required' => true, 'default' => null, 'description' => 'Title', 'type' => 'string' ],
                        ],
                    ],
                ],
            ],
        ];
        $postman = $this->invoke_private( ExportController::class, 'to_postman', [ $routes, 'https://example.test/wp-json' ] );
        $items   = $postman['item'][0]['item'];
        $this->assertCount( 2, $items );
        $this->assertSame( 'GET', $items[0]['request']['method'] );
        $this->assertCount( 1, $items[0]['request']['url']['query'] );
        $this->assertSame( 'POST', $items[1]['request']['method'] );
        $this->assertCount( 0, $items[1]['request']['url']['query'] );
    }
    private function invoke_private( string $class, string $method, array $args ) {
        $reflection = new \ReflectionMethod( $class, $method );
        $reflection->setAccessible( true );
        return $reflection->invokeArgs( null, $args );
    }
}
