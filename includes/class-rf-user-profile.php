<?php
/**
 * RF_User_Profile class.
 *
 * Adds a two-factor authentication setup section to the
 * WordPress user profile page, including QR code display,
 * verification, backup code management, and disable flow.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RF_User_Profile
 *
 * Renders 2FA setup/status UI on user profile pages and
 * handles AJAX actions for profile-based 2FA setup,
 * backup code regeneration, and 2FA disabling.
 *
 * @since 1.0.0
 */
class RF_User_Profile {

	/**
	 * Constructor.
	 *
	 * Registers profile display hooks and AJAX action handlers
	 * for 2FA management on the user profile page.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Render 2FA section on profile pages.
		add_action( 'show_user_profile', array( $this, 'render_2fa_section' ) );
		add_action( 'edit_user_profile', array( $this, 'render_2fa_section' ) );

		// Enqueue assets on profile pages.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// AJAX: Set up 2FA from profile page.
		add_action( 'wp_ajax_rf_setup_2fa_profile', array( $this, 'handle_setup_2fa_profile' ) );

		// AJAX: Regenerate backup codes.
		add_action( 'wp_ajax_rf_regenerate_backup_codes', array( $this, 'handle_regenerate_backup_codes' ) );
	}

	/**
	 * Enqueue scripts and styles on profile pages.
	 *
	 * Registers a small inline script handle for AJAX interactions
	 * and localises nonces and the AJAX URL.
	 *
	 * @since 1.0.0
	 * @param string $hook The current admin page hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'profile.php' !== $hook && 'user-edit.php' !== $hook ) {
			return;
		}

		// Register a minimal script handle for localisation.
		wp_register_script(
			'rf-user-profile',
			'',
			array(),
			RF_SECURE_LOGIN_VERSION,
			true
		);
		wp_enqueue_script( 'rf-user-profile' );

		wp_localize_script(
			'rf-user-profile',
			'rfProfile',
			array(
				'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
				'setupNonce'           => wp_create_nonce( 'rf_setup_2fa_nonce' ),
				'disableNonce'         => wp_create_nonce( 'rf_disable_2fa_nonce' ),
				'regenerateNonce'      => wp_create_nonce( 'rf_regenerate_backup_codes_nonce' ),
				'i18n'                 => array(
					'setupSuccess'      => __( 'Two-factor authentication is now active.', 'rf-secure-login' ),
					'setupError'        => __( 'Failed to enable 2FA. Please try again.', 'rf-secure-login' ),
					'disableSuccess'    => __( 'Two-factor authentication has been disabled.', 'rf-secure-login' ),
					'disableError'      => __( 'Failed to disable 2FA. Please check your code.', 'rf-secure-login' ),
					'regenerateSuccess' => __( 'New backup codes generated. Save them now.', 'rf-secure-login' ),
					'invalidCode'       => __( 'Invalid verification code. Please try again.', 'rf-secure-login' ),
					'saveCodes'         => __( 'Save these backup codes in a secure location. They will not be shown again.', 'rf-secure-login' ),
					'confirmDisable'    => __( 'Are you sure you want to disable two-factor authentication?', 'rf-secure-login' ),
				),
			)
		);

		wp_add_inline_script( 'rf-user-profile', $this->get_inline_script() );
	}

	/**
	 * Render the 2FA section on the user profile page.
	 *
	 * Displays either the setup flow (QR code, verification input)
	 * or the active status with management options depending on the
	 * user's current 2FA state.
	 *
	 * @since 1.0.0
	 * @param WP_User $user The user object being edited.
	 */
	public function render_2fa_section( $user ) {
		// Only allow editing own profile or users with edit_users capability.
		if ( get_current_user_id() !== $user->ID && ! current_user_can( 'edit_users' ) ) {
			return;
		}

		$is_enabled     = RF_Two_Factor::is_enabled_for_user( $user->ID );
		$is_own_profile = ( get_current_user_id() === $user->ID );

		$this->render_inline_styles();
		?>
		<div id="rf-2fa-profile-section" class="rf-profile-card">
			<h2 class="rf-profile-heading">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;margin-right:8px;color:#00ff88;">
					<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
					<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
				</svg>
				<?php esc_html_e( 'Two-Factor Authentication', 'rf-secure-login' ); ?>
			</h2>

			<div id="rf-2fa-content">
				<?php if ( $is_enabled ) : ?>
					<?php $this->render_active_state( $user, $is_own_profile ); ?>
				<?php else : ?>
					<?php $this->render_setup_state( $user, $is_own_profile ); ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the active 2FA status and management controls.
	 *
	 * Shows the enabled status, setup date, remaining backup codes,
	 * and options to regenerate codes or disable 2FA.
	 *
	 * @since 1.0.0
	 * @param WP_User $user           The user object.
	 * @param bool    $is_own_profile Whether the current user is viewing their own profile.
	 */
	private function render_active_state( $user, $is_own_profile ) {
		$setup_date      = get_user_meta( $user->ID, '_rf_2fa_setup_date', true );
		$remaining_codes = RF_Backup_Codes::remaining_count( $user->ID );

		$formatted_date = '';
		if ( ! empty( $setup_date ) ) {
			$formatted_date = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $setup_date ) );
		}
		?>
		<div class="rf-status-active">
			<span class="rf-status-indicator rf-status-on"></span>
			<span class="rf-status-text">
				<?php esc_html_e( 'Two-Factor Authentication is active', 'rf-secure-login' ); ?>
			</span>
		</div>

