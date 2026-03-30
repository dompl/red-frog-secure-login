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
	}

	/**
	 * Register the settings page under the Settings menu.
	 *
	 * @since 1.0.0
	 */
	public function register_menu() {
		$this->page_hook = add_options_page(
			__( 'Secure Login Settings', 'rf-secure-login' ),
			__( 'Secure Login', 'rf-secure-login' ),
			'manage_options',
			'rf-secure-login',
			array( $this, 'render_page' )
		);

		if ( $this->page_hook ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		}
	}

	/**
	 * Enqueue admin assets only on the settings page.
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( $hook_suffix !== $this->page_hook ) {
			return;
		}

		// Stub — admin asset enqueuing will be implemented in Phase 7.
	}

	/**
	 * Register plugin settings with the WordPress Settings API.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		register_setting( 'rf_secure_login_options', 'rf_secure_login_enabled', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'yes',
		) );

		register_setting( 'rf_secure_login_options', 'rf_particle_count', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 80,
		) );

		register_setting( 'rf_secure_login_options', 'rf_recaptcha_enabled', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'no',
		) );

		register_setting( 'rf_secure_login_options', 'rf_recaptcha_threshold', array(
			'type'              => 'number',
			'sanitize_callback' => 'floatval',
			'default'           => 0.5,
		) );

		register_setting( 'rf_secure_login_options', 'rf_2fa_enforced_roles', array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_roles' ),
			'default'           => array(),
		) );
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
	 * Render the settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="wrap rf-settings-wrap">';
		echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
		echo '<p>' . esc_html__( 'Red Frog Secure Login settings will be configured here.', 'rf-secure-login' ) . '</p>';
		echo '</div>';
	}
}
