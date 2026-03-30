<?php
/**
 * RF_Two_Factor class.
 *
 * Handles TOTP (Time-based One-Time Password) two-factor
 * authentication logic, including secret generation, code
 * validation, and user meta management.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RF_Two_Factor
 *
 * Implements RFC 6238 TOTP directly in PHP without external
 * dependencies. Manages user 2FA secrets, generates QR code
 * URIs, and validates one-time codes.
 *
 * @since 1.0.0
 */
class RF_Two_Factor {

	/**
	 * Length of the TOTP code.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const CODE_LENGTH = 6;

	/**
	 * TOTP time step in seconds.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const TIME_STEP = 30;

	/**
	 * Number of time steps to allow for clock skew (+-1).
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const TIME_WINDOW = 1;

	/**
	 * User meta key for the 2FA secret.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const META_SECRET = '_rf_2fa_secret';

	/**
	 * User meta key for 2FA enabled status.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const META_ENABLED = '_rf_2fa_enabled';

	/**
	 * Constructor.
	 *
	 * This is a utility class with no hooks. Methods are called
	 * directly by other classes (Ajax handler, user profile, etc.).
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Utility class — no hooks to register.
	}

	/**
	 * Generate a random Base32-encoded secret.
	 *
	 * @since  1.0.0
	 * @param  int $length Number of characters in the secret (default 16).
	 * @return string Base32-encoded secret.
	 */
	public static function generate_secret( $length = 16 ) {
		// Stub — will be implemented in Phase 5.
		return '';
	}

	/**
	 * Generate a TOTP code for a given secret and time.
	 *
	 * @since  1.0.0
	 * @param  string   $secret Base32-encoded secret.
	 * @param  int|null $time   Unix timestamp (defaults to current time).
	 * @return string 6-digit TOTP code.
	 */
	public static function generate_code( $secret, $time = null ) {
		// Stub — will be implemented in Phase 5.
		return '';
	}

	/**
	 * Validate a TOTP code against a user's secret.
	 *
	 * Checks the code against the current time step and
	 * adjacent steps to allow for clock skew.
	 *
	 * @since  1.0.0
	 * @param  string $secret Base32-encoded secret.
	 * @param  string $code   6-digit code to validate.
	 * @return bool True if the code is valid.
	 */
	public static function validate_code( $secret, $code ) {
		// Stub — will be implemented in Phase 5.
		return false;
	}

	/**
	 * Check if a user has 2FA enabled.
	 *
	 * @since  1.0.0
	 * @param  int $user_id WordPress user ID.
	 * @return bool True if 2FA is enabled for this user.
	 */
	public static function is_enabled_for_user( $user_id ) {
		return 'yes' === get_user_meta( $user_id, self::META_ENABLED, true );
	}

	/**
	 * Get the 2FA secret for a user.
	 *
	 * @since  1.0.0
	 * @param  int $user_id WordPress user ID.
	 * @return string The stored secret, or empty string if not set.
	 */
	public static function get_user_secret( $user_id ) {
		return (string) get_user_meta( $user_id, self::META_SECRET, true );
	}

	/**
	 * Generate an otpauth:// URI for QR code generation.
	 *
	 * @since  1.0.0
	 * @param  string $secret   Base32-encoded secret.
	 * @param  string $username User's login name.
	 * @param  string $issuer   Issuer name (defaults to site name).
	 * @return string otpauth:// URI.
	 */
	public static function get_otpauth_uri( $secret, $username, $issuer = '' ) {
		if ( empty( $issuer ) ) {
			$issuer = get_bloginfo( 'name' );
		}

		return sprintf(
			'otpauth://totp/%s:%s?secret=%s&issuer=%s&digits=%d&period=%d',
			rawurlencode( $issuer ),
			rawurlencode( $username ),
			$secret,
			rawurlencode( $issuer ),
			self::CODE_LENGTH,
			self::TIME_STEP
		);
	}
}
