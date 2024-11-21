<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Post;

use WP_Custom_API\Includes\Model_Interface;

class Model implements Model_Interface
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
