<?php

namespace RestApiExplorer\Rest;

class FavoritesController {

	private const NAMESPACE = 'rest-api-explorer/v1';
	private const META_KEY  = 'rest_api_explorer_favorites';

	public static function register(): void {
		register_rest_route(
			self::NAMESPACE,
			'/favorites',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ self::class, 'index' ],
					'permission_callback' => static fn () => is_user_logged_in(),
				],
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ self::class, 'create' ],
					'permission_callback' => static fn () => is_user_logged_in(),
					'args'                => [
						'id'        => [ 'type' => 'string', 'required' => true ],
						'name'      => [ 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
						'folder'    => [ 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ],
						'method'    => [ 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
						'path'      => [ 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
						'params'    => [ 'type' => 'object', 'default' => [] ],
						'body'      => [ 'type' => [ 'object', 'null' ], 'default' => null ],
						'authType'  => [ 'type' => 'string', 'default' => 'none', 'sanitize_callback' => 'sanitize_text_field' ],
						'timestamp' => [ 'type' => 'integer', 'required' => true ],
					],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/favorites/(?P<id>[a-zA-Z0-9_-]+)',
			[
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ self::class, 'update' ],
					'permission_callback' => static fn () => is_user_logged_in(),
					'args'                => [
						'name'   => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
						'folder' => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
					],
				],
				[
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => [ self::class, 'delete' ],
					'permission_callback' => static fn () => is_user_logged_in(),
				],
			]
		);
	}

	public static function index(): \WP_REST_Response {
		return new \WP_REST_Response( self::get_favorites(), 200 );
	}

	public static function create( \WP_REST_Request $request ): \WP_REST_Response {
		$favorites = self::get_favorites();

		$item = [
			'id'        => sanitize_text_field( $request->get_param( 'id' ) ),
			'name'      => $request->get_param( 'name' ),
			'folder'    => $request->get_param( 'folder' ) ?: '',
			'method'    => strtoupper( sanitize_text_field( $request->get_param( 'method' ) ) ),
			'path'      => sanitize_text_field( $request->get_param( 'path' ) ),
			'params'    => (array) ( $request->get_param( 'params' ) ?? [] ),
			'body'      => $request->get_param( 'body' ),
			'authType'  => sanitize_text_field( $request->get_param( 'authType' ) ?? 'none' ),
			'timestamp' => (int) $request->get_param( 'timestamp' ),
		];

		// Replace if same id exists (upsert)
		$favorites = array_values( array_filter( $favorites, static fn ( $f ) => $f['id'] !== $item['id'] ) );
		$favorites[] = $item;

		self::save_favorites( $favorites );

		return new \WP_REST_Response( $item, 201 );
	}

	public static function update( \WP_REST_Request $request ): \WP_REST_Response {
		$id        = $request->get_param( 'id' );
		$favorites = self::get_favorites();
		$found     = false;

		$favorites = array_map(
			function ( array $f ) use ( $id, $request, &$found ): array {
				if ( $f['id'] !== $id ) {
					return $f;
				}
				$found = true;
				if ( null !== $request->get_param( 'name' ) ) {
					$f['name'] = sanitize_text_field( $request->get_param( 'name' ) );
				}
				if ( null !== $request->get_param( 'folder' ) ) {
					$f['folder'] = sanitize_text_field( $request->get_param( 'folder' ) );
				}
				return $f;
			},
			$favorites
		);

		if ( ! $found ) {
			return new \WP_REST_Response( [ 'message' => 'Favorite not found' ], 404 );
		}

		self::save_favorites( $favorites );

		$updated = array_values( array_filter( $favorites, static fn ( $f ) => $f['id'] === $id ) )[0] ?? null;
		return new \WP_REST_Response( $updated, 200 );
	}

	public static function delete( \WP_REST_Request $request ): \WP_REST_Response {
		$id        = $request->get_param( 'id' );
		$favorites = self::get_favorites();
		self::save_favorites( array_values( array_filter( $favorites, static fn ( $f ) => $f['id'] !== $id ) ) );
		return new \WP_REST_Response( null, 204 );
	}

	private static function get_favorites(): array {
		$raw = get_user_meta( get_current_user_id(), self::META_KEY, true );
		return is_array( $raw ) ? $raw : [];
	}

	private static function save_favorites( array $favorites ): void {
		update_user_meta( get_current_user_id(), self::META_KEY, $favorites );
	}
}
