<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Config;
use WP_Custom_API\Includes\Password;
use WP_Custom_API\Includes\Auth_Token;
use WP_Custom_API\Includes\Session;
use WP_Custom_API\Includes\Cookie;
use WP_Custom_API\Includes\Response_Handler;
use WP_Session_Tokens;

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
    * @param bool $validate_header_nonce - Optional parameter to validate the header nonce.
    * @param int $logout_time The time when the token should be invalidated (optional).
    *
    * @return Response_Handler The response of the token_validate operation.
    */

    final public static function token_validate(string $token_name, bool $validate_header_nonce = false, int $logout_time = 0): Response_Handler 
    {
        return Auth_Token::validate($token_name, $validate_header_nonce, $logout_time);
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
        $token_prefixed = Config::AUTH_TOKEN_PREFIX . $token_name;

        return Session::get($token_prefixed, $id);
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
        $token_prefixed = Config::AUTH_TOKEN_PREFIX . $token_name;

        // Update the session additionals and return the response
        return Session::update($token_prefixed, $id, $updated_data);
    }

    /**
     * METHOD - token_parser
     * 
     * Validates an authentication token and retrieves the associated session data.
     *
     * This method validates the given token name and then retrieves the
     * associated session data for the given token name and user ID.
     *
     * @param string $token_name The name of the token to parse.
     * @param bool $validate_header_nonce - Optional parameter to validate the header nonce.
     * @param int $logout_time The time at which the token should expire.
     *
     * @return Response_Handler The response of the token parse operation.
     */

    final public static function token_parser(string $token_name, bool $validate_header_nonce = false, int $logout_time = 0): Response_Handler
    {
        // Validate token and get the id if valid.
        $token_validate = Auth_Token::validate($token_name, $validate_header_nonce, $logout_time);

        // If token is invalid, return error
        if (!$token_validate->ok) return $token_validate;

        // If token was validated, gather token session data

        $id = null;
        if (is_object($token_validate->data)) {
            $id = $token_validate->data->id;
        } else $id = $token_validate->data['id'];

        $token_prefixed = Config::AUTH_TOKEN_PREFIX . $token_name;

        $token_session_data = Session::get($token_prefixed, $id);

        // Return token session data
        return $token_session_data;
    }

    /**
     * METHOD - generate_custom_session
     * 
     * Generates a custom session and stores it as a cookie for the specified user.
     *
     * This method checks the HTTPS connection, generates a secure nonce for the session,
     * and creates session data which is then stored as a cookie.
     *
     * @param string $name The name of the session.
     * @param int $id The user ID for whom the session is generated.
     * @param int $expiration_time The time at which the session should expire.
     * @param array $additionals Any additional data to be stored with the session.
     *
     * @return Response_Handler The response of the session generation operation.
     */

    final public static function generate_custom_session(string $name, int $id, int $expiration_time, array $additionals = []): Response_Handler 
    {
        // Check if the connection is using HTTPS for secure cookie transmission
        if (!wp_is_using_https() && Config::TOKEN_OVER_HTTPS_ONLY) {
            return Response_Handler::response(
                false, 
                500, 
                "Custom session of `" . $name . "` can not be stored as a cookie on the client, as the `TOKEN_OVER_HTTPS_ONLY` config variable is set to true and the server is not using HTTPS."
            );
        }

        // Generate a secure random nonce for replay protection
        $nonce = bin2hex(random_bytes(16));

        // Prefix the session name with the configured prefix
        $prefixed_name = Config::PREFIX . $name;

        // Generate a refresh nonce
        $refresh_nonce = bin2hex(random_bytes(16));

        // Set header nonce
        $header_nonce = bin2hex(random_bytes(16));

        // Generate the session data
        $session_result = Session::generate($prefixed_name, $id, $nonce, $expiration_time, $additionals, $refresh_nonce, $header_nonce);
        
        // If an error occurred while creating the session data, return the error response
        if (!$session_result->ok) return $session_result;

        // Set the session nonce in a cookie
        $cookie_result = Cookie::set($prefixed_name, base64_encode(strval($id)) . '.' . base64_encode($nonce) . '.' . base64_encode($refresh_nonce), $expiration_time);

        // If an error occurred while setting the cookie, return the error response
        if (!$cookie_result->ok) return $cookie_result;

        // Set header for header nonce
        header(Config::HEADER_NONCE_PREFIX . ': ' . $header_nonce);

        // Return the successful session creation response
        return $session_result;
    }

    /**
     * METHOD - update_custom_session
     * 
     * Update a custom session. This method updates the session data stored in the database. If the session does not exist, it returns an error response.
     * If the session exists, it updates the session data and returns a success response.
     *
     * @param string $name The name of the session to update.
     * @param int $id The user ID for whom the session is updated.
     * @param bool $validate_header_nonce - Optional parameter to validate the header nonce.
     * @param array $updated_data The updated session data.
     *
     * @return Response_Handler The response of the session update operation.
     */

    final public static function update_custom_session(string $name, int $id, bool $validate_header_nonce, array $updated_data): Response_Handler 
    {
        // Prefix the session name with the configured prefix
        $prefixed_name = Config::PREFIX . $name;

        $cookie_result = Cookie::get($prefixed_name);

        // If the cookie does not exist, return an error response
        if (!$cookie_result->ok) return $cookie_result;

        // Split the cookie value into parts
        $cookie_value_split = explode('.', $cookie_result->data['value'], 3);

        // If the cookie value does not have 3 parts, return an error response
        if (count($cookie_value_split) !== 3) return Response_Handler::response(
            false, 
            401, 
            "Cookie value for session `" . $name . "` is invalid. Expected 3 values, received " . count($cookie_value_split)
        );

        // Split the cookie value into parts
        [$cookie_id, $cookie_nonce, $cookie_refresh_nonce] = $cookie_value_split;

        // Decode the base64 values from the cookie value parts
        $cookie_id = base64_decode($cookie_id, true);
        $cookie_nonce = base64_decode($cookie_nonce, true);
        $cookie_refresh_nonce = base64_decode($cookie_refresh_nonce, true);

        $check_existing_session = Session::get($prefixed_name, intval($cookie_id));

        if (!$check_existing_session->ok) {
            Cookie::remove($prefixed_name);
            return $check_existing_session;
        }

        $existing_session_data = (array) $check_existing_session->data;

        if ($validate_header_nonce) {
            $headers_lowercased = array_change_key_case(getallheaders(), CASE_LOWER);
            $header_nonce_value = $headers_lowercased[strtolower(Config::HEADER_NONCE_PREFIX)] ?? null;
            if (!$header_nonce_value ||$header_nonce_value !== $existing_session_data['header_nonce']) {
                return Response_Handler::response(
                    false, 
                    401, 
                    "Header nonce for session `" . $name . "` does not match the session header nonce."
                );
            }
        }

        // If the cookie nonce does not match the session nonce, return an error response
        if ($cookie_nonce !== $existing_session_data['nonce']) {
            Cookie::remove($prefixed_name);
            Session::delete($prefixed_name, intval($cookie_id));
            return Response_Handler::response(
                false, 
                401, 
                "Cookie nonce for session `" . $name . "` does not match the session nonce."
            );
        }

        // If the cookie refresh nonce does not match the session refresh nonce, return an error response
        if ($cookie_refresh_nonce !== $existing_session_data['refresh_nonce']) {
            Cookie::remove($prefixed_name);
            Session::delete($prefixed_name, intval($cookie_id));
            return Response_Handler::response(
                false, 
                401, 
                "Cookie refresh nonce for session `" . $name . "` does not match the session refresh nonce."
            );
        }

        // Generate a refresh nonce
        $refresh_nonce = bin2hex(random_bytes(16));

        $update_cookie_result = Cookie::set($prefixed_name, base64_encode($cookie_id) . '.' . base64_encode($cookie_nonce) . '.' . base64_encode($refresh_nonce), $existing_session_data['expiration_at']);

        // If the cookie update failed, return the error response
        if (!$update_cookie_result->ok) return $update_cookie_result;

        // Regenerate header nonce value
        $updated_header_nonce_value = bin2hex(random_bytes(16));

        // Update the session data
        $update_existing_session_result = Session::update($prefixed_name, intval($cookie_id), $updated_data, $refresh_nonce, $updated_header_nonce_value);

        // If the session update failed, remove the cookie and existing session if it exsits and return the error response
        if (!$update_existing_session_result->ok) {
            Cookie::remove($prefixed_name);
            Session::delete($prefixed_name, intval($cookie_id));
            return $update_existing_session_result;
        }

        // Reset header for header nonce
        header(Config::HEADER_NONCE_PREFIX . ': ' . $updated_header_nonce_value);

        // Return the successful session update response
        return $update_existing_session_result;
    }

    /**
     * METHOD - delete_custom_session
     * 
     * Delete a custom session by name and user ID.
     *
     * This method deletes the session data from the database and removes the cookie
     * associated with the session.
     *
     * @param string $name The name of the session to delete.
     * @param int $id The user ID for whom the session is deleted.
     *
     * @return Response_Handler The response of the session deletion operation.
     */

    final public static function delete_custom_session(string $name, int $id): Response_Handler 
    {
        // Prefix the session name with the configured prefix
        $prefixed_name = Config::PREFIX . $name;

        $existing_session_result = Session::delete($prefixed_name, $id);

        // If the session deletion failed, remove the cookie and return the error response
        if (!$existing_session_result->ok) {
            Cookie::remove($prefixed_name);
            return $existing_session_result;
        }

        $cookie_removal_result = Cookie::remove($prefixed_name);

        // If the cookie removal failed, return the error response
        if (!$cookie_removal_result->ok) return $cookie_removal_result;

        // Return the successful session deletion response
        return $existing_session_result;
    }

    /**
     * METHOD - wp_user_login
     * 
     * Logs in the user with the provided username and password.
     *
     * @param string $username The username of the user to log in.
     * @param string $password The password of the user to log in.
     * @param bool $remember Whether to remember the user's login.
     *
     * @return Response_Handler The response of the login operation.
     */

    final public static function wp_user_login(string $username, string $password, bool $remember = false): Response_Handler 
    {       
        // Compile credentials
        $credentials = [
            "user_login" => sanitize_user($username),
            "user_password" => $password,
            "remember" => $remember
        ];

        // Login user
        $login = wp_signon($credentials, is_ssl());

        // Check if login was successful
        $ok = !is_wp_error($login);

        // Return response
        $response_data = Response_Handler::response($ok, $ok ? 200 : 401, $ok ? 'Successfully logged in Wordpress user.' : 'Wordpress user login failed.  Invalid credentials.', $login);
        return $response_data;
    }
    
    /**
     * METHOD - wp_user_logout
     * 
     * Log out the current user by destroying all their sessions and logging out from this browser
     * 
     * @return Response_Handler The response of the logout.  Will always be successful.
     */
    
    final public static function wp_user_logout(): Response_Handler 
    {
        // Get the current user's ID
        $user_id = get_current_user_id();

        if ( ! $user_id ) {
            return Response_Handler::response(
                false, 
                401, 
                'No Wordpress user ID found' 
            );
        }

        // Destroy every session this user has
        WP_Session_Tokens::get_instance($user_id)->destroy_all();

        // Log out this browser
        wp_logout();

        // Return response
        $response_data = Response_Handler::response(true, 200, 'Successfully logged out Wordpress user.', null);
        return $response_data;
    }

    final public static function wp_user_data(): Response_Handler
    {
        // Get user ID
        $user_id = get_current_user_id();

        // Return error if no user ID found
        if ( ! $user_id ) {
            return Response_Handler::response(
                false, 
                401, 
                'No Wordpress user ID found' 
            );
        }

        // Get user session data
        $token = wp_get_session_token();
        $manager = WP_Session_Tokens::get_instance( $user_id );
        $session = $manager->get( $token );

        if ( $session === false) {
            return Response_Handler::response(
                false, 
                401, 
                'Could not retrieve Wordpress user session data.' 
            );
        }

        // Return response
        $response_data = Response_Handler::response(true, 200, 'Successfully retrieved Wordpress user data.', $session);
        return $response_data;
    } 

    /**
     * METHOD - wp_user_update_session_data
     * 
     * Update the user session data associated with the current user.
     * Creates an "additionals" array key if not present to store data.
     * 
     * @return Response_Handler The response of the user login data.
     */

    final public static function wp_user_update_session_data(array $data): Response_Handler 
    {
        // Get user ID
        $user_id = get_current_user_id();

        // Return error if no user ID found
        if ( ! $user_id ) {
            return Response_Handler::response(
                false, 
                401, 
                'No Wordpress user ID found.' 
            );
        }

        // Get user session data
        $token = wp_get_session_token();
        $manager = WP_Session_Tokens::get_instance( $user_id );
        $session = $manager->get( $token );

        if ( $session === false) {
            return Response_Handler::response(
                false, 
                401, 
                'Could not retrieve Wordpress user session data.' 
            );
        }

        // Add additionals array key if not present
        if ( !isset( $session['additionals'] ) ) {
            $session['additionals'] = [];
        }

        // Update additionals
        $session['additionals'] = $data;
        $manager->update( $token, $session );

        // Return response
        $response_data = Response_Handler::response(true, 200, 'Successfully update Wordpress user session data', $data);
        return $response_data;
    }
}
