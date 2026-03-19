<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Sample\Test\Yes\One;

use WP_Custom_API\Includes\Model_Interface;

/**
* Prevent direct access from sources other than the Wordpress environment
*/

if (!defined('ABSPATH')) exit;

/**
* Interface namespace - sample_test_yes_one
*/

final class Model extends Model_Interface
{
    public static function schema(): array 
    {
        // Below is a sample schema, feel free to update/delete as needed.

        return
            [
                'name' =>   [
                    'query'    => 'VARCHAR(50)',
                    'type'     => 'text',
                    'required' => true,
                    'minimum'  => 2,
                    'maximum'  => 50,
                ],
                'email' =>  [
                    'query'    => 'VARCHAR(80)',
                    'type'     => 'email',
                    'required' => true,
                    'minimum'  => 8,
                    'maximum'  => 80,
                ]
            ]
        ;
    }

    public static function create_table(): bool 
    {
        // When schema is updated, update this method to return true to have the table created.
        
        return false;
    }
}