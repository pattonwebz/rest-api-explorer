<?php

namespace RestApiExplorer\Admin;

class AdminPage {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_rae_clear_cache', [ $this, 'ajax_clear_cache' ] );
	}

	public function register_menu(): void {
		add_submenu_page(
			'tools.php',
			__( 'REST API Explorer', 'rest-api-explorer' ),
			__( 'REST API Explorer', 'rest-api-explorer' ),
			'manage_options',
			'rest-api-explorer',
			[ $this, 'render_page' ]
		);
	}

	public function enqueue_scripts( string $hook ): void {
		if ( 'tools_page_rest-api-explorer' !== $hook ) {
			return;
		}

		$asset_file = RAE_PLUGIN_DIR . 'build/index.asset.php';
		$version    = RAE_VERSION;
		$deps       = [ 'wp-element', 'wp-i18n' ];

		if ( file_exists( $asset_file ) ) {
			$asset   = require $asset_file;
			$version = $asset['version'] ?? RAE_VERSION;
			$deps    = array_unique( array_merge( $deps, $asset['dependencies'] ?? [] ) );
		}

		wp_enqueue_script(
			'rest-api-explorer',
			RAE_PLUGIN_URL . 'build/index.js',
			$deps,
			$version,
			true
		);

		wp_enqueue_style(
			'rest-api-explorer',
			RAE_PLUGIN_URL . 'assets/css/explorer.css',
			[],
			$version
		);

		wp_localize_script(
			'rest-api-explorer',
			'restApiExplorer',
			[
				'routes'    => RouteDiscovery::get_routes(),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'homeUrl'   => home_url( '/wp-json' ),
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'clearNonce' => wp_create_nonce( 'rae_clear_cache' ),
			]
		);
	}

	public function render_page(): void {
		echo '<div class="wrap"><div id="rest-api-explorer-app"></div></div>';
	}

	public function ajax_clear_cache(): void {
		check_ajax_referer( 'rae_clear_cache' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1, 403 );
		}
		RouteDiscovery::clear_cache();
		wp_send_json_success( [ 'routes' => RouteDiscovery::get_routes() ] );
	}
}
