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
     * Sanitize a single value based on its expected type.
     *
     * @param mixed $value
     * @param string $type Supported: text, int, bool, email, url, raw
     * @return mixed
     */
    
    public static function sanitize_value($value, string $type) {
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
