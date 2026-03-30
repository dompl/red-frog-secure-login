<?php
/**
 * RF_Backup_Codes class.
 *
 * Handles generation, storage, and validation of one-time-use
 * backup codes for emergency 2FA recovery.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RF_Backup_Codes
 *
 * Provides backup code functionality for users who lose access
 * to their authenticator app. Codes are single-use and stored
 * as hashed values in user meta.
 *
 * @since 1.0.0
 */
class RF_Backup_Codes {

	/**
	 * Number of backup codes to generate.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const CODE_COUNT = 10;

	/**
	 * Length of each backup code.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const CODE_LENGTH = 8;

	/**
	 * User meta key for stored backup codes.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const META_KEY = '_rf_backup_codes';

	/**
	 * Constructor.
	 *
	 * This is a utility class with no hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Utility class — no hooks to register.
	}

	/**
	 * Generate a set of backup codes for a user.
	 *
	 * @since  1.0.0
	 * @param  int $user_id WordPress user ID.
	 * @return array Plain-text backup codes (to display to user once).
	 */
	public static function generate( $user_id ) {
		// Stub — will be implemented in Phase 5.
		return array();
	}

	/**
	 * Validate a backup code for a user.
	 *
	 * If valid, the code is consumed (removed from stored codes).
	 *
	 * @since  1.0.0
	 * @param  int    $user_id WordPress user ID.
	 * @param  string $code    Backup code to validate.
	 * @return bool True if the code is valid and was consumed.
	 */
	public static function validate( $user_id, $code ) {
		// Stub — will be implemented in Phase 5.
		return false;
	}

	/**
	 * Get the count of remaining backup codes for a user.
	 *
	 * @since  1.0.0
	 * @param  int $user_id WordPress user ID.
	 * @return int Number of remaining backup codes.
	 */
	public static function get_remaining_count( $user_id ) {
		$codes = get_user_meta( $user_id, self::META_KEY, true );

		if ( ! is_array( $codes ) ) {
			return 0;
		}

		return count( $codes );
	}
}
