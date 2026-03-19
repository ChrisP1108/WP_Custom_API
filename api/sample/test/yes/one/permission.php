<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Sample\Test\Yes\One;

use WP_REST_Request as Request;
use WP_Custom_API\Includes\Permission_Interface;
use WP_Custom_API\Api\Sample\Test\Yes\One\Model;
use WP_Custom_API\Api\Sample\Test\Yes\One\Utils;

/**
* Prevent direct access from sources other than the Wordpress environment
*/

if (!defined('ABSPATH')) exit;

/**
* Interface namespace - sample_test_yes_one
*/

final class Permission extends Permission_Interface
{
    // Insert permission methods here.
}