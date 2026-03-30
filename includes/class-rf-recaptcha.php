<?php
/**
 * RF_Recaptcha class.
 *
 * Integrates Google reCAPTCHA v3 with the login form for
 * bot protection.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RF_Recaptcha
 *
 * Handles reCAPTCHA v3 script loading, token injection into
 * the login form, and server-side token verification.
 *
 * @since 1.0.0
 */
class RF_Recaptcha {

	/**
	 * Constructor.
	 *
	 * reCAPTCHA hooks will be registered in a later phase
	 * when the integration is implemented.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// reCAPTCHA hooks will be registered in Phase 9.
	}

	/**
	 * Check if reCAPTCHA is enabled and configured.
	 *
	 * @since  1.0.0
	 * @return bool True if reCAPTCHA is enabled with valid keys.
	 */
	public static function is_enabled() {
		if ( 'yes' !== get_option( 'rf_recaptcha_enabled', 'no' ) ) {
			return false;
		}

		$site_key   = get_option( 'rf_recaptcha_site_key', '' );
		$secret_key = get_option( 'rf_recaptcha_secret_key', '' );

		return ! empty( $site_key ) && ! empty( $secret_key );
	}

	/**
	 * Verify a reCAPTCHA token with Google's API.
	 *
	 * @since  1.0.0
	 * @param  string $token The reCAPTCHA response token.
	 * @return bool True if the token is valid and score meets threshold.
	 */
	public static function verify_token( $token ) {
		// Stub — will be implemented in Phase 9.
		return true;
	}
}
