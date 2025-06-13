<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Sample;

use WP_REST_Request as Request;
use WP_Custom_API\Includes\Permission_Interface;
use WP_Custom_API\Api\Sample\Model;

/**
* Prevent direct access from sources other than the Wordpress environment
*/

if (!defined('ABSPATH')) exit;

final class Permission extends Permission_Interface
{
    public const TOKEN_NAME = 'sample_token';

    public static function authorized(Request $request): bool|array
    {
        // Replace code in this method with logic for protecting a route from unauthorized access. 

        $token = self::token_parser(self::TOKEN_NAME);
        return [$token->ok, $token->data];
    }
}