<?php
/**
 * RF_Ajax_Handler class.
 *
 * Registers and handles all AJAX endpoints for the plugin,
 * including login authentication, 2FA verification, 2FA setup,
 * and admin user management.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RF_Ajax_Handler
 *
 * Manages AJAX actions for login 2FA verification, user
 * profile 2FA setup/disable, and admin 2FA reset.
 *
 * @since 1.0.0
 */
class RF_Ajax_Handler {

	/**
	 * Constructor.
	 *
	 * Registers all AJAX action hooks for authenticated and
	 * unauthenticated users as appropriate.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Login (both logged-in and not-logged-in users).
		add_action( 'wp_ajax_nopriv_rf_login', array( $this, 'handle_login' ) );
		add_action( 'wp_ajax_rf_login', array( $this, 'handle_login' ) );

		// 2FA verification (both logged-in and not-logged-in users).
		add_action( 'wp_ajax_nopriv_rf_verify_2fa', array( $this, 'handle_verify_2fa' ) );
		add_action( 'wp_ajax_rf_verify_2fa', array( $this, 'handle_verify_2fa' ) );

		// 2FA setup during forced setup at login (not-logged-in users).
		add_action( 'wp_ajax_nopriv_rf_setup_2fa', array( $this, 'handle_setup_2fa' ) );

		// Disable 2FA (logged-in users on profile page).
		add_action( 'wp_ajax_rf_disable_2fa', array( $this, 'handle_disable_2fa' ) );

		// Admin reset user 2FA.
		add_action( 'wp_ajax_rf_reset_user_2fa', array( $this, 'handle_reset_user_2fa' ) );

		// Admin get users list for settings page.
		add_action( 'wp_ajax_rf_get_users', array( $this, 'handle_get_users' ) );
	}

	/**
	 * Handle the AJAX login request.
	 *
	 * Authenticates credentials via wp_authenticate(), then checks
	 * whether the user requires 2FA verification or forced 2FA setup
	 * before completing the login.
	 *
	 * @since 1.0.0
	 * @return void Sends JSON response and terminates.
	 */
	public function handle_login() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'rf_login_nonce', 'rf_login_nonce_field', false ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Security check failed.', 'rf-secure-login' ),
				)
			);
		}

		// Retrieve and sanitise credentials.
		$log = isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : '';
		$pwd = isset( $_POST['pwd'] ) ? wp_unslash( $_POST['pwd'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Password must not be sanitised.

		if ( empty( $log ) || empty( $pwd ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Username and password are required.', 'rf-secure-login' ),
				)
			);
		}

		// Authenticate.
		$user = wp_authenticate( $log, $pwd );

		if ( is_wp_error( $user ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => $user->get_error_message(),
				)
			);
		}

		$remember = isset( $_POST['rememberme'] );

		// Check if user has 2FA enabled.
		if ( RF_Two_Factor::is_enabled_for_user( $user->ID ) ) {
			$token = RF_Rate_Limiter::create_pending_session( $user->ID );

			wp_send_json(
				array(
					'status' => '2fa_required',
					'token'  => $token,
				)
			);
		}

		// Check if 2FA is enforced for this user's role but not yet set up.
		if ( RF_Two_Factor::is_enforced_for_user( $user->ID ) && ! RF_Two_Factor::is_enabled_for_user( $user->ID ) ) {
			$token  = RF_Rate_Limiter::create_pending_session( $user->ID );
			$secret = RF_Two_Factor::generate_secret();

			// Store the temp secret in the pending session transient.
			$session = get_transient( '_rf_2fa_pending_' . $token );

			if ( is_array( $session ) ) {
				$session['secret'] = $secret;
				set_transient( '_rf_2fa_pending_' . $token, $session, RF_Rate_Limiter::SESSION_TTL );
			}

			// Generate QR code URL.
			$otpauth_uri = RF_Two_Factor::get_otpauth_uri( $secret, $user->user_email );
			$qr_url      = RF_QRCode::get_image_url( $otpauth_uri );

			// Format secret for display (groups of 4 characters separated by spaces).
			$secret_display = trim( chunk_split( $secret, 4, ' ' ) );

			wp_send_json(
				array(
					'status'         => '2fa_setup_required',
					'token'          => $token,
					'qr_url'         => $qr_url,
					'secret'         => $secret,
					'secret_display' => $secret_display,
				)
			);
		}

		// No 2FA required — complete login.
		wp_set_auth_cookie( $user->ID, $remember );
		wp_set_current_user( $user->ID );

		$redirect = ! empty( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : admin_url();

		wp_send_json(
			array(
				'status'   => 'success',
				'redirect' => $redirect,
			)
		);
	}

	/**
	 * Handle the AJAX 2FA verification request.
	 *
	 * Validates a TOTP code or backup code against the user's
	 * stored secret. Enforces per-session and per-IP rate limits.
	 *
	 * @since 1.0.0
	 * @return void Sends JSON response and terminates.
	 */
	public function handle_verify_2fa() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'rf_login_nonce', 'rf_login_nonce_field', false ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Security check failed.', 'rf-secure-login' ),
				)
			);
		}

		// Check IP rate limit.
		$ip_check = RF_Rate_Limiter::check_ip();

		if ( ! $ip_check['allowed'] ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Too many attempts. Please try again later.', 'rf-secure-login' ),
				)
			);
		}

		// Retrieve and validate session token.
		$token   = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$session = RF_Rate_Limiter::get_pending_session( $token );

		if ( false === $session ) {
			wp_send_json(
				array(
					'status'  => 'expired',
					'message' => __( 'Session expired.', 'rf-secure-login' ),
				)
			);
		}

		// Check session rate limit.
		$session_check = RF_Rate_Limiter::check_session( $token );

		if ( ! $session_check['allowed'] ) {
			wp_send_json(
				array(
					'status'  => 'expired',
					'message' => __( 'Too many failed attempts.', 'rf-secure-login' ),
				)
			);
		}

		$user_id = (int) $session['user_id'];
		$code    = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
		$type    = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'totp';

		// Validate code based on type.
		if ( 'backup' === $type ) {
			$valid = RF_Backup_Codes::validate( $user_id, $code );
		} else {
			$valid = RF_Two_Factor::validate_user_code( $user_id, $code );
		}

		if ( $valid ) {
			// Success — clean up session and log user in.
			RF_Rate_Limiter::delete_pending_session( $token );
			wp_set_auth_cookie( $user_id, false );
			wp_set_current_user( $user_id );

			$redirect = ! empty( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : admin_url();

			wp_send_json(
				array(
					'status'   => 'success',
					'redirect' => $redirect,
				)
			);
		}

		// Invalid code — increment counters.
		RF_Rate_Limiter::increment_session( $token );
		RF_Rate_Limiter::increment_ip();

		// Re-check session to get updated remaining attempts.
		$session_recheck = RF_Rate_Limiter::check_session( $token );

		if ( $session_recheck['remaining'] <= 0 ) {
			RF_Rate_Limiter::delete_pending_session( $token );

			wp_send_json(
				array(
					'status'  => 'expired',
					'message' => __( 'Too many failed attempts.', 'rf-secure-login' ),
				)
			);
		}

		wp_send_json(
			array(
				'status'    => 'error',
				'message'   => __( 'Invalid code.', 'rf-secure-login' ),
				'remaining' => $session_recheck['remaining'],
			)
		);
	}

	/**
	 * Handle the AJAX 2FA setup request during forced setup at login.
	 *
	 * Validates the TOTP code against the temporary secret stored in
	 * the pending session, enables 2FA for the user, generates backup
	 * codes, and completes the login.
	 *
	 * @since 1.0.0
	 * @return void Sends JSON response and terminates.
	 */
	public function handle_setup_2fa() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'rf_login_nonce', 'rf_login_nonce_field', false ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Security check failed.', 'rf-secure-login' ),
				)
			);
		}

		// Retrieve and validate session token.
		$token   = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$session = RF_Rate_Limiter::get_pending_session( $token );

		if ( false === $session ) {
			wp_send_json(
				array(
					'status'  => 'expired',
					'message' => __( 'Session expired.', 'rf-secure-login' ),
				)
			);
		}

		$user_id = (int) $session['user_id'];
		$secret  = isset( $_POST['secret'] ) ? sanitize_text_field( wp_unslash( $_POST['secret'] ) ) : '';
		$code    = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';

		if ( empty( $secret ) || empty( $code ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Secret and verification code are required.', 'rf-secure-login' ),
				)
			);
		}

		// Validate the TOTP code against the provided secret.
		if ( ! RF_Two_Factor::validate_code( $secret, $code ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Invalid verification code. Please try again.', 'rf-secure-login' ),
				)
			);
		}

		// Enable 2FA for the user.
		$enabled = RF_Two_Factor::enable_for_user( $user_id, $secret );

		if ( ! $enabled ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Failed to enable two-factor authentication. Please try again.', 'rf-secure-login' ),
				)
			);
		}

		// Generate backup codes.
		$backup_codes = RF_Backup_Codes::generate( $user_id );

		// Clean up session and log user in.
		RF_Rate_Limiter::delete_pending_session( $token );
		wp_set_auth_cookie( $user_id, false );
		wp_set_current_user( $user_id );

		wp_send_json(
			array(
				'status'       => 'success',
				'backup_codes' => $backup_codes,
				'redirect'     => admin_url(),
			)
		);
	}

	/**
	 * Handle the AJAX request to disable 2FA for the current user.
	 *
	 * Requires a valid TOTP code to confirm the user has access
	 * to their authenticator before disabling 2FA.
	 *
	 * @since 1.0.0
	 * @return void Sends JSON response and terminates.
	 */
	public function handle_disable_2fa() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'rf_disable_2fa_nonce', 'nonce', false ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Security check failed.', 'rf-secure-login' ),
				)
			);
		}

		// Must be logged in.
		$user_id = get_current_user_id();

		if ( 0 === $user_id ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'You must be logged in to perform this action.', 'rf-secure-login' ),
				)
			);
		}

		$code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';

		if ( empty( $code ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Please enter your authenticator code.', 'rf-secure-login' ),
				)
			);
		}

		// Validate TOTP code.
		if ( ! RF_Two_Factor::validate_user_code( $user_id, $code ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Invalid authenticator code.', 'rf-secure-login' ),
				)
			);
		}

		// Disable 2FA and remove backup codes.
		RF_Two_Factor::disable_for_user( $user_id );
		RF_Backup_Codes::remove( $user_id );

		wp_send_json(
			array(
				'status'  => 'success',
				'message' => __( 'Two-factor authentication has been disabled.', 'rf-secure-login' ),
			)
		);
	}

	/**
	 * Handle the admin AJAX request to reset a user's 2FA.
	 *
	 * Only administrators (manage_options capability) can reset
	 * another user's 2FA settings.
	 *
	 * @since 1.0.0
	 * @return void Sends JSON response and terminates.
	 */
	public function handle_reset_user_2fa() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'rf_admin_nonce', 'nonce', false ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Security check failed.', 'rf-secure-login' ),
				)
			);
		}

		// Check admin capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'You do not have permission to perform this action.', 'rf-secure-login' ),
				)
			);
		}

		$target_user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		if ( 0 === $target_user_id ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Invalid user.', 'rf-secure-login' ),
				)
			);
		}

		// Disable 2FA and remove backup codes for the target user.
		RF_Two_Factor::disable_for_user( $target_user_id );
		RF_Backup_Codes::remove( $target_user_id );

		wp_send_json(
			array(
				'status'  => 'success',
				'message' => __( 'Two-factor authentication has been reset for this user.', 'rf-secure-login' ),
			)
		);
	}

	/**
	 * Handle the admin AJAX request to get the users list.
	 *
	 * Returns user data including 2FA status for display in
	 * the admin settings page user table.
	 *
	 * @since 1.0.0
	 * @return void Sends JSON response and terminates.
	 */
	public function handle_get_users() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'rf_admin_nonce', 'nonce', false ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'Security check failed.', 'rf-secure-login' ),
				)
			);
		}

		// Check admin capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => __( 'You do not have permission to perform this action.', 'rf-secure-login' ),
				)
			);
		}

		// Build query args.
		$args = array(
			'orderby' => 'display_name',
			'order'   => 'ASC',
		);

		// Optional search term.
		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		if ( ! empty( $search ) ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}

		$users      = get_users( $args );
		$users_data = array();

		foreach ( $users as $user ) {
			$roles = array();

			if ( ! empty( $user->roles ) && is_array( $user->roles ) ) {
				$roles = array_values( $user->roles );
			}

			$tfa_enabled   = RF_Two_Factor::is_enabled_for_user( $user->ID );
			$setup_date    = get_user_meta( $user->ID, '_rf_2fa_setup_date', true );
			$backup_remain = RF_Backup_Codes::remaining_count( $user->ID );

			$users_data[] = array(
				'id'                     => $user->ID,
				'username'               => $user->user_login,
				'email'                  => $user->user_email,
				'roles'                  => $roles,
				'2fa_enabled'            => $tfa_enabled,
				'2fa_setup_date'         => $tfa_enabled ? $setup_date : '',
				'backup_codes_remaining' => $backup_remain,
			);
		}

		wp_send_json(
			array(
				'status' => 'success',
				'users'  => $users_data,
			)
		);
	}
}
