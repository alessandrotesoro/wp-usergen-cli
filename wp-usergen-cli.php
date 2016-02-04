<?php
/*
Plugin Name: wp-usergen-cli
Plugin URI:  https://alessandrotesoro.me
Description: A WP-CLI addon command that generates random users. Useful for testing purposes.
Version: 1.0.0
Author:      Alessandro Tesoro
Author URI:  http://alessandrotesoro.me
License:     GPLv2+
*/

/**
 * Load extension if WP CLI exists.
 *
 * @return void
 * @since 1.0.0
 */
function wp_usergen_cli_load() {

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		include dirname(__FILE__) . '/cli.php';
	}

}
add_action( 'plugins_loaded', 'wp_usergen_cli_load' );
