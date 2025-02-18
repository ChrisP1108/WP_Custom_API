<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Config;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) {
    exit;
}

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
     * @param bool $ok - True or false boolean value if hash was generated or validated successfully.
     * @param string $msg - Message description.
     * @param string $hash - optional - Used only when hashing. Omitted on validation.
     * 
     * @return object - Returns an object with a key name of "ok" with a value of true or false, a "message" key, and an optional string of the password hash.
     * @since 1.0.0
     */

    private static function response(bool $ok = false, string $msg = '', string $hash = ''): array
    {
        $output = ['ok' => $ok, 'msg' => $msg];

        if ($hash !== '') {
            $output['hash'] = $hash;
        }

        return $output;
    }

    /**
     * METHOD - hash
     * 
     * Hashes string with Bcrypt algorithm.  Cost rounds set by PASSWORD_HASH_ROUNDS constant in Config class.
     * 
     * @param string $string - String text to be hashed.
     * 
     * @return array Response indicating success or failure, and the generated hash if successful.
     * @since 1.0.0
     */

    public static function hash(string $string = ''): array|object 
    {
        if ($string === '') {
            return self::response(false, 'String must be provided to hash in Password hash method.');
        }

        $cost = (int) (Config::PASSWORD_HASH_ROUNDS ?? 12);

        $cost = is_int($cost) && $cost <= 20 && $cost >= 8 ? $cost : 12;

        $hash = password_hash($string, PASSWORD_BCRYPT, ['cost' => $cost]);

        if ($hash === false) {
            return self::response(false, 'Failed to hash the string.');
        }

        return self::response(true, 'Password hash successful', $hash);
    }

    /**
     * METHOD - verify
     * 
     * Verifies a plain text password against a hashed password.
     * 
     * @param string $entered_password The plain-text password.
     * @param string $hashed_password The hashed password to verify against.
     * 
     * @return array Response indicating whether verification was successful.
     * @since 1.0.0
     */

    public static function verify(string $entered_password = '', string $hashed_password = ''): array|object 
    {
        if ($entered_password === '' || $hashed_password === '') {
            return self::response(false, 'The entered plain text password and the hashed password must be passed in as parameters in the Password verify method.');
        }
        $result = password_verify($entered_password, $hashed_password);

        return self::response(
            $result, 
            'Password verification ' . ($result ? 'passed.' : 'failed.')
        );
    }
}
