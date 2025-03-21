<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Post;

use WP_Custom_API\Includes\Model_Interface;
use WP_Custom_API\Includes\Database;

/**
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Model implements Model_Interface
{
    public static function table_name(): string
    {
        return 'post';
    }
    public static function table_schema(): array
    {
        return
            [];
    }
    public static function run_migration(): bool
    {
        return false;
    }
}
