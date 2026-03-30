<?php
/**
 * RF_Ajax_Handler class.
 *
 * Registers and handles all AJAX endpoints for the plugin,
 * including 2FA verification and settings management.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RF_Ajax_Handler
 *
 * Manages AJAX actions for login 2FA verification, user
 * profile 2FA setup/disable, and admin 2FA reset.
 *
 * @since 1.0.0
 */
class RF_Ajax_Handler {

	/**
	 * Constructor.
	 *
	 * AJAX hooks will be registered in a later phase when
	 * the handler methods are implemented.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// AJAX hooks will be registered in Phase 6.
	}
}
