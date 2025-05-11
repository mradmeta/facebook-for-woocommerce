<?php
// phpcs:ignoreFile
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace WooCommerce\Facebook;

defined( 'ABSPATH' ) or exit;

use WooCommerce\Facebook\API\Exceptions\Request_Limit_Reached;
use WooCommerce\Facebook\API\Request;
use WooCommerce\Facebook\API\Response;
use WooCommerce\Facebook\Events\Event;
use WooCommerce\Facebook\Framework\Api\Base;
use WooCommerce\Facebook\Framework\Api\Exception as ApiException;

/**
 * API handler.
 *
 * @since 2.0.0
 *
 * @method Framework\Api\Request get_request()
 */
class RateLimitedAPIBase extends Base {

	use API\Traits\Rate_Limited_API;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param string $access_token access token to use for API requests
	 */
	public function __construct( $access_token ) {
		$this->access_token = $access_token;
		$this->request_headers = array(
			'Authorization' => "Bearer {$access_token}",
		);
		$this->set_request_content_type_header( 'application/json' );
		$this->set_request_accept_header( 'application/json' );
	}


	/**
	 * Gets the access token being used for API requests.
	 *
	 * @since 2.1.0
	 *
	 * @return string
	 */
	public function get_access_token() {
		return $this->access_token;
	}


	/**
	 * Sets the access token to use for API requests.
	 *
	 * @since 2.1.0
	 *
	 * @param string $access_token access token to set
	 */
	public function set_access_token( $access_token ) {
		$this->access_token = $access_token;
	}


	/**
	 * Performs an API request.
	 *
	 * @param API\Request $request request object
	 * @return API\Response
	 * @throws API\Exceptions\Request_Limit_Reached|ApiException
	 */
	protected function perform_request( $request ): API\Response {
		$rate_limit_id   = $request::get_rate_limit_id();
		$delay_timestamp = $this->get_rate_limit_delay( $rate_limit_id );
		// if there is a delayed timestamp in the future, throw an exception
		if ( $delay_timestamp >= time() ) {
			$this->handle_throttled_request( $rate_limit_id, $delay_timestamp );
		} else {
			$this->set_rate_limit_delay( $rate_limit_id, 0 );
		}
		return parent::perform_request( $request );
	}

	/**
	 * Handles a throttled API request.
	 *
	 * @since 2.1.0
	 *
	 * @param string $rate_limit_id ID for the API request
	 * @param int    $timestamp timestamp until the delay is over
	 * @throws API\Exceptions\Request_Limit_Reached
	 */
	private function handle_throttled_request( $rate_limit_id, $timestamp ) {
		if ( time() > $timestamp ) {
			return;
		}
		$exception = new API\Exceptions\Request_Limit_Reached( "{$rate_limit_id} requests are currently throttled.", 401 );
		$date_time = new \DateTime();
		$date_time->setTimestamp( $timestamp );
		$exception->set_throttle_end( $date_time );
		throw $exception;
	}

	/**
	 * Gets the next page of results for a paginated response.
	 *
	 * @since 2.0.0
	 *
	 * @param API\Response $response previous response object
	 * @param int          $additional_pages number of additional pages of results to retrieve
	 * @return API\Response|null
	 * @throws ApiException
	 */
	public function next( API\Response $response, int $additional_pages = 0 ) {
		$next_response = null;
		// get the next page if we haven't reached the limit of pages to retrieve and the endpoint for the next page is available
		if ( ( 0 === $additional_pages || $response->get_pages_retrieved() <= $additional_pages ) && $response->get_next_page_endpoint() ) {
			$components = parse_url( str_replace( $this->request_uri, '', $response->get_next_page_endpoint() ) );
			$request = $this->get_new_request(
				[
					'path'   => $components['path'] ?? '',
					'method' => 'GET',
					'params' => isset( $components['query'] ) ? wp_parse_args( $components['query'] ) : [],
				]
			);
			$this->set_response_handler( get_class( $response ) );
			$next_response = $this->perform_request( $request );
			// this is the n + 1 page of results for the original response
			$next_response->set_pages_retrieved( $response->get_pages_retrieved() + 1 );
		}
		return $next_response;
	}


	/**
	 * Returns a new request object.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args {
	 *     Optional. An array of request arguments.
	 *
	 *     @type string $path request path
	 *     @type string $method request method
	 *     @type array $params request parameters
	 * }
	 * @return Request
	 */
	protected function get_new_request( $args = [] ) {
		$defaults = array(
			'path'   => '/',
			'method' => 'GET',
			'params' => [],
		);
		$args    = wp_parse_args( $args, $defaults );
		$request = new Request( $args['path'], $args['method'] );
		if ( $args['params'] ) {
			$request->set_params( $args['params'] );
		}
		return $request;
	}


	/**
	 * Returns the plugin class instance associated with this API.
	 *
	 * @since 2.0.0
	 *
	 * @return \WC_Facebookcommerce
	 */
	protected function get_plugin() {
		return facebook_for_woocommerce();
	}
}
