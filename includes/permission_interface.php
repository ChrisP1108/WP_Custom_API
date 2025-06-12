<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Config;
use WP_Custom_API\Includes\Password;
use WP_Custom_API\Includes\Auth_Token;
use WP_Custom_API\Includes\Session;
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
     * @return Response_Handler The response of the password hash operation.
     */

    final public static function password_hash(string $string): Response_Handler 
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
     * @return Response_Handler The response of the password verify operation.
     */

    final public static function password_verify(string $entered_password = '', string $hashed_password = ''): Response_Handler 
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
    * @return Response_Handler The response of the token_generate operation.
    */

    final public static function token_generate(string $token_name, int $id, int $expiration = Config::TOKEN_EXPIRATION): Response_Handler
    {
        return Auth_Token::generate($token_name, $id, $expiration);
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
    * @return Response_Handler The response of the token_validate operation.
    */

    final public static function token_validate(string $token_name, int $logout_time = 0): Response_Handler 
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
    *
    * @return Response_Handler The response of the token remove operation from the self::response() method.
    */

    final public static function token_remove(string $token_name, string|int $id = 0): Response_Handler 
    {
        return Auth_Token::remove_token($token_name, $id);
    }

    /**
     * METHOD - token_session_data
    * 
    * Retrieves the session data associated with an authentication token.
    *
    * This method retrieves the session data associated with an authentication
    * token for the given token name and user ID.
    *
    * @param string $token_name The name of the token to retrieve the session data for.
    * @param int $id The user ID associated with the token.
    *
    * @return Response_Handler The response of the get session data operation.
    */

    final public static function token_session_data(string $token_name, int $id): Response_Handler
    {
        return Session::get($token_name, $id);
    }

    /**
     * METHOD - token_update_session_data
     * 
     * Updates the session data associated with an authentication token.
     *
     * This method utilizes the Session class to update additional data
     * for a session corresponding to the given token name and user ID.
     *
     * @param string $token_name The name of the token for which the session data should be updated.
     * @param int $id The user ID associated with the token.
     * @param array $updated_data The updated data to be stored in the session.
     *
     * @return Response_Handler The response of the update operation.
     */

    final public static function token_update_session_data(string $token_name, int $id, array $updated_data): Response_Handler 
    {
        // Update the session additionals and return the response
        return Session::update_additionals($token_name, $id, $updated_data);
    }
}
