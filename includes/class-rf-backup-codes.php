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
 * as hashed values in user meta. Plaintext codes are returned
 * only once at generation time for the user to record.
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
	 * User meta key for stored (hashed) backup codes.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const META_KEY = '_rf_2fa_backup_codes';

	/**
	 * Character set for backup code generation.
	 *
	 * Excludes ambiguous characters: 0, o, 1, l, i.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const CHAR_SET = 'abcdefghjkmnpqrstuvwxyz23456789';

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
	 * Creates CODE_COUNT random codes, hashes each with wp_hash(),
	 * stores the hashed versions in user meta, and returns the
	 * plaintext codes for the user to save. Any previously stored
	 * codes are replaced.
	 *
	 * @since  1.0.0
	 * @param  int $user_id WordPress user ID.
	 * @return array Plaintext backup codes (to display to user once).
	 */
	public static function generate( $user_id ) {
		$plaintext_codes = array();
		$hashed_codes    = array();

		for ( $i = 0; $i < self::CODE_COUNT; $i++ ) {
			$code              = self::random_code();
			$plaintext_codes[] = $code;
			$hashed_codes[]    = wp_hash( $code );
		}

		update_user_meta( $user_id, self::META_KEY, $hashed_codes );

		return $plaintext_codes;
	}

	/**
	 * Validate a backup code for a user.
	 *
	 * Strips non-alphanumeric characters, hashes the submitted code,
	 * and compares against stored hashes using hash_equals() for
	 * timing-safe comparison. If valid, the code is consumed
	 * (removed from the stored set) so it cannot be reused.
	 *
	 * @since  1.0.0
	 * @param  int    $user_id WordPress user ID.
	 * @param  string $code    Backup code to validate.
	 * @return bool True if the code is valid and was consumed.
	 */
	public static function validate( $user_id, $code ) {
		// Strip non-alphanumeric characters and lowercase.
		$code = strtolower( preg_replace( '/[^a-zA-Z0-9]/', '', $code ) );

		if ( empty( $code ) ) {
			return false;
		}

		$stored_hashes = get_user_meta( $user_id, self::META_KEY, true );

		if ( ! is_array( $stored_hashes ) || empty( $stored_hashes ) ) {
			return false;
		}

		$submitted_hash = wp_hash( $code );

		foreach ( $stored_hashes as $index => $stored_hash ) {
			if ( hash_equals( $stored_hash, $submitted_hash ) ) {
				// Consume the code: remove it from stored hashes.
				unset( $stored_hashes[ $index ] );
				$stored_hashes = array_values( $stored_hashes );
				update_user_meta( $user_id, self::META_KEY, $stored_hashes );

				return true;
			}
		}

		return false;
	}

	/**
	 * Get the count of remaining backup codes for a user.
	 *
	 * @since  1.0.0
	 * @param  int $user_id WordPress user ID.
	 * @return int Number of remaining backup codes.
	 */
	public static function remaining_count( $user_id ) {
		$codes = get_user_meta( $user_id, self::META_KEY, true );

		if ( ! is_array( $codes ) ) {
			return 0;
		}

		return count( $codes );
	}

	/**
	 * Remove all backup codes for a user.
	 *
	 * @since 1.0.0
	 * @param int $user_id WordPress user ID.
	 */
	public static function remove( $user_id ) {
		delete_user_meta( $user_id, self::META_KEY );
	}

	/**
	 * Generate a single random backup code.
	 *
	 * Uses wp_rand() to select characters from an unambiguous
	 * character set, producing codes that are easy to read and
	 * transcribe manually.
	 *
	 * @since  1.0.0
	 * @return string Random backup code of CODE_LENGTH characters.
	 */
	private static function random_code() {
		$code      = '';
		$chars     = self::CHAR_SET;
		$chars_len = strlen( $chars );

		for ( $i = 0; $i < self::CODE_LENGTH; $i++ ) {
			$code .= $chars[ wp_rand( 0, $chars_len - 1 ) ];
		}

		return $code;
	}
}
