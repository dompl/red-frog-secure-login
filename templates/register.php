<?php
/**
 * Register Template.
 *
 * Renders the user registration form with username and email inputs.
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
		<?php esc_html_e( 'Create Account', 'rf-secure-login' ); ?>
	</p>

	<!-- Error container -->
	<?php if ( $error_messages ) : ?>
		<div id="rf-error-container" class="rf-stagger" style="--stagger: 2;">
			<div class="rf-error-bar" role="alert" aria-live="assertive">
				<?php echo wp_kses_post( $error_messages ); ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- Success message bar -->
	<?php if ( $message ) : ?>
		<div class="rf-success-bar rf-stagger" style="--stagger: 2;" role="status">
			<?php echo wp_kses_post( $message ); ?>
		</div>
	<?php endif; ?>

	<!-- Register form -->
	<form id="rf-register-form" method="post" action="<?php echo esc_url( network_site_url( 'wp-login.php?action=register', 'login_post' ) ); ?>" class="rf-form">

		<?php wp_nonce_field( 'register', 'wp_nonce' ); ?>

		<!-- Username field -->
		<div class="rf-field rf-stagger" style="--stagger: 3;">
			<label for="user_login" class="rf-label">
				<?php esc_html_e( 'Username', 'rf-secure-login' ); ?>
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
					name="user_login"
					class="rf-input rf-input-with-icon"
					value="<?php echo isset( $_POST['user_login'] ) ? esc_attr( wp_unslash( $_POST['user_login'] ) ) : ''; ?>"
					placeholder="<?php esc_attr_e( 'Choose a username', 'rf-secure-login' ); ?>"
					autocomplete="username"
					autocapitalize="off"
					spellcheck="false"
					required
				/>
			</div>
		</div>

		<!-- Email field -->
		<div class="rf-field rf-stagger" style="--stagger: 4;">
			<label for="user_email" class="rf-label">
				<?php esc_html_e( 'Email Address', 'rf-secure-login' ); ?>
			</label>
			<div class="rf-input-wrapper">
				<span class="rf-input-icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
						<path d="M3 4a2 2 0 0 0-2 2v1.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 7.162V6a2 2 0 0 0-2-2H3Z" />
						<path d="m19 8.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 8.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.839Z" />
					</svg>
				</span>
				<input
					type="email"
					id="user_email"
					name="user_email"
					class="rf-input rf-input-with-icon"
					value="<?php echo isset( $_POST['user_email'] ) ? esc_attr( wp_unslash( $_POST['user_email'] ) ) : ''; ?>"
					placeholder="<?php esc_attr_e( 'Enter your email address', 'rf-secure-login' ); ?>"
					autocomplete="email"
					spellcheck="false"
					required
				/>
			</div>
		</div>

		<!-- Password note -->
		<p class="rf-note rf-stagger" style="--stagger: 5;">
			<?php esc_html_e( 'A password will be emailed to you.', 'rf-secure-login' ); ?>
		</p>

		<?php
		/**
		 * Fires inside the registration form.
		 *
		 * Used by plugins to add custom form fields.
		 */
		do_action( 'register_form' );
		?>

		<!-- Submit button -->
		<div class="rf-stagger" style="--stagger: 6;">
			<button type="submit" class="rf-btn-primary">
				<span><?php esc_html_e( 'Register', 'rf-secure-login' ); ?></span>
			</button>
		</div>

	</form>

	<!-- Back to login link -->
	<div class="rf-back-link rf-stagger" style="--stagger: 7;">
		<a href="<?php echo esc_url( wp_login_url() ); ?>" class="rf-lost-password-link">
			&larr; <?php esc_html_e( 'Back to login', 'rf-secure-login' ); ?>
		</a>
	</div>

</div><!-- .rf-card -->
