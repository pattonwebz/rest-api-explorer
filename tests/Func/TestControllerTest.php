<?php
namespace RestApiExplorer\Tests\Func;
use RestApiExplorer\Rest\TestController;
use WP_Error;
use WP_Http_Cookie;
use WP_REST_Request;
use WP_UnitTestCase;
class TestControllerTest extends WP_UnitTestCase {
    public function test_build_request_auth_cookie_adds_nonce_and_cookie_objects(): void {
        $_COOKIE = [
            'wordpress_logged_in_test' => 'abc123',
            'sample_cookie'            => 'value',
        ];
        $result = $this->invoke_private( TestController::class, 'build_request_auth', [ [ 'type' => 'cookie' ], [ ' X-Custom ' => ' Value ' ] ] );
        $this->assertArrayHasKey( 'X-WP-Nonce', $result['headers'] );
        $this->assertArrayHasKey( 'X-Custom', $result['headers'] );
        $this->assertSame( 'Value', $result['headers']['X-Custom'] );
        $this->assertCount( 2, $result['cookies'] );
        $this->assertInstanceOf( WP_Http_Cookie::class, $result['cookies'][0] );
    }
    public function test_build_request_auth_basic_and_bearer_headers(): void {
        $basic  = $this->invoke_private( TestController::class, 'build_request_auth', [ [ 'type' => 'basic', 'username' => 'admin', 'password' => 'secret' ], [] ] );
        $bearer = $this->invoke_private( TestController::class, 'build_request_auth', [ [ 'type' => 'bearer', 'token' => 'abc-token' ], [] ] );
        $this->assertSame( 'Basic ' . base64_encode( 'admin:secret' ), $basic['headers']['Authorization'] );
        $this->assertSame( 'Bearer abc-token', $bearer['headers']['Authorization'] );
    }
    public function test_build_request_auth_unknown_mode_only_applies_sanitized_custom_headers(): void {
        $result = $this->invoke_private(
            TestController::class,
            'build_request_auth',
            [ [ 'type' => 'digest' ], [ ' <b>X-Token</b> ' => ' value <script>alert(1)</script> ' ] ]
        );
        $this->assertArrayNotHasKey( 'Authorization', $result['headers'] );
        $this->assertArrayHasKey( 'X-Token', $result['headers'] );
        $this->assertSame( 'value', $result['headers']['X-Token'] );
        $this->assertSame( [], $result['cookies'] );
    }
    public function test_describe_request_redacts_authorization_and_lists_cookie_names_only(): void {
        $args = [
            'headers' => [
                'Authorization' => 'Bearer should-not-leak',
                'X-Test'        => 'ok',
            ],
            'cookies' => [
                new WP_Http_Cookie( [ 'name' => 'wordpress_logged_in_x', 'value' => 'secret' ] ),
            ],
            'body'    => '{"k":"v"}',
        ];
        $described = $this->invoke_private( TestController::class, 'describe_request', [ 'POST', 'https://example.test/wp-json/wp/v2/posts', $args ] );
        $this->assertSame( '**redacted**', $described['headers']['Authorization'] );
        $this->assertSame( [ 'wordpress_logged_in_x' ], $described['cookies'] );
        $this->assertSame( '{"k":"v"}', $described['body'] );
    }
    public function test_describe_request_ignores_non_cookie_values(): void {
        $described = $this->invoke_private(
            TestController::class,
            'describe_request',
            [
                'GET',
                'https://example.test/wp-json/wp/v2/types',
                [
                    'headers' => [ 'Authorization' => 'Bearer secret' ],
                    'cookies' => [ new \stdClass(), 'raw-cookie' ],
                ],
            ]
        );
        $this->assertSame( '**redacted**', $described['headers']['Authorization'] );
        $this->assertSame( [], $described['cookies'] );
        $this->assertNull( $described['body'] );
    }
    public function test_handle_returns_success_response_for_json_payload_and_sets_content_type(): void {
        $request = new WP_REST_Request( 'POST', '/rest-api-explorer/v1/test-request' );
        $request->set_param( 'method', 'POST' );
        $request->set_param( 'path', '/wp/v2/posts' );
        $request->set_param( 'params', [ 'context' => 'view' ] );
        $request->set_param( 'body', [ 'title' => 'Hello' ] );
        $request->set_param( 'auth', [ 'type' => 'none' ] );
        $captured_args = null;
        add_filter(
            'pre_http_request',
            static function ( $preempt, $args ) use ( &$captured_args ) {
                $captured_args = $args;
                return [
                    'response' => [ 'code' => 201, 'message' => 'Created' ],
                    'headers'  => [ 'content-type' => 'application/json; charset=UTF-8' ],
                    'body'     => '{"id":55,"title":"Hello"}',
                ];
            },
            10,
            3
        );
        $response = TestController::handle( $request );
        remove_all_filters( 'pre_http_request' );
        $data = $response->get_data();
        $this->assertTrue( $data['success'] );
        $this->assertSame( 201, $data['status_code'] );
        $this->assertSame( 55, $data['body']['id'] );
        $this->assertSame( 'application/json; charset=UTF-8', $data['headers']['content-type'] );
        $this->assertSame( 'application/json', $captured_args['headers']['Content-Type'] );
    }
    public function test_handle_cookie_auth_forwards_nonce_and_cookie_objects(): void {
        $original_cookies = $_COOKIE;
        $_COOKIE          = [ 'rae_cookie' => 'cookie-value' ];
        $request = new WP_REST_Request( 'POST', '/rest-api-explorer/v1/test-request' );
        $request->set_param( 'method', 'GET' );
        $request->set_param( 'path', '/wp/v2/posts' );
        $request->set_param( 'auth', [ 'type' => 'cookie' ] );
        $request->set_param( 'headers', [ ' X-Trace ' => ' abc ' ] );
        $captured_args = null;
        add_filter(
            'pre_http_request',
            static function ( $preempt, $args ) use ( &$captured_args ) {
                $captured_args = $args;
                return [
                    'response' => [ 'code' => 200, 'message' => 'OK' ],
                    'headers'  => [ 'content-type' => 'text/plain' ],
                    'body'     => 'ok',
                ];
            },
            10,
            3
        );
        $response = TestController::handle( $request );
        remove_all_filters( 'pre_http_request' );
        $_COOKIE = $original_cookies;
        $data = $response->get_data();
        $this->assertTrue( $data['success'] );
        $this->assertArrayHasKey( 'X-WP-Nonce', $captured_args['headers'] );
        $this->assertArrayHasKey( 'X-Trace', $captured_args['headers'] );
        $this->assertCount( 1, $captured_args['cookies'] );
        $this->assertInstanceOf( WP_Http_Cookie::class, $captured_args['cookies'][0] );
        $this->assertSame( [ 'rae_cookie' ], $data['request_sent']['cookies'] );
    }
    public function test_handle_returns_error_payload_and_redacted_request_when_wp_error_occurs(): void {
        $request = new WP_REST_Request( 'POST', '/rest-api-explorer/v1/test-request' );
        $request->set_param( 'method', 'GET' );
        $request->set_param( 'path', '/wp/v2/users' );
        $request->set_param( 'params', [] );
        $request->set_param( 'body', [] );
        $request->set_param( 'auth', [ 'type' => 'basic', 'username' => 'user', 'password' => 'pass' ] );
        add_filter(
            'pre_http_request',
            static function () {
                return new WP_Error( 'http_request_failed', 'Simulated transport failure' );
            }
        );
        $response = TestController::handle( $request );
        remove_all_filters( 'pre_http_request' );
        $data = $response->get_data();
        $this->assertFalse( $data['success'] );
        $this->assertSame( 'Simulated transport failure', $data['error'] );
        $this->assertSame( '**redacted**', $data['request_sent']['headers']['Authorization'] );
    }
    private function invoke_private( string $class, string $method, array $args ) {
        $reflection = new \ReflectionMethod( $class, $method );
        $reflection->setAccessible( true );
        return $reflection->invokeArgs( null, $args );
    }
}
