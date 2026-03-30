<?php
/**
 * Login Template.
 *
 * Renders the main login form with username/password inputs,
 * 2FA digit inputs (hidden initially), remember me toggle,
 * submit button, and lost password link.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 *
 * @var string $logo_html      Logo HTML markup.
 * @var string $error_messages  Error messages HTML.
 * @var string $message         Success/info message string.
 * @var string $redirect_to     Redirect URL after login.
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="rf-card rf-card-entrance">

	<!-- Logo area -->
	<div class="rf-logo-container rf-stagger" style="--stagger: 0;">
		<?php echo wp_kses_post( $logo_html ); ?>
	</div>

	<!-- Tagline -->
	<p class="rf-tagline rf-stagger" style="--stagger: 1;">
		<?php esc_html_e( 'Secure Access', 'rf-secure-login' ); ?>
	</p>

	<!-- Error container -->
	<div id="rf-error-container" class="rf-stagger <?php echo $error_messages ? '' : 'hidden'; ?>" style="--stagger: 2;">
		<div class="rf-error-bar" role="alert" aria-live="assertive">
			<?php echo wp_kses_post( $error_messages ); ?>
		</div>
	</div>

	<!-- Success message bar -->
	<?php if ( $message ) : ?>
		<div class="rf-success-bar rf-stagger" style="--stagger: 2;" role="status">
			<?php echo wp_kses_post( $message ); ?>
		</div>
	<?php endif; ?>

	<!-- Login form -->
	<form id="rf-login-form" method="post" action="<?php echo esc_url( wp_login_url() ); ?>" class="rf-form">

		<?php wp_nonce_field( 'rf_login_nonce', 'rf_login_nonce_field' ); ?>
		<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
		<input type="hidden" name="testcookie" value="1" />

		<!-- Username field -->
		<div class="rf-field rf-stagger" style="--stagger: 3;" id="rf-username-group">
			<label for="user_login" class="rf-label">
				<?php esc_html_e( 'Username or Email', 'rf-secure-login' ); ?>
			</label>
			<div class="rf-input-wrapper">
				<span class="rf-input-icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
						<path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 0 0-13.074.003Z" />
					</svg>
				</span>
				<input
					type="text"
					id="user_login"
					name="log"
					class="rf-input rf-input-with-icon"
					value="<?php echo isset( $_POST['log'] ) ? esc_attr( wp_unslash( $_POST['log'] ) ) : ''; ?>"
					placeholder="<?php esc_attr_e( 'Enter your username or email', 'rf-secure-login' ); ?>"
					autocomplete="username"
					autocapitalize="off"
					spellcheck="false"
					required
				/>
			</div>
		</div>

		<!-- Password field -->
		<div class="rf-field rf-stagger" style="--stagger: 4;" id="rf-password-group">
			<label for="user_pass" class="rf-label">
				<?php esc_html_e( 'Password', 'rf-secure-login' ); ?>
			</label>
			<div class="rf-input-wrapper">
				<span class="rf-input-icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
						<path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd" />
					</svg>
				</span>
				<input
					type="password"
					id="user_pass"
					name="pwd"
					class="rf-input rf-input-with-icon"
					placeholder="<?php esc_attr_e( 'Enter your password', 'rf-secure-login' ); ?>"
					autocomplete="current-password"
					spellcheck="false"
					required
				/>
			</div>
		</div>

		<!-- 2FA digit inputs (hidden initially, shown via JS) -->
		<div id="rf-2fa-group" class="rf-field hidden" role="group" aria-label="<?php esc_attr_e( 'Two-factor authentication code', 'rf-secure-login' ); ?>">
			<label class="rf-label">
				<?php esc_html_e( 'Authentication Code', 'rf-secure-login' ); ?>
			</label>
			<p class="rf-2fa-description">
				<?php esc_html_e( 'Enter the 6-digit code from your authenticator app.', 'rf-secure-login' ); ?>
			</p>
			<div class="rf-2fa-digits">
				<input type="text" class="rf-2fa-digit" data-index="0" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code" aria-label="<?php esc_attr_e( 'Digit 1', 'rf-secure-login' ); ?>" />
				<input type="text" class="rf-2fa-digit" data-index="1" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="<?php esc_attr_e( 'Digit 2', 'rf-secure-login' ); ?>" />
				<input type="text" class="rf-2fa-digit" data-index="2" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="<?php esc_attr_e( 'Digit 3', 'rf-secure-login' ); ?>" />
				<span class="rf-2fa-separator" aria-hidden="true">&mdash;</span>
				<input type="text" class="rf-2fa-digit" data-index="3" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="<?php esc_attr_e( 'Digit 4', 'rf-secure-login' ); ?>" />
				<input type="text" class="rf-2fa-digit" data-index="4" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="<?php esc_attr_e( 'Digit 5', 'rf-secure-login' ); ?>" />
				<input type="text" class="rf-2fa-digit" data-index="5" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="<?php esc_attr_e( 'Digit 6', 'rf-secure-login' ); ?>" />
			</div>
			<input type="hidden" id="rf-2fa-token" name="rf_2fa_token" value="" />
		</div>

		<!-- Backup code link -->
		<div id="rf-backup-toggle" class="hidden">
			<button type="button" id="rf-use-backup-code" class="rf-link-button">
				<?php esc_html_e( 'Use a backup code instead', 'rf-secure-login' ); ?>
			</button>
		</div>

		<!-- Backup code input (hidden initially) -->
		<div id="rf-backup-group" class="rf-field hidden">
			<label for="rf-backup-code" class="rf-label">
				<?php esc_html_e( 'Backup Code', 'rf-secure-login' ); ?>
			</label>
			<div class="rf-input-wrapper">
				<span class="rf-input-icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
						<path fill-rule="evenodd" d="M3.5 2A1.5 1.5 0 0 0 2 3.5V15a3 3 0 1 0 6 0V3.5A1.5 1.5 0 0 0 6.5 2h-3Zm11.76 6.923A.75.75 0 0 0 14 9.709v3.366a.75.75 0 0 0 .38.651l2.75 1.571a.75.75 0 0 0 .74-1.304L15.75 12.87V9.71a.75.75 0 0 0-.49-.786Z" clip-rule="evenodd" />
						<path d="M15.5 9.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11Z" />
					</svg>
				</span>
				<input
					type="text"
					id="rf-backup-code"
					name="rf_backup_code"
					class="rf-input rf-input-with-icon"
					placeholder="<?php esc_attr_e( 'xxxx-xxxx', 'rf-secure-login' ); ?>"
					autocomplete="off"
					spellcheck="false"
				/>
			</div>
			<button type="button" id="rf-use-authenticator" class="rf-link-button">
				<?php esc_html_e( 'Use authenticator code instead', 'rf-secure-login' ); ?>
			</button>
		</div>

		<!-- 2FA setup container (hidden, populated via AJAX in Phase 6) -->
		<div id="rf-2fa-setup-group" class="hidden"></div>

		<!-- Remember Me toggle -->
		<div class="rf-remember-row rf-stagger" style="--stagger: 5;" id="rf-remember-group">
			<label class="rf-toggle-label" for="rememberme">
				<input type="checkbox" id="rememberme" name="rememberme" value="forever" class="sr-only peer" />
				<div class="rf-toggle peer-checked:bg-[var(--accent-primary)] peer-focus:ring-2 peer-focus:ring-[var(--accent-glow)]">
					<div class="rf-toggle-dot peer-checked:translate-x-full"></div>
				</div>
				<span class="rf-toggle-text">
					<?php esc_html_e( 'Remember Me', 'rf-secure-login' ); ?>
				</span>
			</label>
		</div>

		<!-- Submit button -->
		<div class="rf-stagger" style="--stagger: 6;">
			<button type="submit" id="rf-submit-btn" class="rf-btn-primary">
				<span id="rf-btn-text"><?php esc_html_e( 'Sign In', 'rf-secure-login' ); ?></span>
				<span id="rf-btn-loading" class="hidden rf-btn-loading-content">
					<svg class="rf-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
						<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
						<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647Z"></path>
					</svg>
					<span><?php esc_html_e( 'Authenticating...', 'rf-secure-login' ); ?></span>
				</span>
			</button>
		</div>

		<?php
		/**
		 * Fires inside the login form.
		 *
		 * Used by plugins to add custom form fields.
		 */
		do_action( 'login_form' );
		?>

	</form>

	<!-- Lost password link with tooltip -->
	<div class="rf-lost-password rf-stagger" style="--stagger: 7;">
		<a
			href="<?php echo esc_url( wp_lostpassword_url() ); ?>"
			class="rf-lost-password-link"
			data-tooltip-target="rf-tooltip-lostpass"
			data-tooltip-placement="bottom"
		>
			<?php esc_html_e( 'Lost your password?', 'rf-secure-login' ); ?>
		</a>
		<div id="rf-tooltip-lostpass" role="tooltip" class="rf-tooltip">
			<?php esc_html_e( 'Reset via email', 'rf-secure-login' ); ?>
			<div class="tooltip-arrow" data-popper-arrow></div>
		</div>
	</div>

</div><!-- .rf-card -->
