<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

/**
 * Class for sanitizing parameters based on a predefined schema.
 */

class Param_Sanitizer {

    private function __construct(
        public readonly bool $ok,
        public readonly array|string|int|bool|null $value,
        public readonly string $type_found,
        public readonly string $expected_type,
        public readonly string|null $message,
    ) {}

    /**
     * METHOD - sanitize
     * 
     * Sanitize an array of parameters using a provided schema.
     *
     * @param array $params Associative array of request parameters.
     * @param array $schema Associative array where key = param name, value = expected type.
     * 
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
                    $single_value = self::sanitize_value($v, $type);
                    return self::sanitize_value($v, $type);
                }, $value);
            } else $sanitized[$key] = self::sanitize_value($value, $type);
        }

        return $sanitized;
    }

    /**
     * METHOD - generate_types_response
     * 
     * Generate types response.
     * 
     * @param mixed $value The value being checked.
     * @param string $expected The expected type.
     * 
     * @return array A response containing the message, type found, and expected type.
     */

    private static function generate_types_response($value, string $expected): array {
        // Generate an error response with the message, type found, and expected type
        return [
            'message' => 'Expected type `'.$expected.'`, got `' . gettype($value) . '` with value of `'.$value.'`.',
            'type_found' => gettype($value) === 'integer' ? 'int' : (gettype($value) === 'boolean' ? 'bool' : (gettype($value) === 'string' ? 'text' : gettype($value))),
            'expected_type' => $expected
        ];
    }

    /**
     * METHOD - sanitize_value
     * 
     * Sanitize a value according to its type.
     * 
     * @param string|int|array $value The value to be sanitized.
     * @param string $type The expected type of the value.
     * 
     * @return string|int|array The sanitized value.
     */

    public static function sanitize_value($value, string $type): object {
        $valid = false;
        $sanitized = null;
        switch ($type) {
            case 'int':
                // Sanitize integers
                // If the value is numeric, cast it to an integer and return it.
                // Otherwise, return an error message.
                if (is_numeric($value)) {
                    $sanitized =  absint((int)$value);
                    $valid = true;
                } 
                $type = self::generate_types_response($value, 'int');
                return new static(
                    $valid,
                    $sanitized,
                    $type['type_found'],
                    $type['expected_type'],
                    $type['message']
                );
            case 'bool':
                // Sanitize booleans
                // If the value is a boolean, or a string that can be interpreted as a boolean, return it.
                // Otherwise, return an error message.
                if (is_bool($value) || in_array(strtolower((string)$value), ['true', 'false', '0', '1'], true)) {
                    $sanitized = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    $valid = true;
                }
                $type = self::generate_types_response($value, 'bool');
                return new static(
                    $valid,
                    $sanitized,
                    $type['type_found'],
                    $type['expected_type'],
                    $type['message']
                );
            case 'email':
                // Sanitize emails
                // If the value is not a string, return an error message.
                // Otherwise, sanitize the email using the sanitize_email function.
                // If the sanitized email is not a valid email, return an error message, otherwise return the value.
                $type = null;
                if (!is_string($value)) {
                    $type = self::generate_types_response($value, 'email');
                }
                $sanitized = sanitize_email($value);
                if (!is_email($sanitized)) {
                    $type = [
                        'message' => "Invalid email format for value of `$value`.",
                        'type_found' => gettype($value) === 'integer' ? 'int' : gettype($value),
                        'expected_type' => 'email'
                    ];
                }
                if ($type !== null) {
                    return new static(
                        false,
                        null,
                        $type['type_found'],
                        $type['expected_type'],
                        $type['message']
                    );
                }
                $type = self::generate_types_response($value, 'email');
                return new static(
                    true,
                    $sanitized,
                    $type['type_found'],
                    $type['expected_type'],
                    $type['message']
                );
            case 'url':
                // Sanitize URLs
                // If the value is not a string, return an error message.
                // Otherwise, sanitize the URL using the esc_url_raw function.
                // If the sanitized URL is not a valid URL, return an error message, otherwise return the value.
                $type = null;
                if (!is_string($value)) {
                    $type =  self::generate_types_response($value, 'url');
                }
                $sanitized = esc_url_raw($value);
                if (!filter_var($sanitized, FILTER_VALIDATE_URL)) {
                    $type =  [
                        'error_response' => "Invalid URL format for value of `$value`.",
                        'type_found' => gettype($value) === 'integer' ? 'int' : gettype($value),
                        'expected_type' => 'url'
                    ];
                } 
                if ($type !== null) {
                    return new static(
                        false,
                        null,
                        $type['type_found'],
                        $type['expected_type'],
                        $type['message']
                    );
                }
                $type = self::generate_types_response($value, 'url');
                return new static(
                    true,
                    $sanitized,
                    $type['type_found'],
                    $type['expected_type'],
                    $type['message']
                );
            case 'raw':
                // Return the value as is
                // No sanitization is done.
                $type = self::generate_types_response($value, 'raw');
                return new static(
                    true,
                    $value,
                    $type['type_found'],
                    $type['expected_type'],
                    $type['message']
                );
            case 'text':
            default:
                // Sanitize text
                // If the value is not a string, return an error message.
                // Otherwise, sanitize the text using the sanitize_text_field function and return it.
                if (is_string($value)) $valid = true;
                $type = self::generate_types_response($value, 'text');
                return new static(
                    $valid,
                    $valid ? sanitize_text_field($value) : null,
                    $type['type_found'],
                    $type['expected_type'],
                    $type['message']
                );
        }
    }
}