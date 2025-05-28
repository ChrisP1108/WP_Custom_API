<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Sample;

use WP_Custom_API\Includes\Model_Interface;

/**
* Prevent direct access from sources other than the Wordpress environment
*/

if (!defined('ABSPATH')) exit;

final class Model extends Model_Interface
{
    public static function table_name(): string 
    {
        return 'sample';
    }

    public static function schema(): array 
    {
        // Below is a sample schema, feel free to update/delete as needed.

        return
            [
                'name' => 
                    [
                        'query'    => 'VARCHAR(50)',
                        'type'     => 'text',
                        'required' => true,
                        'limit'    => 50
                    ],
                'email' => 
                    [
                        'query'    => 'VARCHAR(80)',
                        'type'     => 'text',
                        'required' => true,
                        'limit'    => 80
                    ]
            ]
        ;
    }

    public static function create_table(): bool 
    {
        return false;
    }
}