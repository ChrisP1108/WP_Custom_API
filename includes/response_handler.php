<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_REST_Response;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Used to create standardized responses
 */
final class Response_Handler
{
    /**
     * METHOD - response
     * 
     * Response method to standardize the response data
     * 
     * @param bool $ok
     * @param int $status_code
     * @param string $message
     * @param array|null $data
     * @return array
     */
    public static function response(bool $ok, int $status_code, string $message = '', ?array $data = null): array
    {
        $return_data = ['ok' => $ok, 'message' => $message, 'data' => $data];

        if (!$ok) {
            $return_data['error_response'] = new WP_REST_Response($return_data, $status_code);
        } else {
            $return_data['success_response'] = new WP_REST_Response($return_data, $status_code);
        }

        return $return_data;
    }
}
