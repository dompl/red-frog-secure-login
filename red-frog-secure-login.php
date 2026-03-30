<?php
/**
 * Plugin Name: Red Frog Secure Login
 * Plugin URI: https://redfrogstudio.co.uk
 * Description: A stunning custom login screen with animated backgrounds and two-factor authentication.
 * Version: 1.0.0
 * Author: Dom Kapelewski
 * Author URI: https://redfrogstudio.co.uk
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rf-secure-login
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin constants.
 */
define( 'RF_SECURE_LOGIN_VERSION', '1.0.0' );
define( 'RF_SECURE_LOGIN_FILE', __FILE__ );
define( 'RF_SECURE_LOGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RF_SECURE_LOGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RF_SECURE_LOGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Include class files.
 */
require_once RF_SECURE_LOGIN_DIR . 'includes/class-rf-login-page.php';
require_once RF_SECURE_LOGIN_DIR . 'includes/class-rf-two-factor.php';
require_once RF_SECURE_LOGIN_DIR . 'includes/class-rf-backup-codes.php';
require_once RF_SECURE_LOGIN_DIR . 'includes/class-rf-settings.php';
require_once RF_SECURE_LOGIN_DIR . 'includes/class-rf-ajax-handler.php';
require_once RF_SECURE_LOGIN_DIR . 'includes/class-rf-user-profile.php';
require_once RF_SECURE_LOGIN_DIR . 'includes/class-rf-rate-limiter.php';
require_once RF_SECURE_LOGIN_DIR . 'includes/class-rf-recaptcha.php';
require_once RF_SECURE_LOGIN_DIR . 'includes/class-rf-github-updater.php';
require_once RF_SECURE_LOGIN_DIR . 'lib/class-rf-qrcode.php';

/**
 * Initialise plugin on plugins_loaded.
 *
 * Creates instances of all plugin classes so their hooks are registered.
 *
 * @since 1.0.0
 */
function rf_secure_login_init() {
	new RF_Login_Page();
	new RF_Two_Factor();
	new RF_Backup_Codes();
	new RF_Settings();
	new RF_Ajax_Handler();
	new RF_User_Profile();
	new RF_Rate_Limiter();
	new RF_Recaptcha();
	new RF_GitHub_Updater();
}
add_action( 'plugins_loaded', 'rf_secure_login_init' );

/**
 * Plugin activation hook.
 *
 * Sets default option values on first activation.
 *
 * @since 1.0.0
 */
function rf_secure_login_activate() {
	add_option( 'rf_secure_login_enabled', 'yes' );
	add_option( 'rf_particle_count', 80 );
	add_option( 'rf_recaptcha_enabled', 'no' );
	add_option( 'rf_recaptcha_threshold', 0.5 );
	add_option( 'rf_2fa_enforced_roles', array() );
}
register_activation_hook( __FILE__, 'rf_secure_login_activate' );

/**
 * Plugin deactivation hook.
 *
 * Cleans up transients on deactivation.
 *
 * @since 1.0.0
 */
function rf_secure_login_deactivate() {
	// Clean up any cached GitHub update check transient.
	delete_transient( '_rf_github_update_check' );
}
register_deactivation_hook( __FILE__, 'rf_secure_login_deactivate' );
