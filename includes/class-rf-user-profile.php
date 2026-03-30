<?php
/**
 * RF_User_Profile class.
 *
 * Adds a two-factor authentication setup section to the
 * WordPress user profile page.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RF_User_Profile
 *
 * Renders 2FA setup/status UI on user profile pages and
 * handles the profile update save action.
 *
 * @since 1.0.0
 */
class RF_User_Profile {

	/**
	 * Constructor.
	 *
	 * Profile hooks will be registered in a later phase when
	 * the 2FA profile UI is implemented.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Profile hooks will be registered in Phase 8.
	}
}
