<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Config;
use WP_REST_Request;
use WP_Custom_API\Includes\Password;
use WP_Custom_API\Includes\Auth_Token;
use WP_Custom_API\Includes\Response_Handler;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

/** 
 * Interface for permission classes. 
 * Each permission class must implement the `authorized` method to check if a given user is authorized to access a route.
 * 
 * @since 1.0.0
 */

class Permission_Interface
{
    
    /**
     * METHOD - public
     * 
     * Used to declare a route for public access.
     * 
     * @return bool Returns true to allow route to be public
     */
    
    final public static function public(): bool {
        return true;
    }

    /**
     * METHOD - unauthorized_response
     * 
     * Generates a error response for unauthorized access.
     * This is primarily used for the controller classes parent controller_interface response helper.
     *
     * @return WP_Error as Error - Returns an error indicating unauthorized access.
     */
    
    final public static function unauthorized_response(): object {
        return Response_Handler::response(false, 401, 'Unauthorized', null, false);
    }

    /**
     * METHOD - password_hash
     * 
     * Hashes a password string using the Password class.
     *
     * This method utilizes the Password class to hash a given password string.
     * The resulting hash is returned as part of an array or object response.
     *
     * @param string $string The password string to hash.
     * 
     * @return array|object The response containing the hash and status information.
     */

    final public static function password_hash(string $string): array|object 
    {
        return Password::hash($string);
    }

    /**
     * METHOD - password_verify
     * 
     * Verifies a password hash using the Password class.
     *
     * This method utilizes the Password class to verify a given password string
     * against the provided hash. The resulting verification status is returned
     * as part of an array or object response.
     *
     * @param string $entered_password The plain text password to compare.
     * @param string $hashed_password The hashed password to verify against.
     * 
     * @return array|object The response containing the verification result and status information.
     */

    final public static function password_verify(string $entered_password = '', string $hashed_password = ''): array|object 
    {
        return Password::verify($entered_password, $hashed_password);
    }

    /**
     * METHOD - token_generate
    * 
    * Generates an authentication token using the Auth_Token class.
    *
    * This method utilizes the Auth_Token class to generate an authentication
    * token for the given user ID and token name. The expiration time can be
    * set optionally.
    *
    * @param int $id The user ID.
    * @param string $token_name The token name.
    * @param int $expiration The expiration time in seconds.
    *
    * @return array|object The response containing the generated token and status information.
    */

    final public static function token_generate(int $id, string $token_name, int $expiration = Config::TOKEN_EXPIRATION): array|object 
    {
        return Auth_Token::generate($id, $token_name, $expiration);
    }

    /**
     * METHOD - token_validate
    * 
    * Validates an authentication token using the Auth_Token class.
    *
    * This method utilizes the Auth_Token class to validate an authentication
    * token for the given token name. The token can also be invalidated if
    * a logout time is specified.
    *
    * @param string $token_name The token name to validate.
    * @param int $logout_time The time when the token should be invalidated (optional).
    *
    * @return array|object The response containing the validation result and status information.
    */

    final public static function token_validate(string $token_name, int $logout_time = 0): array|object 
    {
        return Auth_Token::validate($token_name, $logout_time);
    }

    /**
     * METHOD - token_remove
    * 
    * Removes an authentication token using the Auth_Token class.
    *
    * This method utilizes the Auth_Token class to remove an authentication
    * token based on the provided token name and optional user ID.
    *
    * @param string $token_name The name of the token to remove.
    * @param string|int $id The user ID associated with the token (optional).

    * @return void
    */

    final public static function token_remove(string $token_name, string|int $id = 0): void 
    {
        Auth_Token::remove_token($token_name, $id);
    }
}
