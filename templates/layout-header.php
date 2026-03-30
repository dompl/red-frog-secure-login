<?php
/**
 * Layout Header Template.
 *
 * Full HTML5 doctype, head section with meta tags, CSS variables,
 * enqueued styles, and the opening body/canvas/main wrapper.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 *
 * @var string $page_title   The page title.
 * @var array  $body_classes Array of body class strings.
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="robots" content="noindex, nofollow" />
	<title><?php echo esc_html( $page_title ); ?></title>

	<?php wp_print_styles(); ?>

	<style>
		:root {
			--bg-primary: #0a0a0f;
			--bg-secondary: #12121a;
			--bg-tertiary: #1a1a2e;
			--accent-primary: <?php echo esc_attr( get_option( 'rf_accent_colour', '#00ff88' ) ); ?>;
			--accent-secondary: #00d4ff;
			--accent-warn: #ff3366;
			--accent-glow: <?php echo esc_attr( get_option( 'rf_accent_colour', '#00ff88' ) ); ?>33;
			--text-primary: #e0e0e0;
			--text-muted: #6b7280;
			--text-bright: #ffffff;
			--border-subtle: #1f2937;
			--border-focus: <?php echo esc_attr( get_option( 'rf_accent_colour', '#00ff88' ) ); ?>;
		}
	</style>

	<?php
	/**
	 * Fires in the login page <head>.
	 *
	 * Used by WordPress core and plugins to output additional
	 * styles, scripts, and meta tags.
	 */
	do_action( 'login_head' );
	?>
</head>
<body class="<?php echo esc_attr( implode( ' ', $body_classes ) ); ?>">

	<!-- Canvas background for particle animation -->
	<canvas id="rf-canvas" aria-hidden="true"></canvas>

	<!-- Main content wrapper -->
	<main class="rf-login-wrapper" role="main">
