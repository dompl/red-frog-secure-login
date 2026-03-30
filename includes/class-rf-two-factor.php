<?php
/**
 * RF_Two_Factor class.
 *
 * Handles TOTP (Time-based One-Time Password) two-factor
 * authentication logic, including secret generation, code
 * validation, and user meta management.
 *
 * Implements RFC 6238 directly in PHP without external
 * dependencies.
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
	 * Base32 character set per RFC 4648.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

	/**
	 * TOTP time step in seconds.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const TIME_STEP = 30;

	/**
	 * Length of the TOTP code.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const CODE_LENGTH = 6;

	/**
	 * Number of time steps to allow for clock skew (+-1).
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const SKEW_TOLERANCE = 1;

	/**
	 * Cipher method for secret encryption at rest.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const CIPHER_METHOD = 'aes-256-cbc';

	/**
	 * User meta key for the encrypted 2FA secret.
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
	 * User meta key for 2FA setup date.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const META_SETUP_DATE = '_rf_2fa_setup_date';

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
	 * Uses wp_rand() to select characters from the Base32 alphabet,
	 * producing a cryptographically suitable secret for TOTP.
	 *
	 * @since  1.0.0
	 * @param  int $length Number of characters in the secret (default 16).
	 * @return string Base32-encoded secret.
	 */
	public static function generate_secret( $length = 16 ) {
		$secret     = '';
		$chars_len  = strlen( self::BASE32_CHARS );

		for ( $i = 0; $i < $length; $i++ ) {
			$secret .= self::BASE32_CHARS[ wp_rand( 0, $chars_len - 1 ) ];
		}

		return $secret;
	}

	/**
	 * Decode a Base32-encoded string to raw binary.
	 *
	 * Processes the Base32 input 5 bits at a time, accumulating
	 * into 8-bit output bytes per RFC 4648.
	 *
	 * @since  1.0.0
	 * @param  string $base32 Base32-encoded string.
	 * @return string Raw binary data.
	 */
	private static function base32_decode( $base32 ) {
		// Uppercase and strip padding.
		$base32 = strtoupper( $base32 );
		$base32 = rtrim( $base32, '=' );

		if ( '' === $base32 ) {
			return '';
		}

		// Build lookup table.
		$lookup = array();
		$chars  = self::BASE32_CHARS;
		for ( $i = 0, $len = strlen( $chars ); $i < $len; $i++ ) {
			$lookup[ $chars[ $i ] ] = $i;
		}

		$buffer    = 0;
		$bits_left = 0;
		$output    = '';

		for ( $i = 0, $len = strlen( $base32 ); $i < $len; $i++ ) {
			$char = $base32[ $i ];

			if ( ! isset( $lookup[ $char ] ) ) {
				continue; // Skip invalid characters.
			}

			$buffer    = ( $buffer << 5 ) | $lookup[ $char ];
			$bits_left += 5;

			if ( $bits_left >= 8 ) {
				$bits_left -= 8;
				$output    .= chr( ( $buffer >> $bits_left ) & 0xFF );
			}
		}

		return $output;
	}

	/**
	 * Generate a TOTP code for a given secret and time.
	 *
	 * Implements the TOTP algorithm per RFC 6238:
	 * 1. Divide timestamp by time step to get counter.
	 * 2. Pack counter as 8-byte big-endian.
	 * 3. HMAC-SHA1 with decoded secret as key.
	 * 4. Dynamic truncation to extract 31-bit integer.
	 * 5. Modulo 10^digits and zero-pad.
	 *
	 * @since  1.0.0
	 * @param  string   $secret    Base32-encoded secret.
	 * @param  int|null $timestamp Unix timestamp (defaults to current time).
	 * @return string 6-digit TOTP code.
	 */
	public static function generate_code( $secret, $timestamp = null ) {
		if ( null === $timestamp ) {
			$timestamp = time();
		}

		// Step 1: Calculate time step counter.
		$time_step = floor( $timestamp / self::TIME_STEP );

		// Step 2: Pack as 8-byte big-endian (two 32-bit unsigned ints).
		$time_bytes = pack( 'N*', 0 ) . pack( 'N*', $time_step );

		// Step 3: Decode the Base32 secret to binary key.
		$key = self::base32_decode( $secret );

		// Step 4: HMAC-SHA1.
		$hash = hash_hmac( 'sha1', $time_bytes, $key, true );

		// Step 5: Dynamic truncation.
		$offset = ord( $hash[19] ) & 0x0F;

		$code_int = (
			( ( ord( $hash[ $offset ] ) & 0x7F ) << 24 ) |
			( ( ord( $hash[ $offset + 1 ] ) & 0xFF ) << 16 ) |
			( ( ord( $hash[ $offset + 2 ] ) & 0xFF ) << 8 ) |
			( ord( $hash[ $offset + 3 ] ) & 0xFF )
		);

		// Step 6: Modulo and zero-pad.
		$modulo = pow( 10, self::CODE_LENGTH );
		$code   = $code_int % $modulo;

		return str_pad( (string) $code, self::CODE_LENGTH, '0', STR_PAD_LEFT );
	}

	/**
	 * Validate a TOTP code against a secret.
	 *
	 * Checks the code against the current time step and adjacent
	 * steps (±SKEW_TOLERANCE) to allow for clock drift. Uses
	 * hash_equals() for timing-safe comparison.
	 *
	 * @since  1.0.0
	 * @param  string $secret Base32-encoded secret.
	 * @param  string $code   Code to validate.
	 * @return bool True if the code is valid within the time window.
	 */
	public static function validate_code( $secret, $code ) {
		// Sanitise: strip non-digits and verify length.
		$code = preg_replace( '/[^0-9]/', '', $code );

		if ( self::CODE_LENGTH !== strlen( $code ) ) {
			return false;
		}

		$now = time();

		// Check current time step ± skew tolerance.
		for ( $offset = -self::SKEW_TOLERANCE; $offset <= self::SKEW_TOLERANCE; $offset++ ) {
			$check_time   = $now + ( $offset * self::TIME_STEP );
			$expected     = self::generate_code( $secret, $check_time );

			if ( hash_equals( $expected, $code ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Validate a TOTP code for a specific user.
	 *
	 * Retrieves and decrypts the user's stored secret, then
	 * delegates to validate_code().
	 *
	 * @since  1.0.0
	 * @param  int    $user_id WordPress user ID.
	 * @param  string $code    Code to validate.
	 * @return bool True if the code is valid.
	 */
	public static function validate_user_code( $user_id, $code ) {
		$secret = self::get_user_secret( $user_id );

		if ( empty( $secret ) ) {
			return false;
		}

		return self::validate_code( $secret, $code );
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
	 * Check if 2FA is enforced for a user based on their roles.
	 *
	 * Compares the user's roles against the list of roles that
	 * require 2FA, stored in the rf_2fa_enforced_roles option.
	 *
	 * @since  1.0.0
	 * @param  int $user_id WordPress user ID.
	 * @return bool True if any of the user's roles are in the enforced list.
	 */
	public static function is_enforced_for_user( $user_id ) {
		$enforced_roles = get_option( 'rf_2fa_enforced_roles', array() );

		if ( empty( $enforced_roles ) || ! is_array( $enforced_roles ) ) {
			return false;
		}

		$user = get_userdata( $user_id );

		if ( ! $user || empty( $user->roles ) ) {
			return false;
		}

		$intersection = array_intersect( $user->roles, $enforced_roles );

		return ! empty( $intersection );
	}

	/**
	 * Enable 2FA for a user.
	 *
	 * Encrypts the secret before storing, sets the enabled flag,
	 * and records the setup date.
	 *
	 * @since 1.0.0
	 * @param int    $user_id WordPress user ID.
	 * @param string $secret  Base32-encoded TOTP secret (plaintext).
	 * @return bool True on success, false on failure.
	 */
	public static function enable_for_user( $user_id, $secret ) {
		$encrypted = self::encrypt_secret( $secret );

		if ( false === $encrypted ) {
			return false;
		}

		update_user_meta( $user_id, self::META_SECRET, $encrypted );
		update_user_meta( $user_id, self::META_ENABLED, 'yes' );
		update_user_meta( $user_id, self::META_SETUP_DATE, current_time( 'mysql' ) );

		return true;
	}

	/**
	 * Disable 2FA for a user.
	 *
	 * Removes all 2FA-related user meta including the secret,
	 * enabled flag, and setup date.
	 *
	 * @since 1.0.0
	 * @param int $user_id WordPress user ID.
	 */
	public static function disable_for_user( $user_id ) {
		delete_user_meta( $user_id, self::META_SECRET );
		delete_user_meta( $user_id, self::META_ENABLED );
		delete_user_meta( $user_id, self::META_SETUP_DATE );
	}

	/**
	 * Get the decrypted 2FA secret for a user.
	 *
	 * Retrieves the encrypted secret from user meta and decrypts it.
	 * Returns an empty string if no secret is stored or decryption fails.
	 *
	 * @since  1.0.0
	 * @param  int $user_id WordPress user ID.
	 * @return string Decrypted Base32-encoded secret, or empty string.
	 */
	public static function get_user_secret( $user_id ) {
		$encrypted = get_user_meta( $user_id, self::META_SECRET, true );

		if ( empty( $encrypted ) ) {
			return '';
		}

		$decrypted = self::decrypt_secret( $encrypted );

		return ( false === $decrypted ) ? '' : $decrypted;
	}

	/**
	 * Generate an otpauth:// URI for QR code generation.
	 *
	 * Produces a URI compatible with Google Authenticator, Authy,
	 * and other TOTP authenticator apps.
	 *
	 * @since  1.0.0
	 * @param  string $secret Base32-encoded secret.
	 * @param  string $email  User's email address.
	 * @param  string $issuer Issuer name (defaults to site name).
	 * @return string otpauth:// URI.
	 */
	public static function get_otpauth_uri( $secret, $email, $issuer = '' ) {
		if ( empty( $issuer ) ) {
			$issuer = get_bloginfo( 'name' );
		}

		return sprintf(
			'otpauth://totp/%s:%s?secret=%s&issuer=%s&digits=%d&period=%d',
			rawurlencode( $issuer ),
			rawurlencode( $email ),
			$secret,
			rawurlencode( $issuer ),
			self::CODE_LENGTH,
			self::TIME_STEP
		);
	}

	/**
	 * Encrypt a TOTP secret for safe storage.
	 *
	 * Uses AES-256-CBC with a key derived from WordPress AUTH_KEY.
	 * The IV is prepended to the ciphertext before base64 encoding
	 * so it can be extracted during decryption.
	 *
	 * @since  1.0.0
	 * @param  string $secret Plaintext Base32-encoded secret.
	 * @return string|false Base64-encoded IV + ciphertext, or false on failure.
	 */
	private static function encrypt_secret( $secret ) {
		if ( ! defined( 'AUTH_KEY' ) || '' === AUTH_KEY ) {
			return false;
		}

		$key    = hash( 'sha256', AUTH_KEY, true );
		$iv_len = openssl_cipher_iv_length( self::CIPHER_METHOD );

		if ( false === $iv_len ) {
			return false;
		}

		$iv = openssl_random_pseudo_bytes( $iv_len );

		if ( false === $iv ) {
			return false;
		}

		$ciphertext = openssl_encrypt( $secret, self::CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv );

		if ( false === $ciphertext ) {
			return false;
		}

		// Prepend IV to ciphertext and base64-encode the whole thing.
		return base64_encode( $iv . $ciphertext ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypt a stored TOTP secret.
	 *
	 * Reverses the encrypt_secret() process: base64-decode, extract
	 * the IV from the first bytes, then AES-256-CBC decrypt.
	 *
	 * @since  1.0.0
	 * @param  string $encrypted_data Base64-encoded IV + ciphertext.
	 * @return string|false Decrypted plaintext secret, or false on failure.
	 */
	private static function decrypt_secret( $encrypted_data ) {
		if ( ! defined( 'AUTH_KEY' ) || '' === AUTH_KEY ) {
			return false;
		}

		$key    = hash( 'sha256', AUTH_KEY, true );
		$iv_len = openssl_cipher_iv_length( self::CIPHER_METHOD );

		if ( false === $iv_len ) {
			return false;
		}

		$raw = base64_decode( $encrypted_data, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( false === $raw ) {
			return false;
		}

		// The raw data must be longer than the IV.
		if ( strlen( $raw ) <= $iv_len ) {
			return false;
		}

		$iv         = substr( $raw, 0, $iv_len );
		$ciphertext = substr( $raw, $iv_len );

		$decrypted = openssl_decrypt( $ciphertext, self::CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv );

		if ( false === $decrypted ) {
			return false;
		}

		return $decrypted;
	}
}
