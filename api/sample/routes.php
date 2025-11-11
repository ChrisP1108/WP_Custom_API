<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Sample;

use WP_Custom_API\Includes\Router;
use WP_Custom_API\Api\Sample\Controller;
use WP_Custom_API\Api\Sample\Permission;

/**
* Prevent direct access from sources other than the Wordpress environment
*/

if (!defined('ABSPATH')) exit;

/**
* API Base Route - {url_origin}/wp-json/custom-api/v1/sample 
*/

/**
* Sample GET route
*/

Router::get("/", [Permission::class, "public"], [Controller::class, "index"]);