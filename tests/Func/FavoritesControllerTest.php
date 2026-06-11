<?php
namespace RestApiExplorer\Tests\Func;
use RestApiExplorer\Rest\FavoritesController;
use WP_REST_Request;
use WP_UnitTestCase;
class FavoritesControllerTest extends WP_UnitTestCase {
    private int $user_id;
    protected function setUp(): void {
        parent::setUp();
        $this->user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
        wp_set_current_user( $this->user_id );
        delete_user_meta( $this->user_id, 'rest_api_explorer_favorites' );
    }
    public function test_create_and_index_returns_saved_favorite(): void {
        $request = new WP_REST_Request( 'POST', '/rest-api-explorer/v1/favorites' );
        $request->set_param( 'id', 'fav-1' );
        $request->set_param( 'name', 'My Endpoint' );
        $request->set_param( 'folder', 'Core' );
        $request->set_param( 'method', 'get' );
        $request->set_param( 'path', '/wp/v2/posts' );
        $request->set_param( 'params', [ 'page' => 1 ] );
        $request->set_param( 'authType', 'none' );
        $request->set_param( 'timestamp', 1710000000 );
        $create = FavoritesController::create( $request );
        $index  = FavoritesController::index();
        $this->assertSame( 201, $create->get_status() );
        $this->assertCount( 1, $index->get_data() );
        $this->assertSame( 'GET', $index->get_data()[0]['method'] );
    }
    public function test_create_sanitizes_method_id_path_and_defaults_folder(): void {
        $request = new WP_REST_Request( 'POST', '/rest-api-explorer/v1/favorites' );
        $request->set_param( 'id', ' fav<script>-id ' );
        $request->set_param( 'name', '<b>Name</b>' );
        $request->set_param( 'folder', '' );
        $request->set_param( 'method', 'pAtCh' );
        $request->set_param( 'path', '/wp/v2/<script>posts</script>' );
        $request->set_param( 'timestamp', 99 );
        $create = FavoritesController::create( $request );
        $saved  = $create->get_data();
        $this->assertSame( 'fav-id', $saved['id'] );
        $this->assertSame( 'PATCH', $saved['method'] );
        $this->assertSame( '/wp/v2/', $saved['path'] );
        $this->assertSame( '', $saved['folder'] );
    }
    public function test_create_upserts_existing_id_instead_of_duplicating(): void {
        $first = new WP_REST_Request( 'POST', '/rest-api-explorer/v1/favorites' );
        $first->set_param( 'id', 'fav-dup' );
        $first->set_param( 'name', 'Old Name' );
        $first->set_param( 'folder', 'One' );
        $first->set_param( 'method', 'GET' );
        $first->set_param( 'path', '/wp/v2/posts' );
        $first->set_param( 'timestamp', 1 );
        $second = new WP_REST_Request( 'POST', '/rest-api-explorer/v1/favorites' );
        $second->set_param( 'id', 'fav-dup' );
        $second->set_param( 'name', 'New Name' );
        $second->set_param( 'folder', 'Two' );
        $second->set_param( 'method', 'POST' );
        $second->set_param( 'path', '/wp/v2/posts' );
        $second->set_param( 'timestamp', 2 );
        FavoritesController::create( $first );
        FavoritesController::create( $second );
        $all = FavoritesController::index()->get_data();
        $this->assertCount( 1, $all );
        $this->assertSame( 'New Name', $all[0]['name'] );
        $this->assertSame( 'POST', $all[0]['method'] );
    }
    public function test_create_casts_timestamp_and_defaults_optional_payload_fields(): void {
        $request = new WP_REST_Request( 'POST', '/rest-api-explorer/v1/favorites' );
        $request->set_param( 'id', 'fav-min' );
        $request->set_param( 'name', 'Minimal' );
        $request->set_param( 'method', 'GET' );
        $request->set_param( 'path', '/wp/v2/posts' );
        $request->set_param( 'timestamp', '170' );
        $created = FavoritesController::create( $request )->get_data();
        $this->assertSame( 170, $created['timestamp'] );
        $this->assertSame( [], $created['params'] );
        $this->assertNull( $created['body'] );
        $this->assertSame( 'none', $created['authType'] );
    }
    public function test_update_returns_404_when_favorite_not_found(): void {
        $request = new WP_REST_Request( 'PUT', '/rest-api-explorer/v1/favorites/missing' );
        $request->set_param( 'id', 'missing' );
        $response = FavoritesController::update( $request );
        $this->assertSame( 404, $response->get_status() );
        $this->assertSame( 'Favorite not found', $response->get_data()['message'] );
    }
    public function test_update_changes_only_provided_fields(): void {
        $create = new WP_REST_Request( 'POST', '/rest-api-explorer/v1/favorites' );
        $create->set_param( 'id', 'fav-edit' );
        $create->set_param( 'name', 'Before' );
        $create->set_param( 'folder', 'Folder A' );
        $create->set_param( 'method', 'GET' );
        $create->set_param( 'path', '/wp/v2/posts' );
        $create->set_param( 'timestamp', 123 );
        FavoritesController::create( $create );
        $update = new WP_REST_Request( 'PUT', '/rest-api-explorer/v1/favorites/fav-edit' );
        $update->set_param( 'id', 'fav-edit' );
        $update->set_param( 'name', 'After' );
        $updated = FavoritesController::update( $update );
        $this->assertSame( 200, $updated->get_status() );
        $this->assertSame( 'After', $updated->get_data()['name'] );
        $this->assertSame( 'Folder A', $updated->get_data()['folder'] );
    }
    public function test_delete_nonexistent_item_returns_204_without_errors(): void {
        $delete = new WP_REST_Request( 'DELETE', '/rest-api-explorer/v1/favorites/missing' );
        $delete->set_param( 'id', 'missing' );
        $deleted = FavoritesController::delete( $delete );
        $this->assertSame( 204, $deleted->get_status() );
        $this->assertCount( 0, FavoritesController::index()->get_data() );
    }
    public function test_update_and_delete_modify_saved_favorites(): void {
        $create = new WP_REST_Request( 'POST', '/rest-api-explorer/v1/favorites' );
        $create->set_param( 'id', 'fav-edit' );
        $create->set_param( 'name', 'Before' );
        $create->set_param( 'folder', 'Folder A' );
        $create->set_param( 'method', 'GET' );
        $create->set_param( 'path', '/wp/v2/posts' );
        $create->set_param( 'timestamp', 123 );
        FavoritesController::create( $create );
        $update = new WP_REST_Request( 'PUT', '/rest-api-explorer/v1/favorites/fav-edit' );
        $update->set_param( 'id', 'fav-edit' );
        $update->set_param( 'name', 'After' );
        $update->set_param( 'folder', 'Folder B' );
        $updated = FavoritesController::update( $update );
        $this->assertSame( 200, $updated->get_status() );
        $this->assertSame( 'After', $updated->get_data()['name'] );
        $this->assertSame( 'Folder B', $updated->get_data()['folder'] );
        $delete = new WP_REST_Request( 'DELETE', '/rest-api-explorer/v1/favorites/fav-edit' );
        $delete->set_param( 'id', 'fav-edit' );
        $deleted = FavoritesController::delete( $delete );
        $this->assertSame( 204, $deleted->get_status() );
        $this->assertCount( 0, FavoritesController::index()->get_data() );
    }
}
