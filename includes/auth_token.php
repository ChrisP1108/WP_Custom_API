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
    private static function response(bool $ok = false, string|int $id = null, string $msg = null, int $issued_at = null, int $expires_at = null): array {
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
        $data = strval($id) . '|' . $expiration_time . '|' . $issued_at;
        $base64_data = base64_encode($data); 
        $hmac = hash_hmac("sha256", $base64_data, Config::SECRET_KEY);
        $base64_hmac = base64_encode($hmac);
        $token = $base64_data . '.' . $base64_hmac;
        if (!wp_is_using_https() && Config::TOKEN_OVER_HTTPS_ONLY) {
            return self::response(false, $id, "Token could not be stored as a cookie on the client, as the `TOKEN_OVER_HTTPS_ONLY` config variable is set to true and the server is not using HTTPS.");
        }
        setcookie($token_name, $token, $expiration_time, "/", "", Config::TOKEN_OVER_HTTPS_ONLY, Config::TOKEN_COOKIE_HTTP_ONLY);
        return self::response(true,$id, "Token successfully generated.", $issued_at, $expiration_time);
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
        list($base64_data, $base64_received_hmac) = explode(".", $token, 2);
        if (!isset($base64_received_hmac) || !isset($base64_data)) {
            self::remove_token($token_name);
            return self::response(false, null, "Inadequate data from existing token. May be invalid.");
        }
        $data = base64_decode($base64_data);
        $received_hmac = base64_decode($base64_received_hmac);
        list($id, $expiration, $issued_at) = explode('|', $data);
        if (!isset($id) || !isset($expiration) || !isset($issued_at)) {
            self::remove_token($token_name);
            return self::response(false, null, "Token structure is invalid.");
        }
        if (intval($expiration) <= time()) {
            self::remove_token($token_name);
            return self::response(false, null, "Token has expired.");
        }
        if ($logout_time !== null && intval($issued_at) <= $logout_time) {
            self::remove_token($token_name);
            return self::response(false, null, "Token was issued before or at the last logout time.");
        }
        $computed_hmac = hash_hmac("sha256", $data, Config::SECRET_KEY);
        if (!hash_equals($computed_hmac, $received_hmac)) {
            self::remove_token($token_name);
            return self::response(false, null, "Invalid token.");
        }
        return self::response(true, $id, "Token is valid.", intval($issued_at), intval($expiration));
    }
}