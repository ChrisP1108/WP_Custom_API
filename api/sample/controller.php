<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Sample;

use WP_REST_Request as Request;
use WP_REST_Response as Response;
use WP_Custom_API\Config;
use WP_Custom_API\Includes\Controller_Interface;
use WP_Custom_API\Includes\Auth_Token;
use WP_Custom_API\Includes\Password;
use WP_Custom_API\Api\Sample\Model;
use WP_Custom_API\Api\Sample\Permission;

/**
* Prevent direct access from sources other than the Wordpress environment
*/

if (!defined('ABSPATH')) { 
    exit;
}

final class Controller extends Controller_Interface
{
    public static function index(Request $request): Response 
    {
        return self::response(self::request_parser($request));
    }
}