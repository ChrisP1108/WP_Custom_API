<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Config;
use WP_Custom_API\Includes\Response_Handler;

final class Cookie {

    /**
     * METHOD - set
     * 
     * Set a cookie with the given name, value, and parameters.
     *
     * @param string $name The name of the cookie.
     * @param string $value The value of the cookie.
     * @param int $expires_at The expiration time of the cookie (in seconds).
     * @param string $path The path on the server in which the cookie will be available (default is '/').
     * @param string $domain The domain that the cookie is available to (default is '').
     * @return Response_Handler A response handler object indicating success or failure.
     */

    public static function set(string $name, string $value, int $expires_at = 0, string $path = '/', string $domain = ''): Response_Handler 
    {
        // Delegate to the cookie setter method to handle cookie creation
        return self::cookie_setter($name, $value, $expires_at, $path, $domain);
    }

    /**
     * METHOD - get
     * 
     * Get a cookie with the given name.
     * 
     * @param string $name The name of the cookie.
     * @return Response_Handler A response handler object indicating success or failure.
     */

    public static function get(string $name): Response_Handler 
    {
        // Get the cookie from the $_COOKIE superglobal array
        $cookie_result = $_COOKIE[$name] ?? null;

        // Set the $ok variable to true if the cookie is found and false otherwise
        $ok = $cookie_result !== null;

        // Return a response handler object with the appropriate status code and message
        return Response_Handler::response($ok, $ok ? 200 : 500, $ok ? 'Cookie `' . $name . '` found.' : 'Cookie `' . $name . '` not found.', ['value' => $cookie_result]);
    }

    /**
     * METHOD - remove
     * 
     * Remove a cookie with the given name.
     * 
     * @param string $name The name of the cookie.
     * @param string $path The path on the server in which the cookie will be available (default is '/').
     * @param string $domain The domain that the cookie is available to (default is '').
     * @return Response_Handler A response handler object indicating success or failure.
     */

    public static function remove(string $name, string $path = '/', string $domain = ''): Response_Handler 
    {
        return self::cookie_setter($name, '', time() - 3600, $path, $domain);
    }

    /**
     * METHOD - cookie_setter
     * 
     * Set a cookie with the given name, value, and parameters or
     * remove a cookie with the given name.
     *
     * @param string $name The name of the cookie.
     * @param string $value The value of the cookie (leave empty to remove cookie).
     * @param int $expires_at The expiration time of the cookie (in seconds).
     * @param string $path The path on the server in which the cookie will be available (default is '/').
     * @param string $domain The domain that the cookie is available to (default is '').
     * @return Response_Handler A response handler object indicating success or failure.
     */

    private static function cookie_setter(string $name, string $value = '', int $expires_at = 0, string $path = '/', string $domain = ''): Response_Handler 
    {
        $cookie_result = setcookie($name, $value, 
            [
                'expires' => $expires_at,
                'path' => $path, 
                'domain' => $domain, 
                'secure' => Config::TOKEN_OVER_HTTPS_ONLY, 
                'httponly' => Config::TOKEN_COOKIE_HTTP_ONLY, 
                'samesite' => Config::TOKEN_COOKIE_SAME_SITE
            ]
        );

        return Response_Handler::response(
            $cookie_result, 
            $cookie_result ? 200 : 500, 
            $cookie_result ? "Cookie `" . $name . "` set successfully." : "Failed to set cookie `" . $name . "`.");
    }
}