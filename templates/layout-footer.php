<?php
/**
 * Layout Footer Template.
 *
 * Closes the main content wrapper, renders the Red Frog Studio
 * branding footer, fires login_footer action, prints scripts,
 * and closes the body/html tags.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
	</main><!-- .rf-login-wrapper -->

	<!-- Red Frog Studio branding footer -->
	<footer class="rf-footer">
		<a href="https://redfrogstudio.co.uk" target="_blank" rel="noopener noreferrer" class="rf-footer-logo-link" aria-label="<?php esc_attr_e( 'Red Frog Studio', 'rf-secure-login' ); ?>">
			<img
				src="<?php echo esc_url( RF_SECURE_LOGIN_URL . 'assets/images/red-frog-logo.png' ); ?>"
				alt="<?php esc_attr_e( 'Red Frog Studio', 'rf-secure-login' ); ?>"
				class="rf-footer-logo"
				width="28"
				height="28"
			/>
		</a>
		<span class="rf-footer-text">
			<?php esc_html_e( 'Protected by Red Frog Secure Login', 'rf-secure-login' ); ?>
		</span>
	</footer>

	<?php
	/**
	 * Fires in the login page footer.
	 *
	 * Used by WordPress core and plugins to output additional
	 * scripts and HTML before the closing body tag.
	 */
	do_action( 'login_footer' );

	wp_print_scripts();
	?>
</body>
</html>
