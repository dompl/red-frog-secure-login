<?php
/**
 * Admin Settings Page Template.
 *
 * Dark cyberpunk-themed settings interface using Tailwind + Flowbite,
 * scoped to .rf-settings-wrap to avoid conflicts with WP admin.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Current option values.
$enabled          = get_option( 'rf_secure_login_enabled', 'yes' );
$login_logo       = absint( get_option( 'rf_login_logo', 0 ) );
$particle_count   = absint( get_option( 'rf_particle_count', 80 ) );
$recaptcha_on     = get_option( 'rf_recaptcha_enabled', 'no' );
$recaptcha_site   = get_option( 'rf_recaptcha_site_key', '' );
$recaptcha_secret = get_option( 'rf_recaptcha_secret_key', '' );
$recaptcha_thresh = floatval( get_option( 'rf_recaptcha_threshold', 0.5 ) );
$enforced_roles   = get_option( 'rf_2fa_enforced_roles', array() );
if ( ! is_array( $enforced_roles ) ) {
	$enforced_roles = array();
}

$plugin_url = RF_SECURE_LOGIN_URL;

// Logo preview URL.
$logo_url = '';
if ( $login_logo > 0 ) {
	$logo_src = wp_get_attachment_image_url( $login_logo, 'medium' );
	if ( $logo_src ) {
		$logo_url = $logo_src;
	}
}

// All editable roles.
$all_roles = wp_roles()->get_names();
?>

<style>
	/* CSS variables for the settings page — identical palette to login page */
	.rf-settings-wrap {
		--bg-primary: #0a0a0f;
		--bg-secondary: #12121a;
		--bg-tertiary: #1a1a2e;
		--accent-primary: #00ff88;
		--accent-secondary: #00d4ff;
		--accent-warn: #ff3366;
		--accent-glow: #00ff8833;
		--text-primary: #e0e0e0;
		--text-muted: #6b7280;
		--text-bright: #ffffff;
		--border-subtle: #1f2937;
		--border-focus: #00ff88;
	}
</style>

