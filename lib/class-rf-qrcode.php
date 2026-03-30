<?php
/**
 * RF_QRCode class.
 *
 * Helper class for generating QR code images using an
 * external API service.
 *
 * @package RedFrogSecureLogin
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RF_QRCode
 *
 * Provides static methods to generate QR code image URLs
 * and HTML img tags via api.qrserver.com.
 *
 * @since 1.0.0
 */
class RF_QRCode {

	/**
	 * Base URL for the QR code API.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const API_BASE = 'https://api.qrserver.com/v1/create-qr-code/';

	/**
	 * Get the URL for a QR code image.
	 *
	 * @since  1.0.0
	 * @param  string $data The data to encode in the QR code.
	 * @param  int    $size Image dimensions in pixels (width = height).
	 * @return string QR code image URL.
	 */
	public static function get_image_url( $data, $size = 200 ) {
		return add_query_arg(
			array(
				'size'   => absint( $size ) . 'x' . absint( $size ),
				'data'   => rawurlencode( $data ),
				'format' => 'png',
				'margin' => '10',
			),
			self::API_BASE
		);
	}

	/**
	 * Get an HTML img tag for a QR code.
	 *
	 * @since  1.0.0
	 * @param  string $data The data to encode in the QR code.
	 * @param  int    $size Image dimensions in pixels (width = height).
	 * @param  string $alt  Alt text for the image.
	 * @return string HTML img element.
	 */
	public static function get_image_tag( $data, $size = 200, $alt = 'QR Code' ) {
		$url = self::get_image_url( $data, $size );

		return sprintf(
			'<img src="%s" alt="%s" width="%d" height="%d" class="rf-qr-code" />',
			esc_url( $url ),
			esc_attr( $alt ),
			absint( $size ),
			absint( $size )
		);
	}
}