		<?php if ( ! empty( $formatted_date ) ) : ?>
			<p class="rf-meta-info">
				<?php
				printf(
					/* translators: %s: date and time when 2FA was set up */
					esc_html__( 'Enabled on: %s', 'rf-secure-login' ),
					'<strong>' . esc_html( $formatted_date ) . '</strong>'
				);
				?>
			</p>
		<?php endif; ?>

		<p class="rf-meta-info">
			<?php
			printf(
				/* translators: %d: number of remaining backup codes */
				esc_html__( 'Backup codes remaining: %d of %d', 'rf-secure-login' ),
				absint( $remaining_codes ),
				RF_Backup_Codes::CODE_COUNT
			);
			?>
		</p>

		<?php if ( $is_own_profile ) : ?>
			<!-- Regenerate Backup Codes -->
			<div class="rf-action-group">
				<h3 class="rf-sub-heading"><?php esc_html_e( 'Backup Codes', 'rf-secure-login' ); ?></h3>
				<p class="rf-description">
					<?php esc_html_e( 'Generate a new set of backup codes. This will invalidate any existing unused codes.', 'rf-secure-login' ); ?>
				</p>
				<button type="button" id="rf-regenerate-codes-btn" class="rf-btn rf-btn-secondary" data-user-id="<?php echo absint( $user->ID ); ?>">
					<?php esc_html_e( 'Regenerate Backup Codes', 'rf-secure-login' ); ?>
				</button>
				<div id="rf-regenerated-codes" class="rf-codes-display" style="display:none;"></div>
			</div>

