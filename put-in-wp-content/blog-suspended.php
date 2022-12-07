<?php
/*
Plugin Name: Anti-Splog (Spam-Benachrichtigung und Splog-Überprüfungsformular)
Plugin URI: https://n3rds.work
Description: Das ultimative Plugin und der ultimative Dienst zum Stoppen und Beseitigen von Splogs in WordPress Multisite und BuddyPress
Author: WMS N@W
Author URI: https://n3rds.work
*/
if ( file_exists( WP_PLUGIN_DIR . '/anti-splog/includes/blog-suspended-template.php' ) ) {
	require_once( WP_PLUGIN_DIR . '/anti-splog/includes/blog-suspended-template.php' );
} else {
	wp_die( __( 'Diese Website wurde archiviert oder gesperrt.' ), '', array( 'response' => 410 ) );
}