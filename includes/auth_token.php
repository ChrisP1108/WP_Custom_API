<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Config;
use WP_Custom_API\Includes\Response_Handler;
use WP_Custom_API\Includes\Cookie;
use WP_Custom_API\Includes\Session;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

/** 
 * Used for generating authentication tokens to keep users logged in for a specified period of time
 * These auth tokens are stored as cookies on the client side
 * 
 * @since 1.0.0
 */

final class Auth_Token
{

    /**
     * CONSTRUCTOR
     *
     * @param int|null $id The user ID associated with this token.
     * @param string|null $issued_at The timestamp when the token was issued.
     * @param string|null $expiration_at The timestamp when the token will expire.
     */

    private function __construct(
        public readonly int|null $id,
        public readonly string|null $issued_at,
        public readonly string|null $expiration_at
    ) {}

    /**
     * METHOD - response
     * 
     * Used to return detailed information about the token and its validity.
     * 
     * @param bool $ok - Whether the token validation was successful.
     * @param string|int|null $id - The user ID from the token.
     * @param string|null $message - A descriptive response message.
     * @param int|null $issued_at - Timestamp when the token was issued.
     * @param int|null $expires_at - Timestamp when the token will expire.
     * 
     * @return Response_Handler - Returns a structured response from the Response_Handler::response() method.
     */

    private static function response(bool $ok, int $status_code, string|int|null $id, string $message, int $issued_at = 0, int $expiration_at = 0): Response_Handler
    {
        $id = $id !== '' ? intval($id) : null;

        if ($issued_at !== 0) {
            $issued_at = date("Y-m-d H:i:s", $issued_at);
        } else $issued_at = null;

        if ($expiration_at !== 0) {
            $expiration_at = date("Y-m-d H:i:s", $expiration_at);
        } else $expiration_at = null;

        $object_data = new static($id, $issued_at, $expiration_at);

        $return_data = Response_Handler::response($ok, $status_code, $message, $object_data);

        do_action('wp_custom_api_auth_token_response', $return_data);

        return $return_data;
    }

    /**
     * METHOD - remove_token
     * 
     * Removes token by removing cookie name with corresponding name, along with its corresponding server side Wordpress transient session data.
     * 
     * @param string $token_name - Name of token to remove
     * @param string|int|null $id - The user ID from the token.
     * 
     * @return Response_Handler The response of the token remove operation from the self::response() method.
     */

    public static function remove_token(string $token_name, string|int $id = 0): Response_Handler
    {
        // Apply auth token prefix to token name if it doesn't exist
        if (!str_starts_with($token_name, Config::AUTH_TOKEN_PREFIX)) {
            $token_name = Config::AUTH_TOKEN_PREFIX . $token_name;
        }

        // Remove cookie
        $remove_cookie_result = Cookie::remove($token_name);

        // Check if cookie removal was successful
        if (!$remove_cookie_result->ok) return self::response(false, 500, null, "Token cookie removal failed for token name of `" . $token_name . "`.");

        // Check if id was provided
        if ($id === 0) return self::response(false, 500, null, "No id was provided to remove token for token name `" . $token_name . "`.");
        $id = intval($id);

        // Check that session data exists corresponding to token
        $session = Session::get($token_name, $id);
        if (!$session->ok) return self::response(false, 500, null, "No token with the name of `" . $token_name . "` was found.");

        // Remove session data corresponding to token
        $remove_session = Session::delete($token_name, $id);

        if (!$remove_session->ok) return self::response(false, 500, null, "Token session data removal failed for token name of `" . $token_name . "`.");

        return self::response(true, 200, $id, "Token removed successfully for token name of `" . $token_name . "` along with its session data.");
    }

    /**
     * METHOD - generate
     * 
     * @param int $id - Id of user for token generation
     * @param string $token_name - Name of token to be stored.  Token name is also the cookie name
     * @param int $expiration - Set duration of token before expiring.
     * 
     * @return Response_Handler The response of the token generate operation from the self::response() method.
     */

    public static function generate(string $token_name, int $id, int $expiration_at = Config::TOKEN_EXPIRATION): Response_Handler
    {
        // Check if token name was provided.  If not return error
        if (!$id || !$token_name) return self::response(false, 500, $id, "`id` and `token_name` parameters required to generate auth token.");

        $issued_at = time(); // Current timestamp

        // Generate a secure random nonce for replay protection
        $nonce = bin2hex(random_bytes(16));

        // Expiration timestamp
        $expires_at = $issued_at + $expiration_at;

        // Token data to be stored
        $data = strval($id) . '|' . $expires_at . '|' . $issued_at . '|' . $nonce;

        // Generate random bytes for IV
        $iv = random_bytes(16);

         // Derive keys using HKDF
        $encryption_key = hash_hkdf('sha256', Config::SECRET_KEY, 32, 'encryption');
        $hmac_key = hash_hkdf('sha256', Config::SECRET_KEY, 32, 'authentication');

        // Encrypt the data
        $encrypted_data = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, OPENSSL_RAW_DATA, $iv);
        if ($encrypted_data === false) return self::response(false, 500, $id, "Encryption failed while generating the auth token.");

        // Create HMAC of the encrypted data (with IV) for integrity
        $hmac = hash_hmac("sha256", $iv . $encrypted_data, $hmac_key, true);

        $iv_64 = base64_encode($iv);
        $encrypted_data_64 = base64_encode($encrypted_data);
        $hmac_64 = base64_encode($hmac);

        // Combine IV + encrypted data(base64 encoded) + HMAC(base64_encoded) and base64 encode it
        $token = $iv_64 . '.' . $encrypted_data_64 . '.' . $hmac_64;

