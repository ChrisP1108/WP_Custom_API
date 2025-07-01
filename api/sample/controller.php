<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Sample;

use WP_REST_Request as Request;
use WP_REST_Response as Response;
use WP_Custom_API\Includes\Controller_Interface;
use WP_Custom_API\Api\Sample\Model;
use WP_Custom_API\Api\Sample\Permission;

/**
* Prevent direct access from sources other than the Wordpress environment
*/

if (!defined('ABSPATH')) exit;

final class Controller extends Controller_Interface
{
    public static function index(Request $request, $permission_params): Response 
    {
        return self::response(null, 200, 'Sample route works');
    }
}