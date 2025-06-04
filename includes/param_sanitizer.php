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
        public readonly bool $type_error,
        public readonly string $type_found,
        public readonly string $expected_type,
        public readonly string|null $type_message,
        public readonly bool $char_limit_exceeded,
        public readonly string|null $char_limit_message,
        public readonly int $char_limit,
        public readonly int $char_length
    ) {}

    /**
     * METHOD - sanitize
     * 
     * Sanitize an array of parameters using a provided schema.
     *
     * @param array $params Associative array of request parameters.
     * @param array $schema Associative array where key = param name, [type = expected type, limit = max length].
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
     * @param bool $type_valid Whether the type is valid.
     * @param mixed $value The value being checked.
     * @param string $expected The expected type.
     * 
     * @return array A response containing the message, type found, and expected type.
     */

    private static function generate_types_response(bool $type_valid, $value, string $expected): array {
        
        // Generate correct type verbiage
        $type_found = 'text';
        switch(gettype($value)) {
            case 'integer':
                $type_found = 'int';
                break;
            case 'boolean':
                $type_found = 'bool';
                break;
            case 'string':
                if ($expected === 'text') {
                    $type_found = 'text';
                }
                if ($expected === 'email' && $type_valid) {
                    $type_found = 'email';
                }
                break;
            default:
                $type_found = gettype($value);
                break;
        }
        
        // Generate response
        return [
            'type_error' => !$type_valid,
            'type_found' => $type_found,
            'expected_type' => $expected,
            'type_message' => 'Expected type `'.$expected.'`, got `' . $type_found . '` with value of `'.$value.'`.'
        ];
    }

    /**
     * METHOD - generate_char_limit_response
     * 
     * Generate a response regarding the character limit of a given value.
     * 
     * @param mixed $value The value being checked.
     * @param string|int|bool|null $expected The expected type with optional character limit.
     * 
     * @return array A response indicating if the character limit was exceeded, along with related messages and values.
     */

    private static function generate_char_limit_response($value, int $expected): array {
        $char_limit = $expected ?? 255;
        $char_length = strlen(strval($value)) ?? 0;
        
        return [
            'char_limit_exceeded' => $char_length > $char_limit,
            'char_limit_message' => 'Value `' . $value . '` has a character limit of `' . $char_limit . '`. The value had a character length of `' . $char_length . '`.',
            'char_limit' => $char_limit,
            'char_length' => $char_length
        ];
    }

    /**
     * METHOD - sanitize_value
     * 
     * Sanitize a value according to its type.
     * 
     * @param string|int|array $value The value to be sanitized.
     * @param array $type The expected type of the value with keys of type, limit.
     * 
     * @return string|int|array The sanitized value.
     */

    public static function sanitize_value($value, array $type): object {
        $valid = false;
        $sanitized = null;
        switch ($type['type']) {
            case 'int':
                // Sanitize integers
                // If the value is numeric, cast it to an integer and return it.
                // Otherwise, return an error message.
                if (is_numeric($value)) {
                    $sanitized =  absint((int)$value);
                    $valid = true;
                } 
                $type_set = self::generate_types_response($valid, $value, 'int');
                $length_set = self::generate_char_limit_response($value, $type['limit']);
                return new static(
                    $valid && !$length_set['char_limit_exceeded'],
                    $sanitized,
                    $type_set['type_error'],
                    $type_set['type_found'],
                    $type_set['expected_type'],
                    $type_set['type_message'],
                    $length_set['char_limit_exceeded'],
                    $length_set['char_limit_message'],
                    $length_set['char_limit'],
                    $length_set['char_length']
                );
            case 'bool':
                // Sanitize booleans
                // If the value is a boolean, or a string that can be interpreted as a boolean, return it.
                // Otherwise, return an error message.
                if (is_bool($value) || in_array(strtolower((string)$value), ['true', 'false', '0', '1'], true)) {
                    $sanitized = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    $valid = true;
                }
                $type_set = self::generate_types_response($valid, $value, 'bool');
                $length_set = self::generate_char_limit_response($value, $type['limit']);
                return new static(
                    $valid && !$length_set['char_limit_exceeded'],
                    $sanitized,
                    $type_set['type_error'],
                    $type_set['type_found'],
                    $type_set['expected_type'],
                    $type_set['type_message'],
                    $length_set['char_limit_exceeded'],
                    $length_set['char_limit_message'],
                    $length_set['char_limit'],
                    $length_set['char_length']
                );
            case 'email':
                // Sanitize emails
                // If the value is not a string, return an error message.
                // Otherwise, sanitize the email using the sanitize_email function.
                // If the sanitized email is not a valid email, return an error message, otherwise return the value.
                $type_set = null;
                if (!is_string($value)) {
                    $type_set = self::generate_types_response(false, $value, 'email');
                }
                $sanitized = sanitize_email($value);
                if (!is_email($sanitized)) {
                    $type_set = self::generate_types_response(false, $value, 'email');
                }
                $length_set = self::generate_char_limit_response($value, $type['limit']);
                if ($type_set !== null) {
                    return new static(
                        false,
                        null,
                        $type_set['type_error'],
                        $type_set['type_found'],
                        $type_set['expected_type'],
                        $type_set['type_message'],
                        $length_set['char_limit_exceeded'],
                        $length_set['char_limit_message'],
                        $length_set['char_limit'],
                        $length_set['char_length']
                    );
                }
                $type_set = self::generate_types_response(true, $value, 'email');
                return new static(
                    true && !$length_set['char_limit_exceeded'],
                    $sanitized,
                    $type_set['type_error'],
                    $type_set['type_found'],
                    $type_set['expected_type'],
                    $type_set['type_message'],
                    $length_set['char_limit_exceeded'],
                    $length_set['char_limit_message'],
                    $length_set['char_limit'],
                    $length_set['char_length']
                );
            case 'url':
                // Sanitize URLs
                // If the value is not a string, return an error message.
                // Otherwise, sanitize the URL using the esc_url_raw function.
                // If the sanitized URL is not a valid URL, return an error message, otherwise return the value.
                $type_set = null;
                if (!is_string($value)) {
                    $type_set =  self::generate_types_response(false, $value, 'url');
                }
                $sanitized = esc_url_raw($value);
                if (!filter_var($sanitized, FILTER_VALIDATE_URL)) {
                    $type_set =  self::generate_types_response(false, $value, 'url');
                } 
                $length_set = self::generate_char_limit_response($value, $type['limit']);
                if ($type_set !== null) {
                    return new static(
                        false,
                        null,
                        $type_set['type_error'],
                        $type_set['type_found'],
                        $type_set['expected_type'],
                        $type_set['type_message'],
                        $length_set['char_limit_exceeded'],
                        $length_set['char_limit_message'],
                        $length_set['char_limit'],
                        $length_set['char_length']
                    );
                }
                $type_set = self::generate_types_response(true, $value, 'url');
                return new static(
                    true,
                    $sanitized,
                    $type_set['type_error'],
                    $type_set['type_found'],
                    $type_set['expected_type'],
                    $type_set['type_message'],
                    $length_set['char_limit_exceeded'],
                    $length_set['char_limit_message'],
                    $length_set['char_limit'],
                    $length_set['char_length']
                );
            case 'raw':
                // Return the value as is
                // No sanitization is done.
                $type_set = self::generate_types_response(true, $value, 'raw');
                $length_set = self::generate_char_limit_response($value, $type['limit']);
                return new static(
                    true,
                    $value,
                    $type_set['type_error'],
                    $type_set['type_found'],
                    $type_set['expected_type'],
                    $type_set['type_message'],
                    $length_set['char_limit_exceeded'],
                    $length_set['char_limit_message'],
                    $length_set['char_limit'],
                    $length_set['char_length']
                );
            case 'text':
            default:
                // Sanitize text
                // If the value is not a string, return an error message.
                // Otherwise, sanitize the text using the sanitize_text_field function and return it.
                if (is_string($value)) $valid = true;
                $type_set = self::generate_types_response($valid, $value, 'text');
                $length_set = self::generate_char_limit_response($value, $type['limit']);
                return new static(
                    $valid,
                    $valid ? sanitize_text_field($value) : null,
                    $type_set['type_error'],
                    $type_set['type_found'],
                    $type_set['expected_type'],
                    $type_set['type_message'],
                    $length_set['char_limit_exceeded'],
                    $length_set['char_limit_message'],
                    $length_set['char_limit'],
                    $length_set['char_length']
                );
        }
    }
}