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
        // $token_generate = Permission::token_generate(Permission::TOKEN_NAME, 4);
        $destroy_session = Permission::token_remove(Permission::TOKEN_NAME, 4);
        // $token_validate = Permission::token_validate(Permission::TOKEN_NAME);
        // var_dump($token_validate);
        // $token_session = Permission::token_session_data(Permission::TOKEN_NAME, 4);
        // $additionals = $token_session->data->additionals;
        // $additionals['testers'] = 'farters25dd5';
        // $update_session = Permission::token_update_session_data(Permission::TOKEN_NAME, 4, $additionals);
        return self::response(null, 200, 'Sample route works');
    }
}
