<?php
/**
 * Check Email Template.
 *
 * Renders the confirmation page shown after a password reset
 * request or user registration.
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

// Determine which checkemail type was triggered.
$checkemail_type = isset( $_GET['checkemail'] ) ? sanitize_text_field( wp_unslash( $_GET['checkemail'] ) ) : 'confirm';
?>
<div class="rf-card rf-card-entrance">

	<!-- Logo area -->
	<div class="rf-logo-container rf-stagger" style="--stagger: 0;">
		<?php echo wp_kses_post( $logo_html ); ?>
	</div>

	<!-- Tagline -->
	<p class="rf-tagline rf-stagger" style="--stagger: 1;">
		<?php esc_html_e( 'Check Your Email', 'rf-secure-login' ); ?>
	</p>

	<!-- Envelope icon -->
	<div class="rf-checkemail-icon rf-stagger" style="--stagger: 2;" aria-hidden="true">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="64" height="64" class="rf-envelope-svg">
			<rect x="2" y="4" width="20" height="16" rx="2" />
			<path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
		</svg>
	</div>

	<!-- Confirmation message -->
	<div class="rf-checkemail-message rf-stagger" style="--stagger: 3;">
		<?php if ( 'registered' === $checkemail_type ) : ?>
			<p class="rf-description">
				<?php esc_html_e( 'Registration is almost complete. Please check your email for your login details.', 'rf-secure-login' ); ?>
			</p>
		<?php else : ?>
			<p class="rf-description">
				<?php esc_html_e( 'If there is an account associated with the username or email address you entered, you will receive a password reset link shortly.', 'rf-secure-login' ); ?>
			</p>
			<p class="rf-description rf-description-muted">
				<?php esc_html_e( 'If you do not receive an email, please check your spam folder or try again.', 'rf-secure-login' ); ?>
			</p>
		<?php endif; ?>
	</div>

	<!-- Back to login link -->
	<div class="rf-back-link rf-stagger" style="--stagger: 4;">
		<a href="<?php echo esc_url( wp_login_url() ); ?>" class="rf-lost-password-link">
			&larr; <?php esc_html_e( 'Back to login', 'rf-secure-login' ); ?>
		</a>
	</div>

</div><!-- .rf-card -->
