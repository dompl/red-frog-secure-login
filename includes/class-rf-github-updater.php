<?php
/**
 * RF_GitHub_Updater class.
 *
 * Enables automatic plugin updates from GitHub releases.
 * Checks the GitHub API for new releases, integrates with
 * the WordPress plugin update system, and handles post-install
 * directory renaming.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RF_GitHub_Updater
 *
 * Hooks into WordPress plugin update transients and the
 * plugins_api filter to provide seamless updates from
 * GitHub releases.
 *
 * @since 1.0.0
 */
class RF_GitHub_Updater {

	/**
	 * GitHub repository in "owner/repo" format.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const GITHUB_REPO = 'dompl/red-frog-secure-login';

	/**
	 * Transient key for caching the GitHub API response.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const CACHE_KEY = '_rf_github_update_check';

	/**
	 * Cache time-to-live in seconds (12 hours).
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const CACHE_TTL = 43200;

	/**
	 * The plugin basename (e.g. "red-frog-secure-login/red-frog-secure-login.php").
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * The plugin slug (directory name).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Constructor.
	 *
	 * Registers hooks for the WordPress plugin update system.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->plugin_basename = RF_SECURE_LOGIN_BASENAME;
		$this->plugin_slug     = dirname( $this->plugin_basename );

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
	}

	/**
	 * Check GitHub for a newer release and inject it into the update transient.
	 *
	 * @since  1.0.0
	 * @param  object $transient The plugin update transient object.
	 * @return object Modified transient with update data if available.
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();

		if ( false === $release ) {
			return $transient;
		}

		$remote_version = $this->tag_to_version( $release->tag_name );

		if ( version_compare( RF_SECURE_LOGIN_VERSION, $remote_version, '<' ) ) {
			$download_url = $this->get_download_url( $release );

			if ( ! empty( $download_url ) ) {
				$update              = new \stdClass();
				$update->slug        = $this->plugin_slug;
				$update->plugin      = $this->plugin_basename;
				$update->new_version = $remote_version;
				$update->url         = 'https://github.com/' . self::GITHUB_REPO;
				$update->package     = $download_url;
				$update->icons       = array();
				$update->banners     = array();
				$update->tested      = '';
				$update->requires    = '6.0';
				$update->requires_php = '8.0';

				$transient->response[ $this->plugin_basename ] = $update;
			}
		}

		return $transient;
	}

	/**
	 * Provide plugin information for the WordPress update modal.
	 *
	 * Responds to plugins_api requests for this plugin's slug
	 * with data from the latest GitHub release.
	 *
	 * @since  1.0.0
	 * @param  false|object|array $result The result object or array. Default false.
	 * @param  string             $action The API action being performed.
	 * @param  object             $args   Plugin API arguments.
	 * @return false|object Plugin info object or false if not our plugin.
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || $this->plugin_slug !== $args->slug ) {
			return $result;
		}

		$release = $this->get_latest_release();

		if ( false === $release ) {
			return $result;
		}

		$remote_version = $this->tag_to_version( $release->tag_name );

		$info                = new \stdClass();
		$info->name          = 'Red Frog Secure Login';
		$info->slug          = $this->plugin_slug;
		$info->version       = $remote_version;
		$info->author        = '<a href="https://redfrogstudio.co.uk">Dom Kapelewski</a>';
		$info->author_profile = 'https://redfrogstudio.co.uk';
		$info->homepage      = 'https://github.com/' . self::GITHUB_REPO;
		$info->requires      = '6.0';
		$info->requires_php  = '8.0';
		$info->tested        = '';
		$info->download_link = $this->get_download_url( $release );
		$info->trunk         = $this->get_download_url( $release );
		$info->last_updated  = $release->published_at ?? '';
		$info->sections      = array(
			'description' => __( 'A stunning custom login screen with animated backgrounds and two-factor authentication.', 'rf-secure-login' ),
			'changelog'   => isset( $release->body ) ? wp_kses_post( $release->body ) : '',
		);
		$info->banners       = array();

		return $info;
	}

	/**
	 * Rename the extracted directory after a GitHub update.
	 *
	 * GitHub ZIP downloads extract to "owner-repo-hash" directories.
	 * This renames it back to the expected plugin directory name.
	 *
	 * @since  1.0.0
	 * @param  bool  $response   Installation response.
	 * @param  array $hook_extra Extra arguments passed to the upgrader.
	 * @param  array $result     Installation result data.
	 * @return array Modified result with corrected destination.
	 */
	public function after_install( $response, $hook_extra, $result ) {
		// Only act on our plugin.
		if ( ! isset( $hook_extra['plugin'] ) || $this->plugin_basename !== $hook_extra['plugin'] ) {
			return $result;
		}

		global $wp_filesystem;

		$proper_destination = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
		$wp_filesystem->move( $result['destination'], $proper_destination );
		$result['destination']      = $proper_destination;
		$result['destination_name'] = $this->plugin_slug;

		// Reactivate the plugin.
		activate_plugin( $this->plugin_basename );

		return $result;
	}

	/**
	 * Get the latest release data from GitHub.
	 *
	 * Uses a transient cache to avoid excessive API calls.
	 *
	 * @since  1.0.0
	 * @return object|false Release data object or false on failure.
	 */
	private function get_latest_release() {
		$cached = get_transient( self::CACHE_KEY );

		if ( false !== $cached ) {
			return $cached;
		}

		$url = sprintf(
			'https://api.github.com/repos/%s/releases/latest',
			self::GITHUB_REPO
		);

		$response = wp_remote_get( $url, array(
			'headers' => array(
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'RedFrogSecureLogin/' . RF_SECURE_LOGIN_VERSION,
			),
			'timeout' => 10,
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status_code ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( empty( $data ) || ! isset( $data->tag_name ) ) {
			return false;
		}

		set_transient( self::CACHE_KEY, $data, self::CACHE_TTL );

		return $data;
	}

	/**
	 * Strip the leading "v" from a release tag to get the version number.
	 *
	 * @since  1.0.0
	 * @param  string $tag The release tag (e.g. "v1.2.3").
	 * @return string The version number (e.g. "1.2.3").
	 */
	private function tag_to_version( $tag ) {
		return ltrim( $tag, 'vV' );
	}

	/**
	 * Get the download URL for a release.
	 *
	 * Prefers a .zip asset attached to the release. Falls back
	 * to the GitHub-generated zipball URL.
	 *
	 * @since  1.0.0
	 * @param  object $release Release data from the GitHub API.
	 * @return string Download URL.
	 */
	private function get_download_url( $release ) {
		// Check for a .zip asset attached to the release.
		if ( ! empty( $release->assets ) && is_array( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				if ( isset( $asset->content_type ) && 'application/zip' === $asset->content_type ) {
					return $asset->browser_download_url;
				}

				// Also match by file extension.
				if ( isset( $asset->name ) && str_ends_with( $asset->name, '.zip' ) ) {
					return $asset->browser_download_url;
				}
			}
		}

		// Fall back to the auto-generated zipball.
		if ( ! empty( $release->zipball_url ) ) {
			return $release->zipball_url;
		}

		return '';
	}
}
