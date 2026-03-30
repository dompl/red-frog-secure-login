<?php
/**
 * RF_Login_Page class.
 *
 * Intercepts WordPress login page requests and renders the custom
 * cyberpunk-themed login screen with canvas animation, styled forms,
 * and two-factor authentication support.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RF_Login_Page
 *
 * Hooks into login_init at priority 1 to take over the login page
 * rendering. Handles all standard WordPress login actions (login,
 * lostpassword, resetpass, register) through custom templates while
 * preserving core WordPress login functionality.
 *
 * @since 1.0.0
 */
class RF_Login_Page {

	/**
	 * Actions that should use the custom login templates.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $custom_actions = array(
		'login',
		'lostpassword',
		'retrievepassword',
		'resetpass',
		'rp',
		'register',
	);

	/**
	 * Constructor.
	 *
	 * Registers the login_init hook at priority 1 for early execution.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'login_init', array( $this, 'intercept_login' ), 1 );
	}

	/**
	 * Intercept the login page request and render custom templates.
	 *
	 * Handles all login actions: login, lostpassword, resetpass, register.
	 * Processes form submissions, normalises action aliases, and renders
	 * the appropriate template.
	 *
	 * @since 1.0.0
	 */
	public function intercept_login() {
		// Check if custom login is enabled.
		if ( 'yes' !== get_option( 'rf_secure_login_enabled', 'yes' ) ) {
			return;
		}

		// Determine the current action.
		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : 'login';

		// Handle logout action.
		if ( 'logout' === $action ) {
			check_admin_referer( 'log-out' );
			wp_logout();

			$redirect_to = '';
			if ( isset( $_REQUEST['redirect_to'] ) ) {
				$redirect_to = sanitize_url( wp_unslash( $_REQUEST['redirect_to'] ) );
			}

			if ( $redirect_to ) {
				wp_safe_redirect( $redirect_to );
				exit;
			}

			wp_safe_redirect( wp_login_url() . '?loggedout=true' );
			exit;
		}

		// Handle confirmaction (email confirmation links).
		if ( 'confirmaction' === $action ) {
			return; // Let WordPress handle this natively.
		}

		// Return early for actions we don't customise.
		if ( ! in_array( $action, $this->custom_actions, true ) ) {
			return;
		}

		// Normalise action aliases.
		if ( 'retrievepassword' === $action ) {
			$action = 'lostpassword';
		}

		if ( 'rp' === $action ) {
			$action = 'resetpass';
		}

		// Check if registration is allowed.
		if ( 'register' === $action && ! get_option( 'users_can_register' ) ) {
			wp_safe_redirect( wp_login_url() );
			exit;
		}

		// Handle checkemail states (after lostpassword or register).
		if ( isset( $_GET['checkemail'] ) ) {
			$checkemail = sanitize_text_field( wp_unslash( $_GET['checkemail'] ) );
			if ( in_array( $checkemail, array( 'confirm', 'registered' ), true ) ) {
				$this->render_template( 'checkemail' );
				exit;
			}
		}

		// Handle password changed state.
		if ( isset( $_GET['password'] ) && 'changed' === sanitize_text_field( wp_unslash( $_GET['password'] ) ) ) {
			$action = 'login';
		}

		// Process lostpassword form submission.
		if ( 'lostpassword' === $action && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			check_admin_referer( 'lost_password', 'wp_nonce' );

			$errors = retrieve_password();

			if ( ! is_wp_error( $errors ) ) {
				wp_safe_redirect( wp_login_url() . '?checkemail=confirm' );
				exit;
			}

			// Errors will be passed to the template.
			$this->render_template( 'lostpassword', $errors );
			exit;
		}

		// Process resetpass form submission.
		if ( 'resetpass' === $action && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$rp_key   = isset( $_REQUEST['rp_key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['rp_key'] ) ) : '';
			$rp_login = isset( $_REQUEST['rp_login'] ) ? sanitize_user( wp_unslash( $_REQUEST['rp_login'] ) ) : '';

			check_admin_referer( 'reset_password', 'wp_nonce' );

			$user = check_password_reset_key( $rp_key, $rp_login );

			if ( is_wp_error( $user ) ) {
				$this->render_template( 'resetpass', $user );
				exit;
			}

			$pass1 = isset( $_POST['pass1'] ) ? wp_unslash( $_POST['pass1'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$pass2 = isset( $_POST['pass2'] ) ? wp_unslash( $_POST['pass2'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			$errors = new WP_Error();

			if ( empty( $pass1 ) || empty( $pass2 ) ) {
				$errors->add( 'password_reset_empty', __( '<strong>Error:</strong> Please enter a password.', 'rf-secure-login' ) );
			}

			if ( $pass1 !== $pass2 ) {
				$errors->add( 'password_reset_mismatch', __( '<strong>Error:</strong> The passwords do not match.', 'rf-secure-login' ) );
			}

			/**
			 * Fires before the password reset is processed.
			 *
			 * @param WP_Error $errors WP_Error object.
			 * @param WP_User  $user   The user whose password is being reset.
			 */
			do_action( 'validate_password_reset', $errors, $user );

			if ( $errors->has_errors() ) {
				$this->render_template( 'resetpass', $errors );
				exit;
			}

			reset_password( $user, $pass1 );

			wp_safe_redirect( wp_login_url() . '?password=changed' );
			exit;
		}

		// Process register form submission.
		if ( 'register' === $action && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			check_admin_referer( 'register', 'wp_nonce' );

			$user_login = isset( $_POST['user_login'] ) ? sanitize_user( wp_unslash( $_POST['user_login'] ) ) : '';
			$user_email = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';

			$errors = register_new_user( $user_login, $user_email );

			if ( ! is_wp_error( $errors ) ) {
				wp_safe_redirect( wp_login_url() . '?checkemail=registered' );
				exit;
			}

			$this->render_template( 'register', $errors );
			exit;
		}

		// Map action to template file.
		$template_map = array(
			'login'        => 'login',
			'lostpassword' => 'lostpassword',
			'resetpass'    => 'resetpass',
			'register'     => 'register',
		);

		$template = isset( $template_map[ $action ] ) ? $template_map[ $action ] : 'login';

		$this->render_template( $template );
		exit;
	}

	/**
	 * Enqueue login page assets.
	 *
	 * Loads Google Fonts, Flowbite CSS/JS, Tailwind CDN, custom CSS,
	 * and plugin JavaScript files. Localises script with configuration.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_assets() {
		$version = RF_SECURE_LOGIN_VERSION;
		$url     = RF_SECURE_LOGIN_URL;

		// Google Fonts — Orbitron + JetBrains Mono.
		wp_enqueue_style(
			'rf-google-fonts',
			'https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=JetBrains+Mono:wght@300;400;500;700&display=swap',
			array(),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- external resource
		);

		// Flowbite CSS.
		wp_enqueue_style(
			'rf-flowbite-css',
			'https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css',
			array(),
			'2.5.2'
		);

		// Custom login stylesheet.
		wp_enqueue_style(
			'rf-login-style',
			$url . 'assets/css/login-style.css',
			array( 'rf-flowbite-css' ),
			$version
		);

		// Tailwind CSS CDN (JIT engine loaded as script).
		wp_enqueue_script(
			'rf-tailwind-cdn',
			'https://cdn.tailwindcss.com?plugins=forms',
			array(),
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- external resource
			false // Load in head for Tailwind JIT.
		);

		// Flowbite JS.
		wp_enqueue_script(
			'rf-flowbite-js',
			'https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js',
			array(),
			'2.5.2',
			true
		);

		// Canvas animation.
		wp_enqueue_script(
			'rf-canvas-animation',
			$url . 'assets/js/canvas-animation.js',
			array(),
			$version,
			true
		);

		// Login form behaviour.
		wp_enqueue_script(
			'rf-login-form',
			$url . 'assets/js/login-form.js',
			array( 'rf-flowbite-js' ),
			$version,
			true
		);

		// Localise script with configuration data.
		$recaptcha_key = '';
		if ( class_exists( 'RF_Recaptcha' ) && RF_Recaptcha::is_enabled() ) {
			$recaptcha_key = get_option( 'rf_recaptcha_site_key', '' );
		}

		wp_localize_script(
			'rf-login-form',
			'rfSecureLogin',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'rf_login_nonce' ),
				'particleCount' => absint( get_option( 'rf_particle_count', 80 ) ),
				'recaptchaKey'  => sanitize_text_field( $recaptcha_key ),
				'loginUrl'      => wp_login_url(),
				'adminUrl'      => admin_url(),
				'i18n'          => array(
					'authenticating'    => __( 'Authenticating...', 'rf-secure-login' ),
					'verifying'         => __( 'Verifying...', 'rf-secure-login' ),
					'invalidCode'       => __( 'Invalid verification code. Please try again.', 'rf-secure-login' ),
					'tooManyAttempts'   => __( 'Too many failed attempts. Please log in again.', 'rf-secure-login' ),
					'networkError'      => __( 'A network error occurred. Please try again.', 'rf-secure-login' ),
					'enterCode'         => __( 'Enter the 6-digit code from your authenticator app.', 'rf-secure-login' ),
					'enterBackupCode'   => __( 'Enter one of your backup codes.', 'rf-secure-login' ),
					'signIn'            => __( 'Sign In', 'rf-secure-login' ),
					'sendResetLink'     => __( 'Send Reset Link', 'rf-secure-login' ),
					'register'          => __( 'Register', 'rf-secure-login' ),
					'passwordChanged'   => __( 'Your password has been changed. You may now log in.', 'rf-secure-login' ),
					'checkEmail'        => __( 'Check your email for the confirmation link.', 'rf-secure-login' ),
					'registrationEmail' => __( 'Registration complete. Check your email for your password.', 'rf-secure-login' ),
				),
			)
		);

		/**
		 * Fires after login page scripts and styles are enqueued.
		 *
		 * Third-party plugins can hook here to add their own assets.
		 */
		do_action( 'login_enqueue_scripts' );
	}

	/**
	 * Render a login page template with the full layout.
	 *
	 * Builds error and message strings from query parameters,
	 * applies WordPress login filters, determines the logo HTML,
	 * and includes the header, template, and footer files.
	 *
	 * @since 1.0.0
	 * @param string        $template Template name (without .php extension).
	 * @param WP_Error|null $errors   Optional WP_Error object with form errors.
	 */
	private function render_template( $template, $errors = null ) {
		if ( null === $errors ) {
			$errors = new WP_Error();
		}

		$message = '';

		// Handle query string messages.
		if ( isset( $_GET['loggedout'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['loggedout'] ) ) ) {
			$message = __( 'You are now logged out.', 'rf-secure-login' );
			// Clear any lingering auth cookies on logout.
			if ( ! is_user_logged_in() ) {
				$message = __( 'You are now logged out.', 'rf-secure-login' );
			}
		}

		if ( isset( $_GET['registration'] ) && 'disabled' === sanitize_text_field( wp_unslash( $_GET['registration'] ) ) ) {
			$errors->add( 'registerdisabled', __( 'User registration is currently not allowed.', 'rf-secure-login' ) );
		}

		if ( isset( $_GET['checkemail'] ) ) {
			$checkemail = sanitize_text_field( wp_unslash( $_GET['checkemail'] ) );
			if ( 'confirm' === $checkemail ) {
				$message = __( 'Check your email for the confirmation link, then visit the login page.', 'rf-secure-login' );
			} elseif ( 'registered' === $checkemail ) {
				$message = __( 'Registration complete. Please check your email.', 'rf-secure-login' );
			}
		}

		if ( isset( $_GET['password'] ) && 'changed' === sanitize_text_field( wp_unslash( $_GET['password'] ) ) ) {
			$message = __( 'Your password has been reset. You may now log in with your new password.', 'rf-secure-login' );
		}

		if ( isset( $_GET['action'] ) && 'lostpassword' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) && ! empty( $_GET['error'] ) ) {
			$lp_error = sanitize_text_field( wp_unslash( $_GET['error'] ) );
			if ( 'invalidkey' === $lp_error ) {
				$errors->add( 'invalidkey', __( 'Your password reset link appears to be invalid. Please request a new link below.', 'rf-secure-login' ) );
			} elseif ( 'expiredkey' === $lp_error ) {
				$errors->add( 'expiredkey', __( 'Your password reset link has expired. Please request a new link below.', 'rf-secure-login' ) );
			}
		}

		/**
		 * Filters the login page message.
		 *
		 * @param string $message The login message.
		 */
		$message = apply_filters( 'login_message', $message );

		// Get redirect_to URL.
		$redirect_to = isset( $_REQUEST['redirect_to'] ) ? sanitize_url( wp_unslash( $_REQUEST['redirect_to'] ) ) : admin_url();

		// Build body classes.
		$body_classes = array( 'rf-login-page', 'login', 'no-js' );

		/**
		 * Filters the login page body classes.
		 *
		 * @param array  $body_classes Array of body class strings.
		 * @param string $action       Current login action.
		 */
		$body_classes = apply_filters( 'login_body_class', $body_classes, $template );

		// Build the logo HTML.
		$logo_html = $this->get_logo_html();

		// Get error messages HTML.
		$error_messages = '';
		if ( $errors->has_errors() ) {
			$error_list = $errors->get_error_messages();
			foreach ( $error_list as $err ) {
				$error_messages .= '<div class="rf-error-message">' . wp_kses_post( $err ) . '</div>';
			}
		}

		// Enqueue assets.
		$this->enqueue_assets();

		// Page title.
		$page_titles = array(
			'login'        => __( 'Log In', 'rf-secure-login' ),
			'lostpassword' => __( 'Lost Password', 'rf-secure-login' ),
			'resetpass'    => __( 'Reset Password', 'rf-secure-login' ),
			'register'     => __( 'Registration', 'rf-secure-login' ),
			'checkemail'   => __( 'Check Email', 'rf-secure-login' ),
		);

		$page_title = isset( $page_titles[ $template ] ) ? $page_titles[ $template ] : __( 'Log In', 'rf-secure-login' );

		/**
		 * Filters the login page title.
		 *
		 * @param string $page_title The page title.
		 * @param string $action     The login action.
		 */
		$page_title = apply_filters( 'login_title', $page_title . ' &lsaquo; ' . get_bloginfo( 'name', 'display' ), $template );

		// Template path.
		$template_dir = RF_SECURE_LOGIN_DIR . 'templates/';

		// Include the layout and template.
		include $template_dir . 'layout-header.php';
		include $template_dir . $template . '.php';
		include $template_dir . 'layout-footer.php';
	}

	/**
	 * Get the logo HTML for the login page.
	 *
	 * Checks for a plugin-specific logo option first, then falls back
	 * to the theme's custom logo, and finally to a text-based logo
	 * using the site name in Orbitron font.
	 *
	 * @since  1.0.0
	 * @return string Logo HTML markup.
	 */
	private function get_logo_html() {
		// Check for plugin-specific login logo.
		$logo_id = get_option( 'rf_login_logo_id', 0 );
		if ( $logo_id ) {
			$logo_url = wp_get_attachment_image_url( $logo_id, 'medium' );
			if ( $logo_url ) {
				return sprintf(
					'<a href="%s" class="rf-logo-link"><img src="%s" alt="%s" class="rf-logo-img" /></a>',
					esc_url( home_url( '/' ) ),
					esc_url( $logo_url ),
					esc_attr( get_bloginfo( 'name' ) )
				);
			}
		}

		// Check for theme custom logo.
		if ( has_custom_logo() ) {
			$custom_logo = get_custom_logo();
			if ( $custom_logo ) {
				return '<div class="rf-logo-container rf-logo-custom-theme">' . $custom_logo . '</div>';
			}
		}

		// Text fallback in Orbitron font.
		return sprintf(
			'<a href="%s" class="rf-logo-text">%s</a>',
			esc_url( home_url( '/' ) ),
			esc_html( get_bloginfo( 'name' ) )
		);
	}
}