<div class="rf-settings-wrap" style="margin-left: -20px;">

	<!-- ================================================================
	     Header
	     ================================================================ -->
	<div class="rf-settings-header">
		<img
			src="<?php echo esc_url( $plugin_url . 'assets/images/red-frog-logo.png' ); ?>"
			alt="<?php esc_attr_e( 'Red Frog Studio', 'rf-secure-login' ); ?>"
			class="rf-header-logo"
		/>
		<h1 class="rf-header-title">
			<?php esc_html_e( 'Red Frog Secure Login', 'rf-secure-login' ); ?>
		</h1>
		<span class="rf-header-version">
			v<?php echo esc_html( RF_SECURE_LOGIN_VERSION ); ?>
		</span>
	</div>

	<!-- ================================================================
	     Tabs
	     ================================================================ -->
	<div class="rf-tabs" role="tablist">
		<button class="rf-tab active" data-tab="general" role="tab" aria-selected="true" aria-controls="tab-general">
			<svg class="rf-tab-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
			<?php esc_html_e( 'General', 'rf-secure-login' ); ?>
		</button>
		<button class="rf-tab" data-tab="security" role="tab" aria-selected="false" aria-controls="tab-security">
			<svg class="rf-tab-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/></svg>
			<?php esc_html_e( 'Security', 'rf-secure-login' ); ?>
		</button>
		<button class="rf-tab" data-tab="users" role="tab" aria-selected="false" aria-controls="tab-users">
			<svg class="rf-tab-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
			<?php esc_html_e( 'Users', 'rf-secure-login' ); ?>
		</button>
		<button class="rf-tab" data-tab="help" role="tab" aria-selected="false" aria-controls="tab-help">
			<svg class="rf-tab-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
			<?php esc_html_e( 'Help', 'rf-secure-login' ); ?>
		</button>
	</div>

	<!-- ================================================================
	     Settings saved notice
	     ================================================================ -->
	<?php if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="rf-notice rf-notice-success" role="alert">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
			<?php esc_html_e( 'Settings saved successfully.', 'rf-secure-login' ); ?>
		</div>
	<?php endif; ?>

	<!-- ================================================================
	     Form: General + Security tabs
	     ================================================================ -->
	<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" id="rf-settings-form">
		<?php settings_fields( 'rf_secure_login_settings' ); ?>

		<!-- ============================================================
		     General Tab
		     ============================================================ -->
		<div class="rf-tab-content active" id="tab-general" role="tabpanel">

			<!-- Enable Custom Login -->
			<div class="rf-setting-card">
				<div class="rf-setting-card-header">
					<h2 class="rf-section-title">
						<?php esc_html_e( 'Custom Login Page', 'rf-secure-login' ); ?>
					</h2>
					<p class="rf-section-desc">
						<?php esc_html_e( 'Replace the default WordPress login screen with the Red Frog cyberpunk terminal interface.', 'rf-secure-login' ); ?>
					</p>
				</div>

				<div class="rf-setting-row">
					<div class="rf-setting-label-group">
						<label for="rf_secure_login_enabled" class="rf-setting-label">
							<?php esc_html_e( 'Enable Custom Login', 'rf-secure-login' ); ?>
						</label>
						<span class="rf-setting-hint">
							<?php esc_html_e( 'When disabled, the standard WordPress login page will be used.', 'rf-secure-login' ); ?>
						</span>
					</div>
					<label class="rf-toggle-switch">
						<input
							type="hidden"
							name="rf_secure_login_enabled"
							value="no"
						/>
						<input
							type="checkbox"
							id="rf_secure_login_enabled"
							name="rf_secure_login_enabled"
							value="yes"
							class="sr-only peer"
							<?php checked( $enabled, 'yes' ); ?>
						/>
						<div class="rf-toggle-track">
							<div class="rf-toggle-thumb"></div>
						</div>
					</label>
				</div>
			</div>

			<!-- Login Logo -->
			<div class="rf-setting-card">
				<div class="rf-setting-card-header">
					<h2 class="rf-section-title">
						<?php esc_html_e( 'Login Page Logo', 'rf-secure-login' ); ?>
					</h2>
					<p class="rf-section-desc">
						<?php esc_html_e( 'Upload a custom logo for the login page. If not set, the site name will be displayed.', 'rf-secure-login' ); ?>
					</p>
				</div>

				<div class="rf-setting-row rf-setting-row-logo">
					<div class="rf-logo-preview" id="rf-logo-preview">
						<?php if ( $logo_url ) : ?>
							<img
								src="<?php echo esc_url( $logo_url ); ?>"
								alt="<?php esc_attr_e( 'Login logo preview', 'rf-secure-login' ); ?>"
								id="rf-logo-preview-img"
							/>
						<?php else : ?>
							<div class="rf-logo-placeholder" id="rf-logo-placeholder">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="32" height="32"><path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 012.25-2.25h16.5A2.25 2.25 0 0122.5 6v12a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 18V6zM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0021 18v-1.94l-2.69-2.689a1.5 1.5 0 00-2.12 0l-.88.879.97.97a.75.75 0 11-1.06 1.06l-5.16-5.159a1.5 1.5 0 00-2.12 0L3 16.061zm10.125-7.81a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0z" clip-rule="evenodd"/></svg>
								<span><?php esc_html_e( 'No logo selected', 'rf-secure-login' ); ?></span>
							</div>
						<?php endif; ?>
					</div>
					<input
						type="hidden"
						name="rf_login_logo"
						id="rf_login_logo"
						value="<?php echo esc_attr( $login_logo ); ?>"
					/>
					<div class="rf-logo-buttons">
						<button type="button" class="rf-btn-secondary" id="rf-upload-logo-btn">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M9.25 13.25a.75.75 0 001.5 0V4.636l2.955 3.129a.75.75 0 001.09-1.03l-4.25-4.5a.75.75 0 00-1.09 0l-4.25 4.5a.75.75 0 101.09 1.03L9.25 4.636v8.614z"/><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"/></svg>
							<?php esc_html_e( 'Upload Logo', 'rf-secure-login' ); ?>
						</button>
						<button type="button" class="rf-btn-ghost" id="rf-remove-logo-btn" <?php echo ( ! $logo_url ) ? 'style="display:none;"' : ''; ?>>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd"/></svg>
							<?php esc_html_e( 'Remove', 'rf-secure-login' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- Particle Count -->
			<div class="rf-setting-card">
				<div class="rf-setting-card-header">
					<h2 class="rf-section-title">
						<?php esc_html_e( 'Background Animation', 'rf-secure-login' ); ?>
					</h2>
					<p class="rf-section-desc">
						<?php esc_html_e( 'Control the density of the particle network animation on the login page.', 'rf-secure-login' ); ?>
					</p>
				</div>

				<div class="rf-setting-row">
					<div class="rf-setting-label-group">
						<label for="rf_particle_count" class="rf-setting-label">
							<?php esc_html_e( 'Particle Count', 'rf-secure-login' ); ?>
						</label>
						<span class="rf-setting-hint">
							<?php esc_html_e( 'Higher values create a denser network. Lower values improve performance on slower devices.', 'rf-secure-login' ); ?>
						</span>
					</div>
					<div class="rf-range-group">
						<input
							type="range"
							id="rf_particle_count"
							name="rf_particle_count"
							min="20"
							max="120"
							step="5"
							value="<?php echo esc_attr( $particle_count ); ?>"
							class="rf-range-input"
						/>
						<span class="rf-range-value" id="rf-particle-value">
							<?php echo esc_html( $particle_count ); ?>
						</span>
					</div>
				</div>
			</div>

		</div><!-- #tab-general -->

		<!-- ============================================================
		     Security Tab
		     ============================================================ -->
		<div class="rf-tab-content" id="tab-security" style="display: none;" role="tabpanel">

			<!-- reCAPTCHA v3 -->
			<div class="rf-setting-card">
				<div class="rf-setting-card-header">
					<h2 class="rf-section-title">
						<?php esc_html_e( 'reCAPTCHA v3', 'rf-secure-login' ); ?>
					</h2>
					<p class="rf-section-desc">
						<?php esc_html_e( 'Protect the login page from bots using Google reCAPTCHA v3. Runs invisibly in the background.', 'rf-secure-login' ); ?>
					</p>
				</div>

				<!-- Enable reCAPTCHA toggle -->
				<div class="rf-setting-row">
					<div class="rf-setting-label-group">
						<label for="rf_recaptcha_enabled" class="rf-setting-label">
							<?php esc_html_e( 'Enable reCAPTCHA', 'rf-secure-login' ); ?>
						</label>
						<span class="rf-setting-hint">
							<?php esc_html_e( 'You will need a reCAPTCHA v3 site key and secret key from Google.', 'rf-secure-login' ); ?>
						</span>
					</div>
					<label class="rf-toggle-switch">
						<input
							type="hidden"
							name="rf_recaptcha_enabled"
							value="no"
						/>
						<input
							type="checkbox"
							id="rf_recaptcha_enabled"
							name="rf_recaptcha_enabled"
							value="yes"
							class="sr-only peer"
							<?php checked( $recaptcha_on, 'yes' ); ?>
						/>
						<div class="rf-toggle-track">
							<div class="rf-toggle-thumb"></div>
						</div>
					</label>
				</div>

				<!-- reCAPTCHA keys (greyed out when disabled) -->
				<div class="rf-recaptcha-fields" id="rf-recaptcha-fields" <?php echo ( 'yes' !== $recaptcha_on ) ? 'data-disabled="true"' : ''; ?>>
					<div class="rf-setting-row rf-setting-row-input">
						<label for="rf_recaptcha_site_key" class="rf-setting-label">
							<?php esc_html_e( 'Site Key', 'rf-secure-login' ); ?>
						</label>
						<input
							type="text"
							id="rf_recaptcha_site_key"
							name="rf_recaptcha_site_key"
							value="<?php echo esc_attr( $recaptcha_site ); ?>"
							class="rf-input-admin"
							placeholder="6Le..."
							<?php echo ( 'yes' !== $recaptcha_on ) ? 'disabled' : ''; ?>
						/>
					</div>

					<div class="rf-setting-row rf-setting-row-input">
						<label for="rf_recaptcha_secret_key" class="rf-setting-label">
							<?php esc_html_e( 'Secret Key', 'rf-secure-login' ); ?>
						</label>
						<input
							type="password"
							id="rf_recaptcha_secret_key"
							name="rf_recaptcha_secret_key"
							value="<?php echo esc_attr( $recaptcha_secret ); ?>"
							class="rf-input-admin"
							placeholder="6Le..."
							autocomplete="off"
							<?php echo ( 'yes' !== $recaptcha_on ) ? 'disabled' : ''; ?>
						/>
					</div>

					<div class="rf-setting-row">
						<div class="rf-setting-label-group">
							<label for="rf_recaptcha_threshold" class="rf-setting-label">
								<?php esc_html_e( 'Score Threshold', 'rf-secure-login' ); ?>
							</label>
							<span class="rf-setting-hint">
								<?php esc_html_e( 'Scores below this value will be blocked. Higher = stricter. 0.5 is recommended.', 'rf-secure-login' ); ?>
							</span>
						</div>
						<div class="rf-range-group">
							<input
								type="range"
								id="rf_recaptcha_threshold"
								name="rf_recaptcha_threshold"
								min="0.1"
								max="1.0"
								step="0.1"
								value="<?php echo esc_attr( $recaptcha_thresh ); ?>"
								class="rf-range-input"
								<?php echo ( 'yes' !== $recaptcha_on ) ? 'disabled' : ''; ?>
							/>
							<span class="rf-range-value" id="rf-threshold-value">
								<?php echo esc_html( number_format( $recaptcha_thresh, 1 ) ); ?>
							</span>
						</div>
					</div>
				</div>
			</div>

			<!-- 2FA Enforcement -->
			<div class="rf-setting-card">
				<div class="rf-setting-card-header">
					<h2 class="rf-section-title">
						<?php esc_html_e( 'Two-Factor Authentication', 'rf-secure-login' ); ?>
					</h2>
					<p class="rf-section-desc">
						<?php esc_html_e( 'Require specific user roles to set up TOTP-based two-factor authentication. Users with enforced roles will be prompted to configure an authenticator app.', 'rf-secure-login' ); ?>
					</p>
				</div>

				<div class="rf-setting-row rf-setting-row-roles">
					<label class="rf-setting-label">
						<?php esc_html_e( 'Enforce 2FA for Roles', 'rf-secure-login' ); ?>
					</label>
					<div class="rf-roles-grid">
						<?php foreach ( $all_roles as $role_slug => $role_name ) : ?>
							<label class="rf-role-checkbox">
								<input
									type="checkbox"
									name="rf_2fa_enforced_roles[]"
									value="<?php echo esc_attr( $role_slug ); ?>"
									<?php checked( in_array( $role_slug, $enforced_roles, true ) ); ?>
								/>
								<span class="rf-role-checkmark">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
								</span>
								<span class="rf-role-name"><?php echo esc_html( translate_user_role( $role_name ) ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

		</div><!-- #tab-security -->

		<!-- Save Button (visible on General + Security tabs) -->
		<div class="rf-save-bar" id="rf-save-bar">
			<button type="submit" class="rf-btn-save">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
				<?php esc_html_e( 'Save Settings', 'rf-secure-login' ); ?>
			</button>
		</div>

	</form>

	<!-- ================================================================
	     Users Tab (outside form — AJAX driven)
	     ================================================================ -->
	<div class="rf-tab-content" id="tab-users" style="display: none;" role="tabpanel">

		<div class="rf-setting-card">
			<div class="rf-setting-card-header">
				<h2 class="rf-section-title">
					<?php esc_html_e( 'User 2FA Status', 'rf-secure-login' ); ?>
				</h2>
				<p class="rf-section-desc">
					<?php esc_html_e( 'View and manage two-factor authentication status for all users. You can reset a user\'s 2FA or regenerate their backup codes.', 'rf-secure-login' ); ?>
				</p>
			</div>

			<!-- Search -->
			<div class="rf-users-search-row">
				<div class="rf-search-input-wrapper">
					<svg class="rf-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd"/></svg>
					<input
						type="text"
						id="rf-user-search"
						class="rf-input-admin rf-search-input"
						placeholder="<?php esc_attr_e( 'Search users...', 'rf-secure-login' ); ?>"
					/>
				</div>
			</div>

			<!-- Users table -->
			<div class="rf-users-table-wrap" id="rf-users-table-wrap">
				<table class="rf-users-table" id="rf-users-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Username', 'rf-secure-login' ); ?></th>
							<th><?php esc_html_e( 'Email', 'rf-secure-login' ); ?></th>
							<th><?php esc_html_e( 'Role', 'rf-secure-login' ); ?></th>
							<th><?php esc_html_e( '2FA Status', 'rf-secure-login' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'rf-secure-login' ); ?></th>
						</tr>
					</thead>
					<tbody id="rf-users-tbody">
						<tr>
							<td colspan="5" class="rf-table-loading">
								<div class="rf-loading-spinner"></div>
								<?php esc_html_e( 'Loading users...', 'rf-secure-login' ); ?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

	</div><!-- #tab-users -->

	<!-- ================================================================
	     Help Tab
	     ================================================================ -->
	<div class="rf-tab-content" id="tab-help" style="display: none;" role="tabpanel">

		<div class="rf-setting-card">
			<div class="rf-setting-card-header">
				<h2 class="rf-section-title">
					<?php esc_html_e( 'Documentation & Help', 'rf-secure-login' ); ?>
				</h2>
				<p class="rf-section-desc">
					<?php esc_html_e( 'Everything you need to get started with Red Frog Secure Login.', 'rf-secure-login' ); ?>
				</p>
			</div>

			<!-- Accordion -->
			<div class="rf-accordion" id="rf-help-accordion">

				<!-- Getting Started -->
				<div class="rf-accordion-item">
					<button class="rf-accordion-trigger active" data-accordion="help-getting-started" aria-expanded="true">
						<span class="rf-accordion-title">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M3 4.25A2.25 2.25 0 015.25 2h5.5A2.25 2.25 0 0113 4.25v2a.75.75 0 01-1.5 0v-2a.75.75 0 00-.75-.75h-5.5a.75.75 0 00-.75.75v11.5c0 .414.336.75.75.75h5.5a.75.75 0 00.75-.75v-2a.75.75 0 011.5 0v2A2.25 2.25 0 0110.75 18h-5.5A2.25 2.25 0 013 15.75V4.25z" clip-rule="evenodd"/><path fill-rule="evenodd" d="M6 10a.75.75 0 01.75-.75h9.546l-1.048-.943a.75.75 0 111.004-1.114l2.5 2.25a.75.75 0 010 1.114l-2.5 2.25a.75.75 0 11-1.004-1.114l1.048-.943H6.75A.75.75 0 016 10z" clip-rule="evenodd"/></svg>
							<?php esc_html_e( 'Getting Started', 'rf-secure-login' ); ?>
						</span>
						<svg class="rf-accordion-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
					</button>
					<div class="rf-accordion-body active" id="help-getting-started">
						<p><?php esc_html_e( 'Red Frog Secure Login replaces the default WordPress login page with a cyberpunk-themed secure login screen. Here is how to get up and running:', 'rf-secure-login' ); ?></p>
						<ol>
							<li><?php esc_html_e( 'Make sure the "Enable Custom Login" toggle is switched on in the General tab. This is enabled by default.', 'rf-secure-login' ); ?></li>
							<li><?php esc_html_e( 'Visit your site login page (usually /wp-login.php) to see the new design immediately.', 'rf-secure-login' ); ?></li>
							<li><?php esc_html_e( 'Optionally upload a custom logo in the General tab. If no logo is set, your site name will be displayed.', 'rf-secure-login' ); ?></li>
							<li><?php esc_html_e( 'All standard WordPress login features continue to work: password reset, registration (if enabled), and all redirect parameters.', 'rf-secure-login' ); ?></li>
						</ol>
						<p><?php esc_html_e( 'If you ever need to disable the custom login page (for troubleshooting, for example), simply switch the toggle off and the default WordPress login will return.', 'rf-secure-login' ); ?></p>
					</div>
				</div>

				<!-- reCAPTCHA Setup -->
				<div class="rf-accordion-item">
					<button class="rf-accordion-trigger" data-accordion="help-recaptcha" aria-expanded="false">
						<span class="rf-accordion-title">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/></svg>
							<?php esc_html_e( 'reCAPTCHA v3 Setup', 'rf-secure-login' ); ?>
						</span>
						<svg class="rf-accordion-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
					</button>
					<div class="rf-accordion-body" id="help-recaptcha" style="display: none;">
						<p><?php esc_html_e( 'reCAPTCHA v3 protects your login page from automated attacks without requiring users to solve puzzles. It runs invisibly in the background and assigns a score to each login attempt.', 'rf-secure-login' ); ?></p>
						<h4><?php esc_html_e( 'How to set it up:', 'rf-secure-login' ); ?></h4>
						<ol>
							<li><?php echo wp_kses_post( __( 'Go to <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener noreferrer">Google reCAPTCHA Admin Console</a> and sign in with your Google account.', 'rf-secure-login' ) ); ?></li>
							<li><?php esc_html_e( 'Click "+" to create a new site. Choose "reCAPTCHA v3" as the type.', 'rf-secure-login' ); ?></li>
							<li><?php esc_html_e( 'Add your website domain(s) and submit the form.', 'rf-secure-login' ); ?></li>
							<li><?php esc_html_e( 'Copy the Site Key and Secret Key provided by Google.', 'rf-secure-login' ); ?></li>
							<li><?php esc_html_e( 'Paste them into the Security tab here, enable the toggle, and save.', 'rf-secure-login' ); ?></li>
						</ol>
						<h4><?php esc_html_e( 'About the score threshold:', 'rf-secure-login' ); ?></h4>
						<p><?php esc_html_e( 'Google assigns a score between 0.0 (likely a bot) and 1.0 (likely a human) to each request. The threshold setting controls what score a user must achieve. A value of 0.5 works well for most sites. Increase it if you are seeing spam; decrease it if legitimate users are being blocked.', 'rf-secure-login' ); ?></p>
					</div>
				</div>

				<!-- Two-Factor Authentication -->
				<div class="rf-accordion-item">
					<button class="rf-accordion-trigger" data-accordion="help-2fa" aria-expanded="false">
						<span class="rf-accordion-title">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path d="M10 1a.75.75 0 01.75.75v1.5a.75.75 0 01-1.5 0v-1.5A.75.75 0 0110 1zM5.05 3.05a.75.75 0 011.06 0l1.062 1.06A.75.75 0 116.11 5.173L5.05 4.11a.75.75 0 010-1.06zm9.9 0a.75.75 0 010 1.06l-1.06 1.062a.75.75 0 01-1.062-1.061l1.061-1.06a.75.75 0 011.06 0zM3 8a7 7 0 1114 0A7 7 0 013 8zm4-1a.75.75 0 000 1.5h1.756l-1.96 2.614A.75.75 0 007.5 12h3a.75.75 0 000-1.5H8.744l1.96-2.614A.75.75 0 0010.5 7H7z"/></svg>
							<?php esc_html_e( 'Two-Factor Authentication', 'rf-secure-login' ); ?>
						</span>
						<svg class="rf-accordion-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
					</button>
					<div class="rf-accordion-body" id="help-2fa" style="display: none;">
						<p><?php esc_html_e( 'Two-factor authentication (2FA) adds an extra security layer to user accounts. After entering their password, users must also provide a 6-digit code from an authenticator app on their phone.', 'rf-secure-login' ); ?></p>
						<h4><?php esc_html_e( 'Supported authenticator apps:', 'rf-secure-login' ); ?></h4>
						<ul>
							<li><?php esc_html_e( 'Google Authenticator (Android / iOS)', 'rf-secure-login' ); ?></li>
							<li><?php esc_html_e( 'Microsoft Authenticator (Android / iOS)', 'rf-secure-login' ); ?></li>
							<li><?php esc_html_e( 'Authy (Android / iOS / Desktop)', 'rf-secure-login' ); ?></li>
							<li><?php esc_html_e( '1Password, Bitwarden, or any TOTP-compatible app', 'rf-secure-login' ); ?></li>
						</ul>
						<h4><?php esc_html_e( 'How users set up 2FA:', 'rf-secure-login' ); ?></h4>
						<ol>
							<li><?php esc_html_e( 'Each user goes to their Profile page in the WordPress admin.', 'rf-secure-login' ); ?></li>
							<li><?php esc_html_e( 'They click "Set Up Two-Factor Authentication" and scan the QR code with their authenticator app.', 'rf-secure-login' ); ?></li>
							<li><?php esc_html_e( 'They enter the 6-digit verification code to confirm the setup.', 'rf-secure-login' ); ?></li>
							<li><?php esc_html_e( 'From that point on, they will need their authenticator app code each time they log in.', 'rf-secure-login' ); ?></li>
						</ol>
						<h4><?php esc_html_e( 'Enforcing 2FA for specific roles:', 'rf-secure-login' ); ?></h4>
						<p><?php esc_html_e( 'In the Security tab, you can tick the roles that should be required to use 2FA. Users with those roles will be prompted to set up an authenticator app the next time they visit their profile page. Until they do, their account will not have the additional protection.', 'rf-secure-login' ); ?></p>
					</div>
				</div>

				<!-- Backup Codes -->
				<div class="rf-accordion-item">
					<button class="rf-accordion-trigger" data-accordion="help-backup" aria-expanded="false">
						<span class="rf-accordion-title">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M15.621 4.379a3 3 0 00-4.242 0l-7 7a3 3 0 004.241 4.243h.001l.497-.5a.75.75 0 011.064 1.057l-.498.501a4.5 4.5 0 01-6.364-6.364l7-7a4.5 4.5 0 016.368 6.36l-3.455 3.553A2.625 2.625 0 119.52 9.52l3.45-3.451a.75.75 0 111.061 1.06l-3.45 3.451a1.125 1.125 0 001.587 1.595l3.454-3.553a3 3 0 000-4.242z" clip-rule="evenodd"/></svg>
							<?php esc_html_e( 'Backup Codes', 'rf-secure-login' ); ?>
						</span>
						<svg class="rf-accordion-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
					</button>
					<div class="rf-accordion-body" id="help-backup" style="display: none;">
						<p><?php esc_html_e( 'Backup codes are one-time-use codes that let a user log in if they lose access to their authenticator app (for example, if their phone is lost or broken).', 'rf-secure-login' ); ?></p>
						<h4><?php esc_html_e( 'How they work:', 'rf-secure-login' ); ?></h4>
						<ul>
							<li><?php esc_html_e( 'When a user sets up 2FA, they receive a set of backup codes. They should save these in a safe place.', 'rf-secure-login' ); ?></li>
							<li><?php esc_html_e( 'Each code can only be used once. Once used, it is permanently deleted.', 'rf-secure-login' ); ?></li>
							<li><?php esc_html_e( 'On the login page, users can enter a backup code instead of a TOTP code from their authenticator app.', 'rf-secure-login' ); ?></li>
						</ul>
						<h4><?php esc_html_e( 'As an administrator:', 'rf-secure-login' ); ?></h4>
						<p><?php esc_html_e( 'You can regenerate backup codes for any user from the Users tab on this page. This will invalidate all their existing codes and generate a new set. The user will need to save the new codes. Use this if a user reports they have run out of backup codes or suspect their codes have been compromised.', 'rf-secure-login' ); ?></p>
					</div>
				</div>

				<!-- Troubleshooting -->
				<div class="rf-accordion-item">
					<button class="rf-accordion-trigger" data-accordion="help-troubleshoot" aria-expanded="false">
						<span class="rf-accordion-title">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
							<?php esc_html_e( 'Troubleshooting', 'rf-secure-login' ); ?>
						</span>
						<svg class="rf-accordion-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
					</button>
					<div class="rf-accordion-body" id="help-troubleshoot" style="display: none;">
						<h4><?php esc_html_e( 'The custom login page is not appearing', 'rf-secure-login' ); ?></h4>
						<p><?php esc_html_e( 'Check that the "Enable Custom Login" toggle is on in the General tab. Also verify no other plugin is overriding the login page. Some caching plugins may cache the login page; try clearing your cache.', 'rf-secure-login' ); ?></p>

						<h4><?php esc_html_e( 'A user is locked out because of 2FA', 'rf-secure-login' ); ?></h4>
						<p><?php esc_html_e( 'Go to the Users tab on this page and click "Reset 2FA" next to the affected user. This removes their 2FA requirement so they can log in with just their password. They can then set up 2FA again from their profile.', 'rf-secure-login' ); ?></p>

						<h4><?php esc_html_e( '2FA codes from the authenticator app are not working', 'rf-secure-login' ); ?></h4>
						<p><?php esc_html_e( 'This is usually caused by a clock difference between the server and the user\'s phone. Make sure the phone\'s time is set to automatic. TOTP codes are time-based and even a 30-second drift can cause failures. The plugin allows a small window of tolerance, but significant clock skew will cause issues.', 'rf-secure-login' ); ?></p>

						<h4><?php esc_html_e( 'reCAPTCHA is blocking legitimate users', 'rf-secure-login' ); ?></h4>
						<p><?php esc_html_e( 'Try lowering the score threshold in the Security tab. A value of 0.3 is quite lenient. If the issue persists, temporarily disable reCAPTCHA and check your Google reCAPTCHA admin console for analytics on blocked requests.', 'rf-secure-login' ); ?></p>

						<h4><?php esc_html_e( 'I need to completely disable the plugin', 'rf-secure-login' ); ?></h4>
						<p><?php esc_html_e( 'If you cannot access the admin, you can deactivate the plugin via FTP by renaming the plugin folder. Navigate to /wp-content/plugins/ and rename "red-frog-secure-login" to something like "red-frog-secure-login-disabled". This will immediately deactivate the plugin and restore the default WordPress login.', 'rf-secure-login' ); ?></p>
					</div>
				</div>

				<!-- Customisation -->
				<div class="rf-accordion-item">
					<button class="rf-accordion-trigger" data-accordion="help-custom" aria-expanded="false">
						<span class="rf-accordion-title">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M1 5.25A2.25 2.25 0 013.25 3h13.5A2.25 2.25 0 0119 5.25v9.5A2.25 2.25 0 0116.75 17H3.25A2.25 2.25 0 011 14.75v-9.5zm1.5 5.81v3.69c0 .414.336.75.75.75h13.5a.75.75 0 00.75-.75v-2.69l-2.22-2.219a.75.75 0 00-1.06 0l-1.91 1.909.47.47a.75.75 0 11-1.06 1.06L6.53 8.091a.75.75 0 00-1.06 0L2.5 11.06zm10.22-3.03a.75.75 0 011.06 0l2.22 2.22V5.25a.75.75 0 00-.75-.75H3.25a.75.75 0 00-.75.75v4.69l2.22-2.22a2.25 2.25 0 013.18 0l2.97 2.97 1.91-1.909a2.25 2.25 0 013.18 0z" clip-rule="evenodd"/></svg>
							<?php esc_html_e( 'Customisation', 'rf-secure-login' ); ?>
						</span>
						<svg class="rf-accordion-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
					</button>
					<div class="rf-accordion-body" id="help-custom" style="display: none;">
						<h4><?php esc_html_e( 'Custom logo', 'rf-secure-login' ); ?></h4>
						<p><?php esc_html_e( 'Upload a logo in the General tab. For best results, use a PNG with a transparent background, no wider than 300 pixels. The logo will be displayed centred above the login form.', 'rf-secure-login' ); ?></p>

						<h4><?php esc_html_e( 'Particle animation', 'rf-secure-login' ); ?></h4>
						<p><?php esc_html_e( 'The background particle count can be adjusted from 20 to 120. Lower values are better for older devices. If a visitor has "reduced motion" enabled in their browser or operating system settings, the animation will be automatically disabled to respect their preference.', 'rf-secure-login' ); ?></p>

						<h4><?php esc_html_e( 'Advanced styling', 'rf-secure-login' ); ?></h4>
						<p><?php esc_html_e( 'Developers can override the login page styles by adding custom CSS to their theme. The login page uses CSS variables (e.g. --accent-primary, --bg-primary) which can be overridden in a child theme or via the WordPress Customizer\'s "Additional CSS" panel. Target the body.rf-login-page selector for specificity.', 'rf-secure-login' ); ?></p>
					</div>
				</div>

			</div><!-- .rf-accordion -->
		</div>
	</div><!-- #tab-help -->

	<!-- ================================================================
	     Reset 2FA Confirmation Modal
	     ================================================================ -->
	<div class="rf-modal-overlay" id="rf-reset-modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="rf-modal-title">
		<div class="rf-modal">
			<div class="rf-modal-header">
				<h3 class="rf-modal-title" id="rf-modal-title">
					<?php esc_html_e( 'Reset Two-Factor Authentication', 'rf-secure-login' ); ?>
				</h3>
				<button type="button" class="rf-modal-close" id="rf-modal-close" aria-label="<?php esc_attr_e( 'Close', 'rf-secure-login' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>
				</button>
			</div>
			<div class="rf-modal-body">
				<p><?php esc_html_e( 'This will remove 2FA for the selected user. They will need to set it up again from their profile page. This action cannot be undone.', 'rf-secure-login' ); ?></p>
				<p class="rf-modal-user-label">
					<?php esc_html_e( 'User:', 'rf-secure-login' ); ?>
					<strong id="rf-modal-username"></strong>
				</p>
			</div>
			<div class="rf-modal-footer">
				<button type="button" class="rf-btn-ghost" id="rf-modal-cancel">
					<?php esc_html_e( 'Cancel', 'rf-secure-login' ); ?>
				</button>
				<button type="button" class="rf-btn-danger" id="rf-modal-confirm" data-user-id="">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd"/></svg>
					<?php esc_html_e( 'Reset 2FA', 'rf-secure-login' ); ?>
				</button>
			</div>
		</div>
	</div>

</div><!-- .rf-settings-wrap -->
