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
     * METHOD - sanitize_value
     * 
     * Sanitize a value according to its expected type.  Will return an array with an error key and message if type didn't match actual value type.
     *
     * @param mixed $value The value to be sanitized.
     * @param string $type The expected type of the value.
     * @return mixed The sanitized value.
     */

    public static function sanitize_value($value, string $type): string|int|array {
        
        // Check that the value type matches the expected type
        switch (gettype($value)) {
            case 'integer':
                if ($type !== 'int') {
                    return ['error_response' => "Expected type `$type` does not match provided value type `integer`."];
                }
                break;
            case 'boolean':
                if ($type !== 'bool') {
                    return ['error_response' => "Expected type `$type` does not match provided value type `boolean`."];
                }
                break;
            case 'string':
                if (!in_array($type, ['text', 'email', 'url', 'raw'])) {
                    return ['error_response' => "Expected type `$type` does not match provided value type `string`."];
                }
                break;
            default:
                return ['error' => 'Unsupported value type `' . gettype($value) . '`.'];
        }

        // Sanitize the value according to the expected type
        switch ($type) {
            case 'int':
                return absint($value);
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'email':
                return sanitize_email($value);
            case 'url':
                return esc_url_raw($value);
            case 'raw':
                return $value;
            case 'text':
            default:
                return sanitize_text_field($value);
        }
    }
}
