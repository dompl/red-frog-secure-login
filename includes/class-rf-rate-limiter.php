<?php
/**
 * RF_Rate_Limiter class.
 *
 * Provides rate limiting for 2FA verification attempts
 * to prevent brute-force attacks on TOTP codes. Tracks
 * attempts per pending session and per IP address using
 * WordPress transients.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RF_Rate_Limiter
 *
 * Manages pending 2FA sessions with token-based tracking,
 * enforces per-session attempt limits (3 strikes), and
 * per-IP hourly limits (10 attempts) to mitigate brute-force
 * attacks against TOTP codes.
 *
 * @since 1.0.0
 */
class RF_Rate_Limiter {

	/**
	 * Maximum verification attempts per pending session.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const MAX_SESSION_ATTEMPTS = 3;

	/**
	 * Maximum verification attempts per IP address per hour.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const MAX_IP_ATTEMPTS_PER_HOUR = 10;

	/**
	 * IP rate limit window in seconds (1 hour).
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const IP_WINDOW_SECONDS = 3600;

	/**
	 * Pending session TTL in seconds (5 minutes).
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const SESSION_TTL = 300;

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
	 * Check if a pending session has attempts remaining.
	 *
	 * Retrieves the session transient and checks whether the
	 * attempt count is below the maximum. If exhausted, the
	 * session transient is deleted to prevent further use.
	 *
	 * @since  1.0.0
	 * @param  string $token The pending session token.
	 * @return array {
	 *     @type bool $allowed   Whether further attempts are allowed.
	 *     @type int  $remaining Number of attempts remaining.
	 * }
	 */
	public static function check_session( $token ) {
		$session = get_transient( '_rf_2fa_pending_' . $token );

		if ( false === $session || ! is_array( $session ) ) {
			return array(
				'allowed'   => false,
				'remaining' => 0,
			);
		}

		$attempts  = isset( $session['attempts'] ) ? (int) $session['attempts'] : 0;
		$remaining = self::MAX_SESSION_ATTEMPTS - $attempts;

		if ( $remaining <= 0 ) {
			// Session exhausted — delete it.
			delete_transient( '_rf_2fa_pending_' . $token );

			return array(
				'allowed'   => false,
				'remaining' => 0,
			);
		}

		return array(
			'allowed'   => true,
			'remaining' => $remaining,
		);
	}

	/**
	 * Increment the attempt count for a pending session.
	 *
	 * Updates the session transient with an incremented attempt
	 * count and resets the TTL to SESSION_TTL seconds.
	 *
	 * @since 1.0.0
	 * @param string $token The pending session token.
	 */
	public static function increment_session( $token ) {
		$session = get_transient( '_rf_2fa_pending_' . $token );

		if ( false === $session || ! is_array( $session ) ) {
			return;
		}

		$session['attempts'] = isset( $session['attempts'] ) ? (int) $session['attempts'] + 1 : 1;

		set_transient( '_rf_2fa_pending_' . $token, $session, self::SESSION_TTL );
	}

	/**
	 * Check if an IP address has attempts remaining in the hourly window.
	 *
	 * @since  1.0.0
	 * @return array {
	 *     @type bool $allowed   Whether further attempts are allowed.
	 *     @type int  $remaining Number of attempts remaining.
	 * }
	 */
	public static function check_ip() {
		$ip_hash  = self::get_ip_hash();
		$attempts = (int) get_transient( '_rf_rate_limit_' . $ip_hash );
		$remaining = self::MAX_IP_ATTEMPTS_PER_HOUR - $attempts;

		if ( $remaining <= 0 ) {
			return array(
				'allowed'   => false,
				'remaining' => 0,
			);
		}

		return array(
			'allowed'   => true,
			'remaining' => $remaining,
		);
	}

	/**
	 * Increment the attempt count for the current IP address.
	 *
	 * Creates or updates the IP rate limit transient with
	 * IP_WINDOW_SECONDS TTL.
	 *
	 * @since 1.0.0
	 */
	public static function increment_ip() {
		$ip_hash  = self::get_ip_hash();
		$attempts = (int) get_transient( '_rf_rate_limit_' . $ip_hash );

		set_transient( '_rf_rate_limit_' . $ip_hash, $attempts + 1, self::IP_WINDOW_SECONDS );
	}

	/**
	 * Create a new pending 2FA session for a user.
	 *
	 * Generates a unique token, stores session data (user ID,
	 * client IP, attempt count) in a transient with SESSION_TTL,
	 * and returns the token for use in the 2FA verification step.
	 *
	 * @since  1.0.0
	 * @param  int $user_id WordPress user ID.
	 * @return string Session token (32 alphanumeric characters).
	 */
	public static function create_pending_session( $user_id ) {
		$token = wp_generate_password( 32, false );

		$session = array(
			'user_id'  => (int) $user_id,
			'ip'       => self::get_client_ip(),
			'attempts' => 0,
		);

		set_transient( '_rf_2fa_pending_' . $token, $session, self::SESSION_TTL );

		return $token;
	}

	/**
	 * Retrieve a pending 2FA session.
	 *
	 * Fetches the session transient and validates that the current
	 * client IP matches the IP stored at session creation. This
	 * prevents session token theft from a different IP.
	 *
	 * @since  1.0.0
	 * @param  string $token The pending session token.
	 * @return array|false Session data array on success, false if
	 *                     not found or IP mismatch.
	 */
	public static function get_pending_session( $token ) {
		$session = get_transient( '_rf_2fa_pending_' . $token );

		if ( false === $session || ! is_array( $session ) ) {
			return false;
		}

		// Validate that the request comes from the same IP.
		$current_ip = self::get_client_ip();

		if ( ! isset( $session['ip'] ) || $session['ip'] !== $current_ip ) {
			return false;
		}

		return $session;
	}

	/**
	 * Delete a pending 2FA session.
	 *
	 * Removes the session transient, typically called after
	 * successful 2FA verification or session expiry.
	 *
	 * @since 1.0.0
	 * @param string $token The pending session token.
	 */
	public static function delete_pending_session( $token ) {
		delete_transient( '_rf_2fa_pending_' . $token );
	}

	/**
	 * Get the client's IP address.
	 *
	 * Checks HTTP_X_FORWARDED_FOR first (takes the first IP in a
	 * comma-separated list for proxied requests), falls back to
	 * REMOTE_ADDR. All values are sanitised.
	 *
	 * @since  1.0.0
	 * @return string Client IP address.
	 */
	private static function get_client_ip() {
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip  = trim( $ips[0] );

			if ( false !== filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$remote_addr = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );

			if ( false !== filter_var( $remote_addr, FILTER_VALIDATE_IP ) ) {
				return $remote_addr;
			}
		}

		return '0.0.0.0';
	}

	/**
	 * Get a hashed version of the client IP for use as a transient key.
	 *
	 * Hashing prevents overly long or malformed IPs from breaking
	 * transient key naming and adds a layer of obfuscation.
	 *
	 * @since  1.0.0
	 * @return string MD5 hash of the client IP address.
	 */
	private static function get_ip_hash() {
		return md5( self::get_client_ip() );
	}
}
