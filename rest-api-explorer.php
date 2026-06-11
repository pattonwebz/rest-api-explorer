<?php
/**
 * Plugin Name: REST API Explorer
 * Description: Browse, understand, and test all REST endpoints registered on your site.
 * Version:     1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author:      wordpress-dev
 * License:     GPL-2.0-or-later
 * Text Domain: rest-api-explorer
 */

defined( 'ABSPATH' ) || exit;

define( 'RAE_VERSION', '1.0.0' );
define( 'RAE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RAE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( function ( string $class ): void {
	$prefix = 'RestApiExplorer\\';
	if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
		return;
	}
	$file = RAE_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
	if ( file_exists( $file ) ) {
		require $file;
	}
} );

add_action( 'plugins_loaded', function (): void {
	new RestApiExplorer\Admin\AdminPage();
} );

add_action( 'rest_api_init', function (): void {
	RestApiExplorer\Rest\TestController::register();
	RestApiExplorer\Rest\FavoritesController::register();
	RestApiExplorer\Rest\ExportController::register();
} );

register_activation_hook( __FILE__, [ RestApiExplorer\Admin\RouteDiscovery::class, 'clear_cache' ] );
register_deactivation_hook( __FILE__, [ RestApiExplorer\Admin\RouteDiscovery::class, 'clear_cache' ] );
