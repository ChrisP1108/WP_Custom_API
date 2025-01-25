<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Config;

/** 
 * Used for generating authentication tokens to keep users logged in for a specified period of time
 * These auth tokens are stored as cookies on the client side
 * 
 * @since 1.0.0
 */

class Auth_Token
{

    /**
     * METHOD - remove_token
     * 
     * Removes token by removing cookie name with corresponding name
     * 
     * @param string $token_name - Name of token to remove
     * @return void
     * 
     * @since 1.0.0
     */

    public static function remove_token(string $token_name = null): void
    {
        if ($token_name) setcookie($token_name, '', time() - 300, '/');
    }

    /**
     * METHOD - response
     * 
     * Used to return detailed information about the token and its validity.
     * 
     * @param bool $ok - Whether the token validation was successful.
     * @param string|int|null $id - The user ID from the token.
     * @param string|null $msg - A descriptive response message.
     * @param int|null $issued_at - Timestamp when the token was issued.
     * @param int|null $expires_at - Timestamp when the token will expire.
     * @return array - Returns a structured response.
     * 
     * @since 1.0.0
     */

    private static function response(bool $ok = false, string|int $id = null, string $msg = null, int $issued_at = null, int $expires_at = null): array
    {
        $response = [
            'ok' => $ok,
            'id' => $id ? intval($id) : null,
            'msg' => $msg
        ];
        if ($issued_at !== null) {
            $response['issued_at'] = date("Y-m-d H:i:s", $issued_at);
        }
        if ($expires_at !== null) {
            $response['expires_at'] = date("Y-m-d H:i:s", $expires_at);
        }
        return $response;
    }

    /**
     * METHOD - generate
     * 
     * @param int $id - Id of user for token generation
     * @param string $token_name - Name of token to be stored.  Token name is also the cookie name
     * @param int $expiration - Set duration of token before expiring.
     * @return array - Returns array with "ok", "id", and "msg" keys
     * 
     * @since 1.0.0
     */

    public static function generate(int $id = null, string $token_name = null, int $expiration = Config::TOKEN_EXPIRATION): array
    {
        if (!$id || !$token_name) {
            return self::response(false, $id, "`id` and `token_name` parameters required to generate token.");
        }

        $issued_at = time(); // Current timestamp
        $expiration_time = $issued_at + intval($expiration);

        // Generate a secure random nonce for replay protection
        $nonce = bin2hex(random_bytes(16)); // Secure random nonce

        // Token data to be stored
        $data = strval($id) . '|' . $expiration_time . '|' . $issued_at . '|' . $nonce;

        // Encrypt the token data for additional security
        $iv = random_bytes(16); // Generate a random IV for encryption
        $encrypted_data = openssl_encrypt($data, 'aes-256-cbc', Config::SECRET_KEY, 0, $iv);
        if ($encrypted_data === false) {
            return self::response(false, $id, "Encryption failed while generating the token.");
        }

        // Create HMAC of the encrypted data (with IV) for integrity
        $hmac = hash_hmac("sha256", $iv . $encrypted_data, Config::SECRET_KEY);

        // Combine IV + encrypted data + HMAC and base64 encode it
        $token = base64_encode($iv . $encrypted_data . '.' . $hmac);

        // Check if the connection is using HTTPS
        if (!wp_is_using_https() && Config::TOKEN_OVER_HTTPS_ONLY) {
            return self::response(false, $id, "Token could not be stored as a cookie on the client, as the `TOKEN_OVER_HTTPS_ONLY` config variable is set to true and the server is not using HTTPS.");
        }

        // Store the nonce server-side in a transient (or database) to validate later
        set_transient("auth_nonce_$id", $nonce, $expiration_time - $issued_at);

        // Set the token as as a cookie in the browser
        setcookie($token_name, $token, $expiration_time, "/", "", Config::TOKEN_OVER_HTTPS_ONLY, Config::TOKEN_COOKIE_HTTP_ONLY);

        return self::response(true, $id, "Token successfully generated.", $issued_at, $expiration_time);
    }

    /**
     * METHOD - validate
     * 
     * Checks that token is valid.  If not, cookie is removed and false value is returned
     * 
     * @param string $token_name - Name of token to verify. Stored as http only cookie with the same name
     * @param int|null $logout_time - Optional timestamp of the user's last logout.
     * @return array - Returns array with "ok", "id", and "msg" keys
     * 
     * @since 1.0.0
     */

    public static function validate(string $token_name = null, int $logout_time = null): array
    {
        if (!$token_name) {
            return self::response(false, null, "A token name must be provided for validation.");
        }

        $token = $_COOKIE[$token_name] ?? null;
        if (!$token) {
            return self::response(false, null, "No token with the name of `" . $token_name . "` was found.");
        }

        // Base64 decode the token
        $token_base64_decoded = base64_decode($token, true);
        if ($token_base64_decoded === false) {
            self::remove_token($token_name);
            return self::response(false, null, "Invalid base64 token format.");
        }

        // Split the token into encrypted data and HMAC
        list($encrypted_data_with_iv, $received_hmac) = explode(".", $token_base64_decoded, 2);
        if (!isset($received_hmac) || !isset($encrypted_data_with_iv)) {
            self::remove_token($token_name);
            return self::response(false, null, "Inadequate data from existing token. May be invalid.");
        }

        // Extract the IV from the encrypted data (first 16 bytes)
        $iv = substr($encrypted_data_with_iv, 0, 16);

        // Extract the encrypted data (the rest of the data after the IV)
        $encrypted_data = substr($encrypted_data_with_iv, 16);

        // Decrypt the data
        $decrypted_data = openssl_decrypt($encrypted_data, 'aes-256-cbc', Config::SECRET_KEY, 0, $iv);
        if ($decrypted_data === false) {
            self::remove_token($token_name);
            return self::response(false, null, "Decryption failed. Token may be invalid.");
        }

        list($id, $expiration, $issued_at, $nonce) = explode('|', $decrypted_data);
        if (!isset($id) || !isset($expiration) || !isset($issued_at) || !isset($nonce)) {
            self::remove_token($token_name);
            return self::response(false, null, "Token structure is invalid.");
        }

        // Check token expiration
        if (intval($expiration) <= time()) {
            self::remove_token($token_name);
            return self::response(false, null, "Token has expired.");
        }

        // Check if token was issued before logout
        if ($logout_time !== null && intval($issued_at) <= $logout_time) {
            self::remove_token($token_name);
            return self::response(false, null, "Token was issued before or at the last logout time.");
        }

        // Retrieve and validate nonce
        $stored_nonce = get_transient("auth_nonce_$id");
        if (!$stored_nonce || $stored_nonce !== $nonce) {
            if ($stored_nonce) {
                delete_transient("auth_nonce_$id");
            }
            self::remove_token($token_name);
            return self::response(false, null, "Invalid or replayed token.");
        }

        // Recompute the HMAC and compare it
        $computed_hmac = hash_hmac("sha256", $iv . $encrypted_data, Config::SECRET_KEY);
        if (!hash_equals($computed_hmac, $received_hmac)) {
            self::remove_token($token_name);
            return self::response(false, null, "Invalid token.");
        }

        return self::response(true, $id, "Token is valid.", intval($issued_at), intval($expiration));
    }
}
