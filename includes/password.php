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
 * Used for hashing passwords with the Bcrypt algorithm, as well as validating existing hash against a plan string.
 * Number of cost rounds determined by value set in PASSWORD_HASH_ROUNDS constant in Config class.
 * 
 * @since 1.0.0
 */

final class Password 
{

    /**
     * CONSTRUCTOR
     * 
     * @param string|null $hash The hashed password string or null if no hash is provided
     */

    private function __construct(
        public readonly string|null $hash,
    ) {}

    /**
     * METHOD - response
     * 
     * Used to create standardized responses
     * 
     * @param bool $ok - Whether the password hashing or validation was successful
     * @param int $status_code - HTTP status code
     * @param string $message - Message to be returned in the response
     * @param string|null $hash - Hashed password string
     * 
     * @return Response_Handler - Returns a structured response
     */

    private static function response(bool $ok, int $status_code, string $message = '', string|null $hash = null): Response_Handler
    {
        $object_data = new static($hash);

        $return_data = Response_Handler::response($ok, $status_code, $message, $object_data);

        do_action('wp_custom_api_password_response', $return_data);

        return $return_data;
    }

    /**
     * METHOD - hash
     * 
     * Hashes string with Argon2ID.  Configration for Argon2ID can be found in Config class in the PASSWORD_SETTINGS constant.
     * 
     * @param string $string - String text to be hashed.
     * 
     * @return Response_Handler The response of the password hash operation from the self::response() method.
     */

    public static function hash(string $string): Response_Handler
    {
        if ($string === '') {
            return self::response(false, 500, 'String must be provided to hash in Password hash method.');
        }

        if (strlen($string) > 72) {
            return self::response(false, 500, 'String must be less than 72 characters in length in Password hash method.');
        }

        $hash = password_hash($string, PASSWORD_ARGON2ID, Config::PASSWORD_SETTINGS);

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
     * @return Response_Handler The response of the password verify operation from the self::response() method.
     */

    public static function verify(string $entered_password = '', string $hashed_password = ''): Response_Handler 
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
