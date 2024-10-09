<?php  

declare(strict_types=1);

namespace WP_Custom_API\Core;

use WP_Custom_API\Config;

/** 
 * Used for generating authentication tokens to keep users logged in for a specified period of time
 * These auth tokens are stored as cookies on the client side
 * 
 * @since 1.0.0
 */

class Auth_Token {

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

    public static function remove_token(string $token_name = null): void {
        if ($token_name) setcookie($token_name, '', time() - 300, '/');
    }

    /**
     * METHOD - response
     * 
     * Used by validate method to return if token was valid and if so, return the id from it
     * 
     * @param bool $ok - Used to 
     * @param string $id - Id from token
     * @param string $msg - Provide response message
     * @return array - Returns array with keys of "ok" for boolean if token was valid, key of "id" for id from token, and a "msg" key for a response message
     * 
     * @since 1.0.0
     */

    private static function response(bool $ok = false, string|int $id = null, string $msg = null): array {
        if ($id) $id = intval($id);
        return ['ok' => $ok, 'id' => $id, 'msg' => $msg];
    }

    /**
     * METHOD - generate
     * 
     * @param int $id - Id of user for token generation
     * @param string $token_name - Name of token to be stored.  Token name is also the cookie name
     * @return array - Returns array with "ok", "id", and "msg" keys
     * 
     * @since 1.0.0
     */

    public static function generate(int $id = null, string $token_name = null, $expiration = Config::TOKEN_EXPIRATION): array {
        if (!$id || !$token_name) return self::response(false, $id, "`id` and `token_name` parameters required to generate token.");
        $expiration_time = time() + intval($expiration);
        $data = strval($id) . '|' . $expiration_time;
        $hmac = hash_hmac("sha256", $data, Config::SECRET_KEY);
        $token = $data . '.' . $hmac;
        if (!wp_is_using_https() && Config::TOKEN_OVER_HTTPS_ONLY) {
            return self::response(false, $id, "Token could not be stored as a cookie on the client, as the `TOKEN_OVER_HTTPS_ONLY` config variable is set to true and the server is not using HTTPS.");
        }
        setcookie($token_name, $token, $expiration_time, "/", "", Config::TOKEN_OVER_HTTPS_ONLY, Config::TOKEN_COOKIE_HTTP_ONLY);
        return self::response(true, $id, "Token successfully generated.");
    }

    /**
     * METHOD - validate
     * 
     * Checks that token is valid.  If not, cookie is removed and false value is returned
     * 
     * @param string $token_name - Name of token to verify. Stored as http only cookie with the same name
     * @return array - Returns array with "ok", "id", and "msg" keys
     * 
     * @since 1.0.0
     */

    public static function validate(string $token_name = null): array {
        if (!$token_name) return self::response(false, null, "A token name must be provided for validation.");
        $token = $_COOKIE[$token_name] ?? null;
        if (!$token) return self::response(false, null, "No token with the name of `".$token_name."` was found.");  
        list($data, $received_hmac) = explode(".", $token, 2);
        if (!isset($received_hmac) || !isset($data)) {
            self::remove_token($token_name);
            return self::response(false, null, "Inadequate data from existing token.  May be invalid."); 
        }
        list($id, $expiration) = explode('|', $data);
        if (intval($expiration) <= time() || !isset($id)) {
            self::remove_token($token_name);
            return self::response(false, null, "Invalid token."); 
        }
        $computed_hmac = hash_hmac("sha256", $data, Config::SECRET_KEY);
        if (!hash_equals($computed_hmac, $received_hmac)) {
            self::remove_token($token_name);
            return self::response(false, null, "Invalid token."); 
        }
        return self::response(true, $id, "Token valid.");
    }
}