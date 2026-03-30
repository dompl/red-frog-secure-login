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
	 * Google reCAPTCHA site key.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $site_key;

	/**
	 * Google reCAPTCHA secret key.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $secret_key;

	/**
	 * Score threshold for passing verification.
	 *
	 * Scores range from 0.0 (likely bot) to 1.0 (likely human).
	 * Requests scoring below this threshold are rejected.
	 *
	 * @since 1.0.0
	 * @var float
	 */
	private $threshold;

	/**
	 * Constructor.
	 *
	 * Loads reCAPTCHA settings from the database and registers
	 * WordPress hooks when the feature is properly configured.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->site_key   = get_option( 'rf_recaptcha_site_key', '' );
		$this->secret_key = get_option( 'rf_recaptcha_secret_key', '' );
		$this->threshold  = (float) get_option( 'rf_recaptcha_threshold', 0.5 );

		// Only add hooks if reCAPTCHA is enabled and keys are configured.
		if ( $this->is_configured() ) {
			add_action( 'login_enqueue_scripts', array( $this, 'enqueue_script' ) );
		}
	}

	/**
	 * Check whether reCAPTCHA is enabled and both keys are set.
	 *
	 * This is an instance method that checks the current object's
	 * loaded configuration. For a static check without instantiation,
	 * use RF_Recaptcha::is_enabled().
	 *
	 * @since  1.0.0
	 * @return bool True if reCAPTCHA is enabled with valid keys.
	 */
	public function is_configured() {
		return 'yes' === get_option( 'rf_recaptcha_enabled', 'no' )
			&& '' !== $this->site_key
			&& '' !== $this->secret_key;
	}

	/**
	 * Enqueue the Google reCAPTCHA v3 script on the login page.
	 *
	 * The script is loaded with the site key as a render parameter,
	 * which initialises reCAPTCHA in invisible mode.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_script() {
		wp_enqueue_script(
			'rf-recaptcha',
			'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $this->site_key ),
			array(),
			null,
			true
		);
	}

	/**
	 * Verify a reCAPTCHA token with Google's API.
	 *
	 * Sends the token to Google's siteverify endpoint and checks
	 * both the success flag and the score against the configured
	 * threshold.
	 *
	 * When reCAPTCHA is not configured, this method returns true
	 * so that login is not blocked when the feature is disabled.
	 *
	 * @since  1.0.0
	 * @param  string $token The reCAPTCHA response token from the client.
	 * @return bool True if the token is valid and score meets threshold,
	 *              or if reCAPTCHA is not configured (pass-through).
	 */
	public function verify_token( $token ) {
		// Pass-through when reCAPTCHA is not configured.
		if ( ! $this->is_configured() ) {
			return true;
		}

		// Empty token always fails.
		if ( empty( $token ) ) {
			return false;
		}

		$token = sanitize_text_field( $token );

		// Build the verification request.
		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'timeout' => 10,
				'body'    => array(
					'secret'   => $this->secret_key,
					'response' => $token,
					'remoteip' => $this->get_client_ip(),
				),
			)
		);

		// Network or HTTP error — fail closed.
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $response_code ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		// Malformed response — fail closed.
		if ( ! is_object( $data ) ) {
			return false;
		}

		// Google must confirm success AND the score must meet the threshold.
		if ( true === $data->success && isset( $data->score ) && (float) $data->score >= $this->threshold ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if reCAPTCHA is enabled and configured.
	 *
	 * Static helper for use by other classes (e.g. RF_Ajax_Handler)
	 * to decide whether reCAPTCHA verification should be performed,
	 * without needing to instantiate this class.
	 *
	 * @since  1.0.0
	 * @return bool True if reCAPTCHA is enabled with valid keys.
	 */
	public static function is_enabled() {
		return 'yes' === get_option( 'rf_recaptcha_enabled', 'no' )
			&& '' !== get_option( 'rf_recaptcha_site_key', '' )
			&& '' !== get_option( 'rf_recaptcha_secret_key', '' );
	}

	/**
	 * Get the client's IP address.
	 *
	 * Checks common headers for proxied requests before falling
	 * back to REMOTE_ADDR. The result is sanitised to prevent
	 * header injection.
	 *
	 * @since  1.0.0
	 * @return string The client IP address.
	 */
	private function get_client_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// HTTP_X_FORWARDED_FOR can contain multiple IPs; take the first.
			$forwarded = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			$ips       = explode( ',', $forwarded );
			$ip        = trim( $ips[0] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		// Validate the IP address format.
		$validated = filter_var( $ip, FILTER_VALIDATE_IP );

		return false !== $validated ? $validated : '';
	}
}
