<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Post;

use WP_Custom_API\Config;
use WP_Custom_API\Includes\Database;
use WP_Custom_API\Includes\Auth_Token;
use WP_Custom_API\Includes\Password;

/**
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) {
    exit;
}

class Permission
{
    public const TOKEN_NAME = 'post_token';

    public static function is_authorized()
    {
        return false;
    }
}
