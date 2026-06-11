<?php
/**
 * PHPUnit bootstrap — loads the WordPress test suite.
 *
 * Expects WP_TESTS_DIR to point to a wordpress-develop/tests/phpunit clone,
 * or the directory created by wp-cli/scaffold-command's install-wp-tests.sh.
 *
 * Quick setup:
 *   bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: '/tmp/wordpress-tests-lib';

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    echo "ERROR: WP test suite not found at {$_tests_dir}.\n";
    echo "Set WP_TESTS_DIR or run bin/install-wp-tests.sh first.\n";
    exit( 1 );
}

// Load WP test functions (registers plugins, themes, etc.)
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin before the test suite initialises WordPress,
 * so that hooks registered at file-include time are captured.
 */
tests_add_filter( 'muplugins_loaded', function (): void {
    require_once dirname( __DIR__, 2 ) . '/rest-api-explorer.php';
} );

// Bootstrap WordPress itself
require_once $_tests_dir . '/includes/bootstrap.php';
