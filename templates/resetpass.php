<?php
/**
 * Reset Password Template.
 *
 * Renders the password reset form with new password and
 * confirm password inputs. Validates the reset key before
 * displaying the form.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 *
 * @var string   $logo_html      Logo HTML markup.
 * @var string   $error_messages  Error messages HTML.
 * @var string   $message         Success/info message string.
 * @var string   $redirect_to     Redirect URL after login.
 * @var WP_Error $errors          WP_Error object.
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get reset key and login from the request.
$rp_key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
$rp_login = isset( $_GET['login'] ) ? sanitize_user( wp_unslash( $_GET['login'] ) ) : '';

// Validate the reset key.
$rp_user    = null;
$key_valid  = false;
$key_errors = '';

if ( $rp_key && $rp_login ) {
	$rp_user = check_password_reset_key( $rp_key, $rp_login );
	if ( ! is_wp_error( $rp_user ) ) {
		$key_valid = true;
	} else {
		$key_error_codes = $rp_user->get_error_codes();
		if ( in_array( 'expired_key', $key_error_codes, true ) ) {
			$key_errors = __( 'Your password reset link has expired. Please request a new one.', 'rf-secure-login' );
		} else {
			$key_errors = __( 'Your password reset link is invalid. Please request a new one.', 'rf-secure-login' );
		}
	}
} else {
	$key_errors = __( 'Your password reset link is invalid. Please request a new one.', 'rf-secure-login' );
}
?>
<div class="rf-card rf-card-entrance">

	<!-- Logo area -->
	<div class="rf-logo-container rf-stagger" style="--stagger: 0;">
		<?php echo wp_kses_post( $logo_html ); ?>
	</div>

	<!-- Tagline -->
	<p class="rf-tagline rf-stagger" style="--stagger: 1;">
		<?php esc_html_e( 'Set New Password', 'rf-secure-login' ); ?>
	</p>

	<!-- Error container (from form submission or key validation) -->
	<?php if ( $error_messages || $key_errors ) : ?>
		<div id="rf-error-container" class="rf-stagger" style="--stagger: 2;">
			<div class="rf-error-bar" role="alert" aria-live="assertive">
				<?php
				if ( $error_messages ) {
					echo wp_kses_post( $error_messages );
				}
				if ( $key_errors ) {
					echo '<div class="rf-error-message">' . esc_html( $key_errors ) . '</div>';
				}
				?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $key_valid ) : ?>

		<!-- Reset password form -->
		<form id="rf-resetpass-form" method="post" action="<?php echo esc_url( network_site_url( 'wp-login.php?action=resetpass', 'login_post' ) ); ?>" class="rf-form">

			<?php wp_nonce_field( 'reset_password', 'wp_nonce' ); ?>
			<input type="hidden" name="rp_key" value="<?php echo esc_attr( $rp_key ); ?>" />
			<input type="hidden" name="rp_login" value="<?php echo esc_attr( $rp_login ); ?>" />

			<!-- New Password field -->
			<div class="rf-field rf-stagger" style="--stagger: 3;">
				<label for="pass1" class="rf-label">
					<?php esc_html_e( 'New Password', 'rf-secure-login' ); ?>
				</label>
				<div class="rf-input-wrapper">
					<span class="rf-input-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
							<path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd" />
						</svg>
					</span>
					<input
						type="password"
						id="pass1"
						name="pass1"
						class="rf-input rf-input-with-icon"
						placeholder="<?php esc_attr_e( 'Enter new password', 'rf-secure-login' ); ?>"
						autocomplete="new-password"
						spellcheck="false"
						required
					/>
				</div>
			</div>

			<!-- Confirm Password field -->
			<div class="rf-field rf-stagger" style="--stagger: 4;">
				<label for="pass2" class="rf-label">
					<?php esc_html_e( 'Confirm Password', 'rf-secure-login' ); ?>
				</label>
				<div class="rf-input-wrapper">
					<span class="rf-input-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
							<path fill-rule="evenodd" d="M16.403 12.652a3 3 0 0 0 0-5.304 3 3 0 0 0-3.75-3.751 3 3 0 0 0-5.305 0 3 3 0 0 0-3.751 3.75 3 3 0 0 0 0 5.305 3 3 0 0 0 3.75 3.751 3 3 0 0 0 5.305 0 3 3 0 0 0 3.751-3.75Zm-2.546-4.46a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
						</svg>
					</span>
					<input
						type="password"
						id="pass2"
						name="pass2"
						class="rf-input rf-input-with-icon"
						placeholder="<?php esc_attr_e( 'Confirm new password', 'rf-secure-login' ); ?>"
						autocomplete="new-password"
						spellcheck="false"
						required
					/>
				</div>
			</div>

			<!-- Submit button -->
			<div class="rf-stagger" style="--stagger: 5;">
				<button type="submit" class="rf-btn-primary">
					<span><?php esc_html_e( 'Save Password', 'rf-secure-login' ); ?></span>
				</button>
			</div>

		</form>

	<?php endif; ?>

	<!-- Back to login link -->
	<div class="rf-back-link rf-stagger" style="--stagger: <?php echo $key_valid ? '6' : '3'; ?>;">
		<a href="<?php echo esc_url( wp_login_url() ); ?>" class="rf-lost-password-link">
			&larr; <?php esc_html_e( 'Back to login', 'rf-secure-login' ); ?>
		</a>
	</div>

</div><!-- .rf-card -->