        // Check if the connection is using HTTPS
        if (!wp_is_using_https() && Config::TOKEN_OVER_HTTPS_ONLY) return self::response(false, 500, $id, "Token of `" . $token_name . "` could not be stored as a cookie on the client, as the `TOKEN_OVER_HTTPS_ONLY` config variable is set to true and the server is not using HTTPS.");

        // Apply auth token prefix to token name
        $token_name_prefix = Config::AUTH_TOKEN_PREFIX . $token_name;

        // Store the nonce server-side into the sessions table to validate later through Session::generate method
        $session = Session::generate($token_name_prefix, $id, $nonce, $expires_at);
        
        if (!$session->ok) return self::response(false, 500, $id, "There was an error storing the token session data.");    

        // Set the token as a cookie in the browser
        $cookie_result = Cookie::set($token_name_prefix, $token, $expires_at);

        if (!$cookie_result->ok) return self::response(false, 500, $id, "Token was generated but could not be stored as a cookie in the browser. Headers may have already been sent.");

        return self::response(true, 200, $id, "Token successfully generated.", $issued_at, $expires_at);
    }

    /**
     * METHOD - validate
     * 
     * Checks that token is valid.  If not, cookie is removed and false value is returned
     * 
     * @param string $token_name - Name of token to verify. Stored as http only cookie with the same name
     * @param int|null $logout_time - Optional timestamp of the user's last logout.
     * 
     * @return Response_Handler The response of the token validate operation from the self::response() method.
     */

    public static function validate(string $token_name, int $logout_time = 0): Response_Handler
    {
        // Check if token name was provided.  If not return error
        if (!$token_name) return self::response(false, 500, null, "A token name must be provided for validation.");

        // Apply auth token prefix to token name
        $token_name_prefix = Config::AUTH_TOKEN_PREFIX . $token_name;

        // Check if token exists
        $token = Cookie::get($token_name_prefix);
        if (!$token->ok) return self::response(false, 401, null, "No token with the name of `" . $token_name_prefix . "` was found.");

        // Split the token into encrypted data and HMAC and check that it is valid.
        $token_split = explode(".", $token->data['value'], 3);
        if(count($token_split) !== 3) return self::response(false, 401, null, "Inadequate data from existing token. May be invalid.");

        // Split token data into encrypted data and HMAC
        list($iv_base64, $encrypted_data_with_iv_base64, $received_hmac_base64) = $token_split;

        // Base 64 decode the data
        $iv = base64_decode($iv_base64, true);
        $encrypted_data_with_iv = base64_decode($encrypted_data_with_iv_base64, true);
        $received_hmac = base64_decode($received_hmac_base64, true);

        if ($iv === false || $encrypted_data_with_iv === false || $received_hmac === false) {
            self::remove_token($token_name_prefix);
            return self::response(false, 401, null, "Invalid base64 token format.");
        }

        // Derive keys using HKDF (same as in generate)
        $encryption_key = hash_hkdf('sha256', Config::SECRET_KEY, 32, 'encryption');
        $hmac_key = hash_hkdf('sha256', Config::SECRET_KEY, 32, 'authentication');

        // Validate HMAC
        $computed_hmac = hash_hmac("sha256", $iv . $encrypted_data_with_iv, $hmac_key, true);
        if (!hash_equals($computed_hmac, $received_hmac)) {
            self::remove_token($token_name_prefix);
            return self::response(false, 401, null, "Invalid token.");
        }

        // Decrypt the data
        $decrypted_data = openssl_decrypt($encrypted_data_with_iv, 'aes-256-cbc', $encryption_key, OPENSSL_RAW_DATA, $iv);
        if ($decrypted_data === false) {
            self::remove_token($token_name_prefix);
            return self::response(false, 401, null, "Decryption failed. Token may be invalid.");
        }

        // Extract token components
        $parts = explode('|', $decrypted_data);

        // Check if token structure is valid
        if (count($parts) !== 4) {
            self::remove_token($token_name_prefix);
            return self::response(false, 401, null, "Token structure is invalid.");
        }

        // Separate token components
        list($id, $expiration, $issued_at, $nonce) = $parts;

        $id = intval($id);
        $expiration_at = intval($expiration);
        $issued_at = intval($issued_at);

        // Check token expiration
        if ($expiration_at <= time()) {
            self::remove_token($token_name_prefix, $id);
            return self::response(false, 401, null, "Token has expired.");
        }

        // Check if token was issued before logout
        if ($logout_time !== 0 && $issued_at <= $logout_time) {
            self::remove_token($token_name_prefix, $id);
            return self::response(false, 401, null, "Token was issued before or at the last logout time.");
        }

        // Retrieve and validate nonce
        $session_data = Session::get($token_name_prefix, $id);
        $nonce_value = null;
        $session_expiration_at = 0;

        if (is_object($session_data->data)) {
            $nonce_value = $session_data->data->nonce ?? null;
            $session_expiration_at = $session_data->data->expiration_at ?? 0;
        } else {
            $nonce_value = $session_data->data['nonce'] ?? null;
            $session_expiration_at = $session_data->data['expiration_at'] ?? 0;
        }

        if (!$nonce_value || $nonce_value !== $nonce) {
            self::remove_token($token_name_prefix, $id);
            return self::response(false, 401, null, "Invalid, replayed token, or session data for token name of `" . $token_name_prefix . "` is missing.");
        }

        // Check if session data is expired
        if ($session_expiration_at <= time()) {
            self::remove_token($token_name_prefix, $id);
            return self::response(false, 401, null, "Session data for token name of `" . $token_name_prefix . "` has expired.");
        }

        // Token is valid
        return self::response(true, 200, $id, "Token authenticated.", $issued_at, $expiration_at);
    }
}
