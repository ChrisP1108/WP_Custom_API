<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Sample\Test\Yes\One;

use WP_Custom_API\Includes\Router;
use WP_Custom_API\Api\Sample\Test\Yes\One\Controller;
use WP_Custom_API\Api\Sample\Test\Yes\One\Permission;

/**
* Prevent direct access from sources other than the Wordpress environment
*/

if (!defined('ABSPATH')) exit;

/**
* Interface namespace - sample_test_yes_one
*/

/**
* API Base Route - {url_origin}/wp-json/custom-api/v1/sample/test/yes/one 
*/

/**
* Sample GET route
*/

Router::get("/", [Permission::class, "public"], [Controller::class, "index"]);