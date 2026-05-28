<?php
/**
 * Uninstall Script.
 * Fired when the plugin is deleted.
 * Removes all settings and meta data if the user chooses to.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Cleanup options
$mydybox_options = [
	'mydybox_license_key',
	'mydybox_enabled_modules',
	'mydybox_settings',
];

foreach ( $mydybox_options as $mydybox_option ) {
	delete_option( $mydybox_option );
}

// Cleanup transients
delete_transient( 'mydybox_activated' );
