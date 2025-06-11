<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Config;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

/** 
 * Used for hashing passwords with the Bcrypt algorithm, as well as validating existing hash against a plan string.
 * Number of cost rounds determined by value set in PASSWORD_HASH_ROUNDS constant in Config class.
 * 
 * @since 1.0.0
 */

final class Password 
{


    /**
     * METHOD - response
     * 
     * Used to create standardized responses
     * 
     * @param bool $ok - Whether the password hashing or validation was successful
     * @param int $status_code - HTTP status code
     * @param string $message - Message to be returned in the response
     * @param string $hash - Hashed password string
     * 
     * @return object Response from the Response_Handler::response() method.
     */

    private static function response(bool $ok, int $status_code, string $message = '', string $hash = ''): object
    {
        $output = [];

        if ($hash !== '') {
            $output['hash'] = $hash;
        }

        $return_data = Response_Handler::response($ok, $status_code, $message, $output);

        do_action('wp_custom_api_password_response', $return_data);

        return $return_data;
    }

    /**
     * METHOD - hash
     * 
     * Hashes string with Bcrypt algorithm.  Cost rounds set by PASSWORD_HASH_ROUNDS constant in Config class.
     * 
     * @param string $string - String text to be hashed.
     * 
     * @return object Response indicating success or failure, and the generated hash if successful.
     */

    public static function hash(string $string): object 
    {
        if ($string === '') {
            return self::response(false, 500, 'String must be provided to hash in Password hash method.');
        }

        $cost = (int) (Config::PASSWORD_HASH_ROUNDS ?? 12);

        $cost = is_int($cost) && $cost <= 20 && $cost >= 8 ? $cost : 12;

        $hash = password_hash($string, PASSWORD_BCRYPT, ['cost' => $cost]);

        if ($hash === false) {
            return self::response(false, 500, 'Failed to hash the string.');
        }

        return self::response(true, 200, 'Password hash successful', $hash);
    }

    /**
     * METHOD - verify
     * 
     * Verifies a plain text password against a hashed password.
     * 
     * @param string $entered_password The plain-text password.
     * @param string $hashed_password The hashed password to verify against.
     * 
     * @return object Response indicating whether verification was successful.
     */

    public static function verify(string $entered_password = '', string $hashed_password = ''): object 
    {
        if ($entered_password === '' || $hashed_password === '') {
            return self::response(false, 500, 'The entered plain text password and the hashed password must be passed in as parameters in the Password verify method.');
        }
        $result = password_verify($entered_password, $hashed_password);

        return self::response(
            $result, 
            $result ? 200 : 401,
            'Password verification ' . ($result ? 'passed.' : 'failed.')
        );
    }
}