			<!-- Disable 2FA -->
			<div class="rf-action-group rf-danger-zone">
				<h3 class="rf-sub-heading rf-text-warn"><?php esc_html_e( 'Disable Two-Factor Authentication', 'rf-secure-login' ); ?></h3>
				<p class="rf-description">
					<?php esc_html_e( 'Enter your current authenticator code to disable 2FA. This will remove your secret key and all backup codes.', 'rf-secure-login' ); ?>
				</p>
				<div class="rf-inline-form">
					<label for="rf-disable-code" class="rf-label">
						<?php esc_html_e( 'Authenticator Code', 'rf-secure-login' ); ?>
					</label>
					<input type="text" id="rf-disable-code" class="rf-input rf-input-code" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" placeholder="000000" />
					<button type="button" id="rf-disable-2fa-btn" class="rf-btn rf-btn-danger" data-user-id="<?php echo absint( $user->ID ); ?>">
						<?php esc_html_e( 'Disable Two-Factor Authentication', 'rf-secure-login' ); ?>
					</button>
				</div>
				<div id="rf-disable-message" class="rf-message" style="display:none;"></div>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render the 2FA setup flow for users without 2FA enabled.
	 *
	 * Shows a button to begin setup, which reveals the QR code,
	 * manual secret key, and verification input.
	 *
	 * @since 1.0.0
	 * @param WP_User $user           The user object.
	 * @param bool    $is_own_profile Whether the current user is viewing their own profile.
	 */
	private function render_setup_state( $user, $is_own_profile ) {
		if ( ! $is_own_profile ) {
			?>
			<div class="rf-status-active">
				<span class="rf-status-indicator rf-status-off"></span>
				<span class="rf-status-text">
					<?php esc_html_e( 'Two-Factor Authentication is not enabled for this user.', 'rf-secure-login' ); ?>
				</span>
			</div>
			<?php
			return;
		}

		$secret         = RF_Two_Factor::generate_secret();
		$otpauth_uri    = RF_Two_Factor::get_otpauth_uri( $secret, $user->user_email );
		$qr_image_tag   = RF_QRCode::get_image_tag( $otpauth_uri );
		$secret_display = trim( chunk_split( $secret, 4, ' ' ) );
		?>
		<div class="rf-status-active">
			<span class="rf-status-indicator rf-status-off"></span>
			<span class="rf-status-text">
				<?php esc_html_e( 'Two-Factor Authentication is not enabled.', 'rf-secure-login' ); ?>
			</span>
		</div>

		<button type="button" id="rf-setup-2fa-toggle" class="rf-btn rf-btn-primary">
			<?php esc_html_e( 'Set Up Two-Factor Authentication', 'rf-secure-login' ); ?>
		</button>

		<div id="rf-setup-2fa-panel" class="rf-setup-panel" style="display:none;">
			<div class="rf-setup-steps">
				<h3 class="rf-sub-heading"><?php esc_html_e( 'Step 1: Scan QR Code', 'rf-secure-login' ); ?></h3>
				<p class="rf-description">
					<?php esc_html_e( 'Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.).', 'rf-secure-login' ); ?>
				</p>

				<div class="rf-qr-wrapper">
					<?php
					// QR code image tag is already escaped by RF_QRCode::get_image_tag().
					echo $qr_image_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in RF_QRCode::get_image_tag().
					?>
				</div>

				<h3 class="rf-sub-heading"><?php esc_html_e( 'Or Enter Manually', 'rf-secure-login' ); ?></h3>
				<p class="rf-description">
					<?php esc_html_e( 'If you cannot scan the QR code, enter this secret key in your authenticator app:', 'rf-secure-login' ); ?>
				</p>
				<div class="rf-secret-key">
					<code id="rf-secret-display"><?php echo esc_html( $secret_display ); ?></code>
				</div>

				<h3 class="rf-sub-heading"><?php esc_html_e( 'Step 2: Verify Code', 'rf-secure-login' ); ?></h3>
				<p class="rf-description">
					<?php esc_html_e( 'Enter the 6-digit code from your authenticator app to verify setup.', 'rf-secure-login' ); ?>
				</p>

				<div class="rf-inline-form">
					<label for="rf-verify-code" class="rf-label">
						<?php esc_html_e( 'Verification Code', 'rf-secure-login' ); ?>
					</label>
					<input type="text" id="rf-verify-code" class="rf-input rf-input-code" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" placeholder="000000" />
					<input type="hidden" id="rf-temp-secret" value="<?php echo esc_attr( $secret ); ?>" />
					<button type="button" id="rf-verify-activate-btn" class="rf-btn rf-btn-primary" data-user-id="<?php echo absint( $user->ID ); ?>">
						<?php esc_html_e( 'Verify & Activate', 'rf-secure-login' ); ?>
					</button>
				</div>

				<div id="rf-setup-message" class="rf-message" style="display:none;"></div>
				<div id="rf-setup-codes" class="rf-codes-display" style="display:none;"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle AJAX request to set up 2FA from the user profile.
	 *
	 * Validates the submitted TOTP code against the temporary secret,
	 * enables 2FA for the user, generates backup codes, and returns
	 * them in the response.
	 *
	 * @since 1.0.0
	 * @return void Sends JSON response and terminates.
	 */
	public function handle_setup_2fa_profile() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'rf_setup_2fa_nonce', 'nonce', false ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Security check failed.', 'rf-secure-login' ) )
			);
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		// Must be editing own profile or have edit_users capability.
		if ( 0 === $user_id || ( get_current_user_id() !== $user_id && ! current_user_can( 'edit_users' ) ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'rf-secure-login' ) )
			);
		}

		$secret = isset( $_POST['secret'] ) ? sanitize_text_field( wp_unslash( $_POST['secret'] ) ) : '';
		$code   = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';

		if ( empty( $secret ) || empty( $code ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Secret and verification code are required.', 'rf-secure-login' ) )
			);
		}

		// Validate the TOTP code against the provided secret.
		if ( ! RF_Two_Factor::validate_code( $secret, $code ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid verification code. Please try again.', 'rf-secure-login' ) )
			);
		}

		// Enable 2FA for the user.
		$enabled = RF_Two_Factor::enable_for_user( $user_id, $secret );

		if ( ! $enabled ) {
			wp_send_json_error(
				array( 'message' => __( 'Failed to enable two-factor authentication. Please try again.', 'rf-secure-login' ) )
			);
		}

		// Generate backup codes.
		$backup_codes = RF_Backup_Codes::generate( $user_id );

		wp_send_json_success(
			array(
				'message'      => __( 'Two-factor authentication is now active.', 'rf-secure-login' ),
				'backup_codes' => $backup_codes,
			)
		);
	}

	/**
	 * Handle AJAX request to regenerate backup codes.
	 *
	 * Generates a new set of backup codes for the user, replacing
	 * any existing unused codes.
	 *
	 * @since 1.0.0
	 * @return void Sends JSON response and terminates.
	 */
	public function handle_regenerate_backup_codes() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'rf_regenerate_backup_codes_nonce', 'nonce', false ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Security check failed.', 'rf-secure-login' ) )
			);
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		// Must be editing own profile or have edit_users capability.
		if ( 0 === $user_id || ( get_current_user_id() !== $user_id && ! current_user_can( 'edit_users' ) ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'rf-secure-login' ) )
			);
		}

		// Verify user actually has 2FA enabled.
		if ( ! RF_Two_Factor::is_enabled_for_user( $user_id ) ) {
			wp_send_json_error(
				array( 'message' => __( '2FA is not enabled for this user.', 'rf-secure-login' ) )
			);
		}

		$backup_codes = RF_Backup_Codes::generate( $user_id );

		wp_send_json_success(
			array(
				'message'      => __( 'New backup codes generated. Save them now — they will not be shown again.', 'rf-secure-login' ),
				'backup_codes' => $backup_codes,
			)
		);
	}

	/**
	 * Output inline styles for the profile 2FA section.
	 *
	 * Renders a scoped style block that provides the dark cyberpunk
	 * aesthetic within the WordPress admin profile page.
	 *
	 * @since 1.0.0
	 */
	private function render_inline_styles() {
		static $rendered = false;

		if ( $rendered ) {
			return;
		}

		$rendered = true;
		?>
		<style>
			.rf-profile-card {
				background: #12121a;
				border: 1px solid #1f2937;
				border-radius: 12px;
				padding: 28px 32px;
				margin: 24px 0;
				max-width: 720px;
				box-shadow: 0 0 30px rgba(0, 255, 136, 0.03);
			}

			.rf-profile-heading {
				color: #e0e0e0;
				font-size: 18px;
				font-weight: 600;
				margin: 0 0 20px 0;
				padding-bottom: 14px;
				border-bottom: 1px solid #1f2937;
				line-height: 1.4;
			}

			.rf-sub-heading {
				color: #e0e0e0;
				font-size: 14px;
				font-weight: 600;
				margin: 20px 0 8px 0;
			}

			.rf-text-warn {
				color: #ff3366 !important;
			}

			.rf-description {
				color: #6b7280;
				font-size: 13px;
				line-height: 1.5;
				margin: 0 0 12px 0;
			}

			.rf-meta-info {
				color: #6b7280;
				font-size: 13px;
				margin: 6px 0;
			}

			.rf-meta-info strong {
				color: #e0e0e0;
			}

			/* Status indicators */
			.rf-status-active {
				display: flex;
				align-items: center;
				gap: 10px;
				margin-bottom: 16px;
			}

			.rf-status-indicator {
				display: inline-block;
				width: 10px;
				height: 10px;
				border-radius: 50%;
				flex-shrink: 0;
			}

			.rf-status-on {
				background: #00ff88;
				box-shadow: 0 0 8px rgba(0, 255, 136, 0.5);
			}

			.rf-status-off {
				background: #6b7280;
			}

			.rf-status-text {
				color: #e0e0e0;
				font-size: 14px;
				font-weight: 500;
			}

			/* Buttons */
			.rf-btn {
				display: inline-block;
				padding: 9px 20px;
				border-radius: 8px;
				font-size: 13px;
				font-weight: 600;
				cursor: pointer;
				border: none;
				transition: all 0.2s ease;
				line-height: 1.4;
				text-decoration: none;
			}

			.rf-btn:focus {
				outline: 2px solid #00ff88;
				outline-offset: 2px;
			}

			.rf-btn-primary {
				background: linear-gradient(135deg, #00ff88, #00d4ff);
				color: #0a0a0f;
			}

			.rf-btn-primary:hover {
				box-shadow: 0 0 20px rgba(0, 255, 136, 0.3);
				transform: translateY(-1px);
			}

			.rf-btn-primary:active {
				transform: translateY(0);
			}

			.rf-btn-secondary {
				background: #1a1a2e;
				color: #00d4ff;
				border: 1px solid #1f2937;
			}

			.rf-btn-secondary:hover {
				border-color: #00d4ff;
				box-shadow: 0 0 12px rgba(0, 212, 255, 0.15);
			}

			.rf-btn-danger {
				background: rgba(255, 51, 102, 0.1);
				color: #ff3366;
				border: 1px solid rgba(255, 51, 102, 0.3);
			}

			.rf-btn-danger:hover {
				background: rgba(255, 51, 102, 0.2);
				border-color: #ff3366;
				box-shadow: 0 0 12px rgba(255, 51, 102, 0.2);
			}

			.rf-btn:disabled {
				opacity: 0.5;
				cursor: not-allowed;
				transform: none !important;
				box-shadow: none !important;
			}

			/* Inputs */
			.rf-label {
				display: block;
				color: #6b7280;
				font-size: 11px;
				font-weight: 600;
				text-transform: uppercase;
				letter-spacing: 0.1em;
				margin-bottom: 6px;
			}

			.rf-input {
				background: #1a1a2e;
				border: 1px solid #1f2937;
				color: #e0e0e0;
				padding: 9px 14px;
				border-radius: 8px;
				font-size: 14px;
				transition: all 0.2s ease;
				outline: none;
			}

			.rf-input:focus {
				border-color: #00ff88;
				box-shadow: 0 0 0 3px rgba(0, 255, 136, 0.12);
			}

			.rf-input-code {
				font-family: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace;
				font-size: 18px;
				letter-spacing: 0.3em;
				text-align: center;
				width: 160px;
			}

			.rf-inline-form {
				margin-top: 12px;
			}

			.rf-inline-form .rf-input {
				margin-bottom: 12px;
			}

			.rf-inline-form .rf-btn {
				display: block;
			}

			/* Setup panel */
			.rf-setup-panel {
				margin-top: 20px;
				padding-top: 20px;
				border-top: 1px solid #1f2937;
			}

			/* QR code wrapper */
			.rf-qr-wrapper {
				background: #ffffff;
				display: inline-block;
				padding: 12px;
				border-radius: 10px;
				margin: 12px 0 20px 0;
				box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
			}

			.rf-qr-wrapper img {
				display: block;
			}

			/* Secret key display */
			.rf-secret-key {
				margin: 8px 0 20px 0;
			}

			.rf-secret-key code {
				display: inline-block;
				background: #1a1a2e;
				border: 1px solid #1f2937;
				color: #00ff88;
				font-family: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace;
				font-size: 16px;
				letter-spacing: 0.15em;
				padding: 10px 16px;
				border-radius: 8px;
				user-select: all;
			}

			/* Action groups */
			.rf-action-group {
				margin-top: 24px;
				padding-top: 20px;
				border-top: 1px solid #1f2937;
			}

			.rf-danger-zone {
				border-top-color: rgba(255, 51, 102, 0.2);
			}

			/* Messages */
			.rf-message {
				margin-top: 12px;
				padding: 10px 14px;
				border-radius: 8px;
				font-size: 13px;
				line-height: 1.5;
			}

			.rf-message-success {
				background: rgba(0, 255, 136, 0.08);
				border: 1px solid rgba(0, 255, 136, 0.2);
				color: #00ff88;
			}

			.rf-message-error {
				background: rgba(255, 51, 102, 0.08);
				border: 1px solid rgba(255, 51, 102, 0.2);
				color: #ff3366;
			}

			/* Backup codes display */
			.rf-codes-display {
				margin-top: 16px;
			}

			.rf-codes-notice {
				color: #00d4ff;
				font-size: 12px;
				font-weight: 600;
				text-transform: uppercase;
				letter-spacing: 0.05em;
				margin-bottom: 10px;
			}

			.rf-codes-grid {
				display: grid;
				grid-template-columns: repeat(2, 1fr);
				gap: 6px;
				max-width: 360px;
				margin-bottom: 12px;
			}

			.rf-codes-grid span {
				display: block;
				background: #1a1a2e;
				border: 1px solid #1f2937;
				color: #e0e0e0;
				font-family: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace;
				font-size: 14px;
				padding: 6px 12px;
				border-radius: 6px;
				text-align: center;
				letter-spacing: 0.1em;
			}

			/* Spinner */
			.rf-spinner {
				display: inline-block;
				width: 14px;
				height: 14px;
				border: 2px solid transparent;
				border-top-color: currentColor;
				border-radius: 50%;
				animation: rf-spin 0.6s linear infinite;
				margin-right: 6px;
				vertical-align: middle;
			}

			@keyframes rf-spin {
				to { transform: rotate(360deg); }
			}
		</style>
		<?php
	}

	/**
	 * Generate the inline JavaScript for AJAX interactions.
	 *
	 * Returns a self-executing function that handles the setup toggle,
	 * 2FA activation, backup code regeneration, and 2FA disabling flows.
	 *
	 * @since 1.0.0
	 * @return string JavaScript code.
	 */
	private function get_inline_script() {
		return <<<'JSEOF'
(function() {
	'use strict';

	/**
	 * Show a message element with appropriate styling.
	 */
	function showMessage(el, type, text) {
		el.className = 'rf-message rf-message-' + type;
		el.textContent = text;
		el.style.display = 'block';
	}

	/**
	 * Render backup codes into a container element.
	 */
	function renderBackupCodes(container, codes, noticeText) {
		var html = '<p class="rf-codes-notice">' + escapeHtml(noticeText) + '</p>';
		html += '<div class="rf-codes-grid">';
		for (var i = 0; i < codes.length; i++) {
			html += '<span>' + escapeHtml(codes[i]) + '</span>';
		}
		html += '</div>';
		container.innerHTML = html;
		container.style.display = 'block';
	}

	/**
	 * Escape HTML entities for safe insertion.
	 */
	function escapeHtml(text) {
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(text));
		return div.innerHTML;
	}

	/**
	 * Set a button to loading state.
	 */
	function setLoading(btn, loading) {
		if (loading) {
			btn.disabled = true;
			btn.dataset.originalText = btn.textContent;
			btn.innerHTML = '<span class="rf-spinner"></span> ' + escapeHtml(btn.textContent);
		} else {
			btn.disabled = false;
			if (btn.dataset.originalText) {
				btn.textContent = btn.dataset.originalText;
			}
		}
	}

	/**
	 * Send an AJAX POST request.
	 */
	function ajaxPost(action, data, callback) {
		var formData = new FormData();
		formData.append('action', action);
		for (var key in data) {
			if (data.hasOwnProperty(key)) {
				formData.append(key, data[key]);
			}
		}

		var xhr = new XMLHttpRequest();
		xhr.open('POST', rfProfile.ajaxUrl, true);
		xhr.onreadystatechange = function() {
			if (4 === xhr.readyState) {
				var response;
				try {
					response = JSON.parse(xhr.responseText);
				} catch (e) {
					response = { success: false, data: { message: 'Unexpected server response.' } };
				}
				callback(response);
			}
		};
		xhr.send(formData);
	}

	document.addEventListener('DOMContentLoaded', function() {

		/* ----------------------------------------------------------
		 * Toggle setup panel visibility
		 * -------------------------------------------------------- */
		var toggleBtn = document.getElementById('rf-setup-2fa-toggle');
		var panel     = document.getElementById('rf-setup-2fa-panel');

		if (toggleBtn && panel) {
			toggleBtn.addEventListener('click', function() {
				if ('none' === panel.style.display) {
					panel.style.display = 'block';
					toggleBtn.textContent = rfProfile.i18n.setupSuccess ? rfProfile.i18n.cancelSetup || 'Cancel Setup' : 'Cancel Setup';
					/* Update button to cancel */
					toggleBtn.textContent = 'Cancel Setup';
					toggleBtn.classList.remove('rf-btn-primary');
					toggleBtn.classList.add('rf-btn-secondary');
				} else {
					panel.style.display = 'none';
					toggleBtn.textContent = 'Set Up Two-Factor Authentication';
					toggleBtn.classList.remove('rf-btn-secondary');
					toggleBtn.classList.add('rf-btn-primary');
				}
			});
		}

		/* ----------------------------------------------------------
		 * Verify & Activate 2FA
		 * -------------------------------------------------------- */
		var verifyBtn = document.getElementById('rf-verify-activate-btn');

		if (verifyBtn) {
			verifyBtn.addEventListener('click', function() {
				var codeInput    = document.getElementById('rf-verify-code');
				var secretInput  = document.getElementById('rf-temp-secret');
				var messageEl    = document.getElementById('rf-setup-message');
				var codesEl      = document.getElementById('rf-setup-codes');
				var code         = codeInput ? codeInput.value.replace(/\D/g, '') : '';
				var secret       = secretInput ? secretInput.value : '';
				var userId       = verifyBtn.getAttribute('data-user-id');

				if (6 !== code.length) {
					showMessage(messageEl, 'error', rfProfile.i18n.invalidCode);
					return;
				}

				setLoading(verifyBtn, true);

				ajaxPost('rf_setup_2fa_profile', {
					nonce:   rfProfile.setupNonce,
					user_id: userId,
					secret:  secret,
					code:    code
				}, function(response) {
					setLoading(verifyBtn, false);

					if (response.success && response.data) {
						showMessage(messageEl, 'success', response.data.message || rfProfile.i18n.setupSuccess);

						if (response.data.backup_codes && response.data.backup_codes.length) {
							renderBackupCodes(codesEl, response.data.backup_codes, rfProfile.i18n.saveCodes);
						}

						/* Hide the setup form elements */
						var setupSteps = document.querySelector('.rf-setup-steps');
						if (setupSteps) {
							var children = setupSteps.children;
							for (var i = 0; i < children.length; i++) {
								if (children[i] !== messageEl && children[i] !== codesEl) {
									children[i].style.display = 'none';
								}
							}
						}

						if (toggleBtn) {
							toggleBtn.style.display = 'none';
						}

						/* Update status indicator */
						var contentEl = document.getElementById('rf-2fa-content');
						var statusHtml = '<div class="rf-status-active">';
						statusHtml += '<span class="rf-status-indicator rf-status-on"></span>';
						statusHtml += '<span class="rf-status-text">' + escapeHtml(rfProfile.i18n.setupSuccess) + '</span>';
						statusHtml += '</div>';

						var statusContainer = contentEl ? contentEl.querySelector('.rf-status-active') : null;
						if (statusContainer) {
							statusContainer.innerHTML = '<span class="rf-status-indicator rf-status-on"></span>' +
								'<span class="rf-status-text">' + escapeHtml(rfProfile.i18n.setupSuccess) + '</span>';
						}
					} else {
						var msg = (response.data && response.data.message) ? response.data.message : rfProfile.i18n.setupError;
						showMessage(messageEl, 'error', msg);
					}
				});
			});
		}

		/* ----------------------------------------------------------
		 * Regenerate Backup Codes
		 * -------------------------------------------------------- */
		var regenBtn = document.getElementById('rf-regenerate-codes-btn');

		if (regenBtn) {
			regenBtn.addEventListener('click', function() {
				var codesEl = document.getElementById('rf-regenerated-codes');
				var userId  = regenBtn.getAttribute('data-user-id');

				setLoading(regenBtn, true);

				ajaxPost('rf_regenerate_backup_codes', {
					nonce:   rfProfile.regenerateNonce,
					user_id: userId
				}, function(response) {
					setLoading(regenBtn, false);

					if (response.success && response.data && response.data.backup_codes) {
						renderBackupCodes(codesEl, response.data.backup_codes, rfProfile.i18n.saveCodes);
					} else {
						var msg = (response.data && response.data.message) ? response.data.message : rfProfile.i18n.setupError;
						if (codesEl) {
							codesEl.innerHTML = '<div class="rf-message rf-message-error">' + escapeHtml(msg) + '</div>';
							codesEl.style.display = 'block';
						}
					}
				});
			});
		}

		/* ----------------------------------------------------------
		 * Disable 2FA
		 * -------------------------------------------------------- */
		var disableBtn = document.getElementById('rf-disable-2fa-btn');

		if (disableBtn) {
			disableBtn.addEventListener('click', function() {
				var codeInput = document.getElementById('rf-disable-code');
				var messageEl = document.getElementById('rf-disable-message');
				var code      = codeInput ? codeInput.value.replace(/\D/g, '') : '';
				var userId    = disableBtn.getAttribute('data-user-id');

				if (6 !== code.length) {
					showMessage(messageEl, 'error', rfProfile.i18n.invalidCode);
					return;
				}

				if (!confirm(rfProfile.i18n.confirmDisable)) {
					return;
				}

				setLoading(disableBtn, true);

				ajaxPost('rf_disable_2fa', {
					nonce:   rfProfile.disableNonce,
					code:    code,
					user_id: userId
				}, function(response) {
					setLoading(disableBtn, false);

					if (response.success || ('success' === response.status)) {
						var msg = (response.data && response.data.message) ? response.data.message : (response.message || rfProfile.i18n.disableSuccess);
						showMessage(messageEl, 'success', msg);

						/* Reload after a brief delay to show fresh state */
						setTimeout(function() {
							window.location.reload();
						}, 1500);
					} else {
						var errMsg = (response.data && response.data.message) ? response.data.message : (response.message || rfProfile.i18n.disableError);
						showMessage(messageEl, 'error', errMsg);
					}
				});
			});
		}
	});
})();
JSEOF;
	}
}
