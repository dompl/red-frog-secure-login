<?php
/**
 * Red Frog Secure Login — Uninstall.
 *
 * Removes all plugin data from the database when the plugin
 * is deleted via the WordPress admin.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 */

// Prevent direct access — must be called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove all rf_* plugin options.
 */
$rf_options = array(
	'rf_secure_login_enabled',
	'rf_particle_count',
	'rf_accent_colour',
	'rf_login_logo',
	'rf_recaptcha_enabled',
	'rf_recaptcha_site_key',
	'rf_recaptcha_secret_key',
	'rf_recaptcha_threshold',
	'rf_2fa_enforced_roles',
);

foreach ( $rf_options as $option ) {
	delete_option( $option );
}

/**
 * Remove all _rf_* user meta for all users.
 */
global $wpdb;

$rf_user_meta_keys = array(
	'_rf_2fa_secret',
	'_rf_2fa_enabled',
	'_rf_2fa_setup_date',
	'_rf_2fa_backup_codes',
);

foreach ( $rf_user_meta_keys as $meta_key ) {
	$wpdb->delete(
		$wpdb->usermeta,
		array( 'meta_key' => $meta_key ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		array( '%s' )
	);
}

/**
 * Remove all _rf_* transients from the database.
 *
 * Deletes both the transient value and its timeout entry.
 */
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient__rf_' ) . '%',
		$wpdb->esc_like( '_transient_timeout__rf_' ) . '%'
	)
);
