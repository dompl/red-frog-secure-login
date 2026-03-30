<?php
/**
 * RF_Rate_Limiter class.
 *
 * Provides rate limiting for 2FA verification attempts
 * to prevent brute-force attacks on TOTP codes.
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
 * Tracks and enforces attempt limits per pending session
 * and per IP address using WordPress transients.
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
	 * Check if a session has exceeded the maximum attempts.
	 *
	 * @since  1.0.0
	 * @param  string $session_hash The pending session hash.
	 * @return bool True if the session is rate-limited.
	 */
	public static function is_session_limited( $session_hash ) {
		// Stub — will be implemented in Phase 5.
		return false;
	}

	/**
	 * Check if an IP address has exceeded the hourly limit.
	 *
	 * @since  1.0.0
	 * @param  string $ip_address The client IP address.
	 * @return bool True if the IP is rate-limited.
	 */
	public static function is_ip_limited( $ip_address ) {
		// Stub — will be implemented in Phase 5.
		return false;
	}

	/**
	 * Record a verification attempt for a session.
	 *
	 * @since 1.0.0
	 * @param string $session_hash The pending session hash.
	 */
	public static function record_session_attempt( $session_hash ) {
		// Stub — will be implemented in Phase 5.
	}

	/**
	 * Record a verification attempt for an IP address.
	 *
	 * @since 1.0.0
	 * @param string $ip_address The client IP address.
	 */
	public static function record_ip_attempt( $ip_address ) {
		// Stub — will be implemented in Phase 5.
	}

	/**
	 * Get the client's IP address.
	 *
	 * @since  1.0.0
	 * @return string Client IP address.
	 */
	public static function get_client_ip() {
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			return trim( $ips[0] );
		}

		if ( ! empty( $_SERVER['HTTP_X_REAL_IP'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
		}

		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	}
}
