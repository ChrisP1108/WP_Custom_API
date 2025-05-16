<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for sanitizing parameters based on a predefined schema.
 */

class Param_Sanitizer {

    /**
     * METHOD - sanitize
     * 
     * Sanitize an array of parameters using a provided schema.
     *
     * @param array $params Associative array of request parameters.
     * @param array $schema Associative array where key = param name, value = expected type.
     * @return array Sanitized parameters.
     */

    public static function sanitize(array $params, array $schema): array {
        $sanitized = [];

        foreach ($schema as $key => $type) {
            if (!isset($params[$key])) {
                continue;
            }

            $value = $params[$key];

            if (is_array($value)) {
                $sanitized[$key] = array_map(function ($v) use ($type) {
                    return self::sanitize_value($v, $type);
                }, $value);
            } else {
                $sanitized[$key] = self::sanitize_value($value, $type);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize a value according to its type.
     * 
     * @param string|int|array $value The value to be sanitized.
     * @param string $type The expected type of the value.
     * @return string|int|array The sanitized value.
     */

    public static function sanitize_value($value, string $type): string|int|array {
        switch ($type) {
            case 'int':
                // Sanitize integers
                // If the value is numeric, cast it to an integer and return it.
                // Otherwise, return an error message.
                if (is_numeric($value)) {
                    return absint((int)$value);
                }
                return ['error_response' => "Expected type `int`, got `" . gettype($value) . "` with value `$value`."];
            case 'bool':
                // Sanitize booleans
                // If the value is a boolean, or a string that can be interpreted as a boolean, return it.
                // Otherwise, return an error message.
                if (is_bool($value) || in_array(strtolower((string)$value), ['true', 'false', '0', '1'], true)) {
                    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                return ['error_response' => "Expected type `bool`, got `" . gettype($value) . "` with value `$value`."];
            case 'email':
                // Sanitize emails
                // If the value is not a string, return an error message.
                // Otherwise, sanitize the email using the sanitize_email function.
                // If the sanitized email is not a valid email, return an error message, otherwise return the value.
                if (!is_string($value)) {
                    return ['error_response' => "Expected type `email` as string, got `" . gettype($value) . "`."];
                }
                $sanitized = sanitize_email($value);
                if (!is_email($sanitized)) {
                    return ['error_response' => "Invalid email format for value `$value`."];
                }
                return $sanitized;
            case 'url':
                // Sanitize URLs
                // If the value is not a string, return an error message.
                // Otherwise, sanitize the URL using the esc_url_raw function.
                // If the sanitized URL is not a valid URL, return an error message, otherwise return the value.
                if (!is_string($value)) {
                    return ['error_response' => "Expected type `url` as string, got `" . gettype($value) . "`."];
                }
                $sanitized = esc_url_raw($value);
                if (!filter_var($sanitized, FILTER_VALIDATE_URL)) {
                    return ['error_response' => "Invalid URL format for value `$value`."];
                }
                return $sanitized;
            case 'raw':
                // Return the value as is
                // No sanitization is done.
                return $value;
            case 'text':
            default:
                // Sanitize text
                // If the value is not a string, return an error message.
                // Otherwise, sanitize the text using the sanitize_text_field function and return it.
                if (!is_string($value)) {
                    return ['error_response' => "Expected type `text` as string, got `" . gettype($value) . "`."];
                }
                return sanitize_text_field($value);
        }
    }
}