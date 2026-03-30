<?php
/**
 * Two-Factor Setup Template (Stub).
 *
 * Placeholder template for the 2FA setup flow. The setup container
 * will be populated via AJAX in Phase 6.
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
		<?php esc_html_e( 'Setup Two-Factor Authentication', 'rf-secure-login' ); ?>
	</p>

	<!-- 2FA Setup container (populated via AJAX in Phase 6) -->
	<div id="rf-2fa-setup-container" class="rf-stagger" style="--stagger: 2;">
		<p class="rf-description">
			<?php esc_html_e( 'Two-factor authentication setup will be available here.', 'rf-secure-login' ); ?>
		</p>
	</div>

	<!-- Back to login link -->
	<div class="rf-back-link rf-stagger" style="--stagger: 3;">
		<a href="<?php echo esc_url( wp_login_url() ); ?>" class="rf-lost-password-link">
			&larr; <?php esc_html_e( 'Back to login', 'rf-secure-login' ); ?>
		</a>
	</div>

</div><!-- .rf-card -->
