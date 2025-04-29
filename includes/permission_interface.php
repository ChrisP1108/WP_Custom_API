<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Error as Error;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) {
    exit;
}

/** 
 * Interface for permission classes. 
 * Each permission class must implement the `authorized` method to check if a given user is authorized to access a route.
 * 
 * @since 1.0.0
 */

abstract class Permission_Interface
{
    /**
     * Used to declare a route for public access.
     * 
     * @return bool Returns true to allow route to be public
     */
    
    final public static function public(): bool {
        return true;
    }

    /**
     * Generates a error response for unauthorized access.
     *
     * @return WP_Error as Error - Returns an error indicating unauthorized access.
     */
    
    final public static function unauthorized_response(): Error {
        return new Error(
            'unauthorized',
            'You are not authorized to access this resource.',
            ['status' => 401]
        );
    }

    /**
     * Check if a given user is authorized to access a route.
     * 
     * Implement this method in your permission class to check if a given user is authorized to access a route.
     * 
     * @return bool Returns true if the user is authorized to access the route, false otherwise.
     */

    abstract public static function authorized(): bool;
}
