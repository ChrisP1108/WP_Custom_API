<?php

declare(strict_types=1);

namespace WP_Custom_API\App\Models;

use WP_Custom_API\Plugin\Model;

class Post implements Model
{
    public static function table_name():string {
        return 'post';
    }
    public static function table_schema(): array {
        return
            [

            ];
    }
    public static function run_migration(): bool {
        return false;
    }
}