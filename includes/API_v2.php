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
 * API handler. Used to call non-Graph-API endpoints with a base url of https://api.facebook.com/
 *
 * @since 2.0.0
 *
 * @method Framework\Api\Request get_request()
 */
class API_v2 extends RateLimitedAPIBase {

	use API\Traits\Rate_Limited_API;

	public const BASE_URL = 'https://api.facebook.com/';

	public const API_VERSION = '1.0.0';

	/** @var string URI used for the request */
	protected $request_uri = self::BASE_URL;

	/** @var string the configured access token */
	protected $access_token;
}
