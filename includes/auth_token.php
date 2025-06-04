<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Config;
use WP_Custom_API\Includes\Response_Handler;

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
     * @return object - Returns a structured response from the Response_Handler::response() method.
     */

    private static function response(bool $ok, int $status_code, string|int|null $id, string $message, int $issued_at = 0, int $expires_at = 0): object
    {
        $data = ['id' => $id !== '' ? intval($id) : null];

        if ($issued_at !== 0) {
            $data['issued_at'] = date("Y-m-d H:i:s", $issued_at);
        }
        if ($expires_at !== 0) {
            $data['expires_at'] = date("Y-m-d H:i:s", $expires_at);
        }

        $action_hook_data = Response_Handler::response($ok, $status_code, $message, $data);

        do_action('wp_custom_api_database_response', $action_hook_data);

        if (!$ok) {
            $message = 'Unauthorized';
            $status_code = 401;
        }

        return Response_Handler::response($ok, $status_code, $message, $data);
    }

    /**
     * METHOD - remove_token
     * 
     * Removes token by removing cookie name with corresponding name
     * 
     * @param string $token_name - Name of token to remove
     * @param string|int|null $id - The user ID from the token.
     * 
     * @return void
     */

    public static function remove_token(string $token_name, string|int $id = 0): void
    {
        // Apply auth token prefix to token name if it doesn't exist

        if (!str_starts_with($token_name, Config::AUTH_TOKEN_PREFIX)) {
            $token_name = Config::AUTH_TOKEN_PREFIX . $token_name;
        }

        // Remove cookie

        setcookie($token_name, '', time() - 300, '/');

        // Remove transient if id passed in and transient exists
        
        if ($id !== 0) {
            $id = intval($id);
            $stored_nonce = get_transient(Config::AUTH_TOKEN_PREFIX . $id);
            if ($stored_nonce) delete_transient(Config::AUTH_TOKEN_PREFIX . $id);
        }
    }

    /**
     * METHOD - generate
     * 
     * @param int $id - Id of user for token generation
     * @param string $token_name - Name of token to be stored.  Token name is also the cookie name
     * @param int $expiration - Set duration of token before expiring.
     * 
     * @return object - Returns object from the self::response() method
     */

    public static function generate(int $id, string $token_name, int $expiration = Config::TOKEN_EXPIRATION): object
    {
        // Check if token name was provided.  If not return error
        if (!$id || !$token_name) return self::response(false, 500, $id, "`id` and `token_name` parameters required to generate auth token.");

        $issued_at = time(); // Current timestamp
        $expiration_time = $issued_at + intval($expiration);

        // Generate a secure random nonce for replay protection
        $nonce = bin2hex(random_bytes(16));

        // Token data to be stored
        $data = strval($id) . '|' . $expiration_time . '|' . $issued_at . '|' . $nonce;

        // Generate random bytes for IV
        $iv = random_bytes(16);

         // Derive keys using HKDF
        $encryption_key = hash_hkdf('sha256', Config::SECRET_KEY, 32, 'encryption');
        $hmac_key = hash_hkdf('sha256', Config::SECRET_KEY, 32, 'authentication');

        // Encrypt the data
        $encrypted_data = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, 0, $iv);
        if ($encrypted_data === false) return self::response(false, 500, $id, "Encryption failed while generating the auth token.");

        // Create HMAC of the encrypted data (with IV) for integrity
        $hmac = hash_hmac("sha256", $iv . $encrypted_data, $hmac_key);

        $iv_64 = base64_encode($iv);
        $encrypted_data_64 = base64_encode($encrypted_data);
        $hmac_64 = base64_encode($hmac);

        // Combine IV + encrypted data(base64 encoded) + HMAC(base64_encoded) and base64 encode it
        $token = $iv_64 . '.' . $encrypted_data_64 . '.' . $hmac_64;

        // Check if the connection is using HTTPS
        if (!wp_is_using_https() && Config::TOKEN_OVER_HTTPS_ONLY) return self::response(false, 500, $id, "Token could not be stored as a cookie on the client, as the `TOKEN_OVER_HTTPS_ONLY` config variable is set to true and the server is not using HTTPS.");

        // Store the nonce server-side in a transient (or database) to validate later
        set_transient(Config::AUTH_TOKEN_PREFIX . $id, $nonce, $expiration_time - $issued_at);

        // Apply auth token prefix to token name
        $token_name = Config::AUTH_TOKEN_PREFIX . $token_name;

        // Set the token as a cookie in the browser
        $cookie_result = setcookie($token_name, $token, 
        [
            'expires' => $expiration_time, 
            'path' => "/", 
            'domain' => "", 
            'secure' => Config::TOKEN_OVER_HTTPS_ONLY, 
            'httponly' => Config::TOKEN_COOKIE_HTTP_ONLY, 
            'samesite' => Config::TOKEN_COOKIE_SAME_SITE
        ]);

        if (!$cookie_result) return self::response(false, 500, $id, "Token was generated but could not be stored in cookie. Headers may have already been sent.");

        return self::response(true, 200, $id, "Token successfully generated.", $issued_at, $expiration_time);
    }

    /**
     * METHOD - validate
     * 
     * Checks that token is valid.  If not, cookie is removed and false value is returned
     * 
     * @param string $token_name - Name of token to verify. Stored as http only cookie with the same name
     * @param int|null $logout_time - Optional timestamp of the user's last logout.
     * 
     * @return object - Returns object from the self::response() method
     */

    public static function validate(string $token_name, int $logout_time = 0): object
    {
        // Check if token name was provided.  If not return error
        if (!$token_name) return self::response(false, 500, null, "A token name must be provided for validation.");

        // Apply auth token prefix to token name
        $token_name = Config::AUTH_TOKEN_PREFIX . $token_name;

        // Check if token exists
        $token = $_COOKIE[$token_name] ?? null;
        if (!$token) return self::response(false, 401, null, "No token with the name of `" . $token_name . "` was found.");

        // Base64 decode the token
        $token_base64_decoded = base64_decode($token, true);
        if ($token_base64_decoded === false) {
            self::remove_token($token_name);
            return self::response(false, 401, null, "Invalid base64 token format.");
        }

        // Split the token into encrypted data and HMAC and check that it is valid.
        $token_split = explode(".", $token_base64_decoded, 2);
        if(count($token_split) !== 3) return self::response(false, 401, null, "Inadequate data from existing token. May be invalid.");

        // Split token data into encrypted data and HMAC
        list($iv_base64, $encrypted_data_with_iv_base64, $received_hmac_base64) = $token_split;

        // Base 64 decode the data
        $iv = base64_decode($iv_base64, true);
        $encrypted_data_with_iv = base64_decode($encrypted_data_with_iv_base64, true);
        $received_hmac = base64_decode($received_hmac_base64, true);

        if (!$iv === false || $encrypted_data_with_iv === false || $received_hmac === false) {
            self::remove_token($token_name);
            return self::response(false, 401, null, "Invalid base64 token format.");
        }

        if (strlen($encrypted_data_with_iv) < 16) return self::response(false, 401, null, "Invalid token structure. Missing IV.");

        // Extract the IV from the encrypted data (first 16 bytes)
        $iv = substr($encrypted_data_with_iv, 0, 16);

        // Extract the encrypted data (the rest of the data after the IV)
        $encrypted_data = substr($encrypted_data_with_iv, 16);

        // Derive keys using HKDF (same as in generate)
        $encryption_key = hash_hkdf('sha256', Config::SECRET_KEY, 32, 'encryption');
        $hmac_key = hash_hkdf('sha256', Config::SECRET_KEY, 32, 'authentication');

        // Validate HMAC
        $computed_hmac = hash_hmac("sha256", $iv . $encrypted_data, $hmac_key);
        if (!hash_equals($computed_hmac, $received_hmac)) {
            self::remove_token($token_name);
            return self::response(false, 401, null, "Invalid token.");
        }

        // Decrypt the data
        $decrypted_data = openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
        if ($decrypted_data === false) {
            self::remove_token($token_name);
            return self::response(false, 401, null, "Decryption failed. Token may be invalid.");
        }

        // Extract token components
        $parts = explode('|', $decrypted_data);

        // Check if token structure is valid
        if (count($parts) !== 4) {
            self::remove_token($token_name);
            return self::response(false, 401, null, "Token structure is invalid.");
        }

        // Separate token components
        list($id, $expiration, $issued_at, $nonce) = $parts;

        // Check token expiration
        if (intval($expiration) <= time()) {
            self::remove_token($token_name, $id);
            return self::response(false, 401, null, "Token has expired.");
        }

        // Check if token was issued before logout
        if ($logout_time !== 0 && intval($issued_at) <= $logout_time) {
            self::remove_token($token_name, $id);
            return self::response(false, 401, null, "Token was issued before or at the last logout time.");
        }

        // Retrieve and validate nonce
        $stored_nonce = get_transient(Config::AUTH_TOKEN_PREFIX . $id);
        if (!$stored_nonce || $stored_nonce !== $nonce) {
            self::remove_token($token_name, $id);
            return self::response(false, 401, null, "Invalid or replayed token.");
        }

        // Token is valid
        return self::response(true, 200, $id, "Token authenticated.", intval($issued_at), intval($expiration));
    }
}