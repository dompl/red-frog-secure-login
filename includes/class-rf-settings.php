<?php
/**
 * RF_Settings class.
 *
 * Registers and renders the admin settings page under
 * Settings > Secure Login. Uses the WordPress Settings API
 * for all option handling.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RF_Settings
 *
 * Creates the plugin settings page in the WordPress admin,
 * registers all settings fields, and handles rendering with
 * dark cyberpunk-themed UI using Tailwind and Flowbite.
 *
 * @since 1.0.0
 */
class RF_Settings {

	/**
	 * Settings page hook suffix.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $page_hook = '';

	/**
	 * Constructor.
	 *
	 * Registers admin hooks for the settings page.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the settings page under the Settings menu.
	 *
	 * @since 1.0.0
	 */
	public function register_menu() {
		$this->page_hook = add_options_page(
			__( 'Secure Login', 'rf-secure-login' ),
			__( 'Secure Login', 'rf-secure-login' ),
			'manage_options',
			'rf-secure-login',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register plugin settings with the WordPress Settings API.
	 *
	 * All options use the 'rf_secure_login_settings' group so they
	 * can be saved in a single form POST via settings_fields().
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {

		// Enable custom login page.
		register_setting( 'rf_secure_login_settings', 'rf_secure_login_enabled', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_yes_no' ),
			'default'           => 'yes',
		) );

		// Login page logo (attachment ID).
		register_setting( 'rf_secure_login_settings', 'rf_login_logo', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );

		// Background particle count.
		register_setting( 'rf_secure_login_settings', 'rf_particle_count', array(
			'type'              => 'integer',
			'sanitize_callback' => array( $this, 'sanitize_particle_count' ),
			'default'           => 80,
		) );

		// reCAPTCHA v3 enabled.
		register_setting( 'rf_secure_login_settings', 'rf_recaptcha_enabled', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_yes_no' ),
			'default'           => 'no',
		) );

		// reCAPTCHA site key.
		register_setting( 'rf_secure_login_settings', 'rf_recaptcha_site_key', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		// reCAPTCHA secret key.
		register_setting( 'rf_secure_login_settings', 'rf_recaptcha_secret_key', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		// reCAPTCHA threshold.
		register_setting( 'rf_secure_login_settings', 'rf_recaptcha_threshold', array(
			'type'              => 'number',
			'sanitize_callback' => array( $this, 'sanitize_threshold' ),
			'default'           => 0.5,
		) );

		// 2FA enforced roles.
		register_setting( 'rf_secure_login_settings', 'rf_2fa_enforced_roles', array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_roles' ),
			'default'           => array(),
		) );
	}

	/**
	 * Sanitize a yes/no toggle value.
	 *
	 * @since  1.0.0
	 * @param  mixed $value Raw input value.
	 * @return string 'yes' or 'no'.
	 */
	public function sanitize_yes_no( $value ) {
		return ( 'yes' === $value ) ? 'yes' : 'no';
	}

	/**
	 * Sanitize particle count, clamped between 20 and 120.
	 *
	 * @since  1.0.0
	 * @param  mixed $value Raw input value.
	 * @return int Clamped integer.
	 */
	public function sanitize_particle_count( $value ) {
		$count = absint( $value );
		return max( 20, min( 120, $count ) );
	}

	/**
	 * Sanitize reCAPTCHA threshold, clamped between 0.1 and 1.0.
	 *
	 * @since  1.0.0
	 * @param  mixed $value Raw input value.
	 * @return float Clamped float.
	 */
	public function sanitize_threshold( $value ) {
		$threshold = floatval( $value );
		return max( 0.1, min( 1.0, $threshold ) );
	}

	/**
	 * Sanitize the enforced roles array.
	 *
	 * @since  1.0.0
	 * @param  mixed $value Raw input value.
	 * @return array Sanitized array of role slugs.
	 */
	public function sanitize_roles( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_map( 'sanitize_text_field', $value );
	}

	/**
	 * Enqueue admin assets only on our settings page.
	 *
	 * @since 1.0.0
	 * @param string $hook The current admin page hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		if ( $hook !== $this->page_hook ) {
			return;
		}

		$plugin_url = RF_SECURE_LOGIN_URL;
		$version    = RF_SECURE_LOGIN_VERSION;

		// Google Fonts — Orbitron + JetBrains Mono.
		wp_enqueue_style(
			'rf-google-fonts-admin',
			'https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=JetBrains+Mono:wght@300;400;500;700&display=swap',
			array(),
			null
		);

		// Flowbite CSS.
		wp_enqueue_style(
			'rf-flowbite-css',
			'https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css',
			array(),
			null
		);

		// Flowbite JS.
		wp_enqueue_script(
			'rf-flowbite-js',
			'https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js',
			array(),
			null,
			true
		);

		// Tailwind CSS CDN (loaded as a script tag).
		wp_enqueue_script(
			'rf-tailwind-cdn',
			'https://cdn.tailwindcss.com?plugins=forms',
			array(),
			null,
			false
		);

		// WordPress media uploader.
		wp_enqueue_media();

		// Custom admin CSS.
		wp_enqueue_style(
			'rf-admin-settings-css',
			$plugin_url . 'assets/css/admin-settings.css',
			array( 'rf-flowbite-css' ),
			$version
		);

		// Custom admin JS.
		wp_enqueue_script(
			'rf-admin-settings-js',
			$plugin_url . 'assets/js/admin-settings.js',
			array( 'rf-flowbite-js' ),
			$version,
			true
		);

		// Get current logo URL if set.
		$logo_id  = absint( get_option( 'rf_login_logo', 0 ) );
		$logo_url = '';
		if ( $logo_id > 0 ) {
			$logo_src = wp_get_attachment_image_url( $logo_id, 'medium' );
			if ( $logo_src ) {
				$logo_url = $logo_src;
			}
		}

		// Localize script data.
		wp_localize_script( 'rf-admin-settings-js', 'rfAdminSettings', array(
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'rf_admin_nonce' ),
			'pluginUrl'  => $plugin_url,
			'currentLogo' => $logo_url,
		) );
	}

	/**
	 * Render the settings page.
	 *
	 * Checks capability and includes the template file.
	 *
	 * @since 1.0.0
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include RF_SECURE_LOGIN_DIR . 'templates/admin-settings-page.php';
	}
}
