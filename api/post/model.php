<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Post;

use WP_Custom_API\Includes\Model_Interface;

/**
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Model extends Model_Interface
{
    public static function table_name(): string
    {
        return 'post';
    }


    public static function table_schema(): array
    {
        return [
            'name' => 'VARCHAR(255) NOT NULL'
        ];
    }

    public static function run_migration(): bool
    {
        return true;
    }
}
