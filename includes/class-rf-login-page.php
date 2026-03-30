<?php
/**
 * RF_Login_Page class.
 *
 * Handles the custom login page rendering by hooking into
 * WordPress login customisation hooks to restyle wp-login.php.
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
 * Overrides the default WordPress login page appearance using
 * login hooks. Loads Tailwind, Flowbite, Google Fonts, and custom
 * assets. Renders the cyberpunk-themed login card with canvas animation.
 *
 * @since 1.0.0
 */
class RF_Login_Page {

	/**
	 * Constructor.
	 *
	 * Registers all login page hooks at priority 1 on login_init
	 * to ensure early execution.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'login_init', array( $this, 'init' ), 1 );
	}

	/**
	 * Initialise login page hooks.
	 *
	 * Checks if the custom login page is enabled before registering
	 * the rendering hooks.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		if ( 'yes' !== get_option( 'rf_secure_login_enabled', 'yes' ) ) {
			return;
		}

		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'login_head', array( $this, 'login_head' ) );
		add_filter( 'login_headerurl', array( $this, 'login_header_url' ) );
		add_filter( 'login_headertext', array( $this, 'login_header_text' ) );
		add_filter( 'login_body_class', array( $this, 'login_body_class' ) );
		add_action( 'login_message', array( $this, 'login_message' ) );
		add_action( 'login_form', array( $this, 'login_form' ) );
		add_action( 'login_footer', array( $this, 'login_footer' ) );
	}

	/**
	 * Enqueue login page assets.
	 *
	 * Loads Tailwind CDN, Flowbite CDN, Google Fonts, and plugin
	 * CSS/JS files on the login page.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_assets() {
		// Stub — asset enqueuing will be implemented in Phase 2.
	}

	/**
	 * Output custom styles and CSS variables in the login head.
	 *
	 * @since 1.0.0
	 */
	public function login_head() {
		// Stub — inline styles will be implemented in Phase 2.
	}

	/**
	 * Set the login header URL to the site URL.
	 *
	 * @since  1.0.0
	 * @return string Site URL.
	 */
	public function login_header_url() {
		return home_url( '/' );
	}

	/**
	 * Set the login header text to the site name.
	 *
	 * @since  1.0.0
	 * @return string Site name.
	 */
	public function login_header_text() {
		return get_bloginfo( 'name' );
	}

	/**
	 * Add custom body classes to the login page.
	 *
	 * @since  1.0.0
	 * @param  array $classes Existing body classes.
	 * @return array Modified body classes.
	 */
	public function login_body_class( $classes ) {
		$classes[] = 'rf-secure-login';
		return $classes;
	}

	/**
	 * Output custom login messages.
	 *
	 * @since  1.0.0
	 * @param  string $message Existing login message HTML.
	 * @return string Modified login message HTML.
	 */
	public function login_message( $message ) {
		// Stub — custom messages will be implemented in Phase 2.
		return $message;
	}

	/**
	 * Inject additional HTML into the login form.
	 *
	 * Adds the hidden 2FA container and any additional form fields.
	 *
	 * @since 1.0.0
	 */
	public function login_form() {
		// Stub — 2FA form container will be implemented in Phase 6.
	}

	/**
	 * Inject canvas element, footer text, and JS initialisation.
	 *
	 * @since 1.0.0
	 */
	public function login_footer() {
		// Stub — canvas and footer will be implemented in Phase 2/3.
	}
}
