<?php
$WP_TESTS_DIR = getenv('WP_TESTS_DIR');

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv('WP_TESTS_PHPUNIT_POLYFILLS_PATH');
if (false !== $_phpunit_polyfills_path) {
  define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path);
}

require_once getenv('HOME') . '/.composer/vendor/autoload.php';

// Give access to tests_add_filter() function.
require_once $WP_TESTS_DIR . '/includes/functions.php';

// /**
//  * Manually load the plugin being tested.
//  */
// function _manually_load_plugin() {
// 	require dirname( dirname( __FILE__ ) ) . '/starter-plugin.php';
// }

// tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $WP_TESTS_DIR . '/includes/bootstrap.php';
