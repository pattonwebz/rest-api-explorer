<?php
/**
 * WordPress plugin test bootstrap.
 *
 * Requires a WordPress test suite installed via tests/scripts/install-wp-tests.sh.
 * Set WP_TESTS_DIR if the suite is not in the default /tmp location.
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: '/tmp/wordpress-tests-lib';

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    echo "Could not find {$_tests_dir}/includes/functions.php; ";
    echo "have you run tests/scripts/install-wp-tests.sh ?\n";
    exit( 1 );
}

// Optional: configure polyfills path for PHPUnit cross-version compatibility.
$_polyfills_dir = getenv( 'WP_PHPUNIT_POLYFILLS_PATH' )
    ?: dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills';
if ( file_exists( $_polyfills_dir . '/phpunitpolyfills-autoload.php' ) ) {
    define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_polyfills_dir );
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Load the plugin under test before WordPress initialises.
 */
function _manually_load_plugin(): void {
    require_once dirname( __DIR__ ) . '/rest-api-explorer.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/** Test asset directory for any fixtures or stubs used by tests. */
define( 'RAE_TEST_ASSETS', __DIR__ . '/assets' );

require_once $_tests_dir . '/includes/bootstrap.php';
