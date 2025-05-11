<?php
declare( strict_types=1 );

namespace WooCommerce\Facebook\API\PublicKeyGet;

defined( 'ABSPATH' ) || exit;

use WooCommerce\Facebook\API;

/**
 * Page API request object.
 *
 * @since 2.0.0
 */
class Request extends API\Request {
	/**
	 * API request constructor.
	 */
	public function __construct() {
		parent::__construct( '/shops_public_key', 'GET' );
	}
}
