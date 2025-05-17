<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Sample;

use WP_Custom_API\Includes\Model_Interface;

/**
* Prevent direct access from sources other than the Wordpress environment
*/

if (!defined('ABSPATH')) { 
    exit;
}

final class Model extends Model_Interface
{
    public static function table_name():string 
    {
        return 'sample';
    }

    public static function table_schema(): array 
    {
        return
            [

            ];
    }

    public static function create_table(): bool 
    {
        return false;
    }

    public static function data_schema(): array 
    {
        return 
            [

            ];
    }

    public static function required_keys(): array 
    {
        return 
            [

            ];
    }
}