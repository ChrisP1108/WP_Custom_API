<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Sample;

use WP_Custom_API\Config;
use WP_Custom_API\Includes\Permission_Interface;
use WP_Custom_API\Includes\Auth_Token;
use WP_Custom_API\Includes\Password;

/**
* Prevent direct access from sources other than the Wordpress environment
*/

if (!defined('ABSPATH')) { 
    exit;
}

final class Permission extends Permission_Interface
{
    public const TOKEN_NAME = 'sample_token';

    public static function authorized(): bool
    {
        // Replace code in this method with logic for protecting a route from unauthorized access. 

        return Auth_Token::validate(self::TOKEN_NAME)['ok'];
    }
}