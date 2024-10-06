<?php  

declare(strict_types=1);

namespace WP_Custom_API\Core;

/** 
 * Used for generating authentication tokens to keep users logged in for a specified period of time
 * These auth tokens are stored on the client side via an HTTP only cookie for improved security.
 * 
 * @since 1.0.0
 */

class Auth_Token {

    /**
     * METHOD - remove_token
     * 
     * Removes invalid token by removing cookie name with corresponding name
     * 
     * @param string $token_name - Name of token to remove
     * @return void
     * 
     * @since 1.0.0
     */

    private static function remove_token(string $token_name):void {
        setcookie($token_name, '', time() - 300, '/'); 
    }

    /**
     * METHOD - response
     * 
     * Used by validate method to return if token was valid and if so, return the id from it
     * 
     * @param bool $ok - Used to 
     * @param string $id - Id from token
     * @return array - Returns array with keys of "ok" for boolean if token was valid, key of "id" for id from token 
     * 
     * @since 1.0.0
     */

    private static function response(bool $ok = false, string $id = null):array {
        if ($id) $id = intval($id);
        return ['ok' => $ok, 'id' => $id];
    }

    /**
     * METHOD - generate
     * 
     * @param int $id - Id of user for token generation
     * @param string $token_name - Name of token to be stored.  Token name is also the cookie name
     * @return string|null - Returns the generated token if successful, null if not
     * 
     * @since 1.0.0
     */

    public static function generate(int $id = null, string $token_name = ''): bool {
        if (!$id || $token_name === '') return null;
        $expiration = time() + WP_CUSTOM_API_TOKEN_EXPIRATION;
        $data = strval($id) . '|' . $expiration;
        $hmac = hash_hmac("sha256", $data, WP_CUSTOM_API_SECRET_KEY);
        $token = $data . '.' . $hmac;
        setcookie($token_name, $token, $expiration, "/", "", true, true);
        return true;
    }

    /**
     * METHOD - validate
     * 
     * Checks that token is valid.  If not, cookie is removed and false value is returned
     * 
     * @param string $token_name - Name of token to verify. Stored as http only cookie with the same name
     * @return bool - Returns true if valid, false if not
     * 
     * @since 1.0.0
     */

    public static function validate(string $token_name = '') {
        if (empty($token_name)) return self::response(false);
        $token = $_COOKIE[$token_name] ?? null;
        if (!$token) return false;  
        list($data, $received_hmac) = explode(".", $token, 2);
        if (!isset($received_hmac) || !isset($data)) {
            self::remove_token($token_name);
            return self::response(false); 
        }
        list($id, $expiration) = explode('|', $data);
        if ($expiration <= time()) {
            self::remove_token($token_name);
            return self::response(false); 
        }
        $computed_hmac = hash_hmac("sha256", $data, WP_CUSTOM_API_SECRET_KEY);
        if(!hash_equals($computed_hmac, $received_hmac)) {
            self::remove_token($token_name);
            return self::response(false); 
        }
        return self::response(true, $id);
    }
}