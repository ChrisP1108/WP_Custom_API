<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Sample\Test;

use WP_Custom_API\Includes\Router;
use WP_Custom_API\Api\Sample\Test\Controller;
use WP_Custom_API\Api\Sample\Test\Permission;

/**
* Prevent direct access from sources other than the Wordpress environment
*/

if (!defined('ABSPATH')) exit;

/**
* API Base Route - {url_origin}/wp-json/custom-api/v1/sample/test 
*/

/**
* Sample GET route
*/

Router::get("/", [Controller::class, "index"], [Permission::class, "public"]);