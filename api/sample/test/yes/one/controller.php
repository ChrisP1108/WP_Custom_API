<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Sample\Test\Yes\One;

use WP_REST_Request as Request;
use WP_REST_Response as Response;
use WP_Custom_API\Includes\Controller_Interface;
use WP_Custom_API\Api\Sample\Test\Yes\One\Model;
use WP_Custom_API\Api\Sample\Test\Yes\One\Permission;
use WP_Custom_API\Api\Sample\Test\Yes\One\Utils;

/**
* Prevent direct access from sources other than the Wordpress environment
*/

if (!defined('ABSPATH')) exit;

/**
* Interface namespace - sample_test_yes_one
*/

final class Controller extends Controller_Interface
{
    public static function index(Request $request, mixed $permission_params): Response 
    {
        return self::response(null, 200, 'sample_test_yes_one route works');
    }
}