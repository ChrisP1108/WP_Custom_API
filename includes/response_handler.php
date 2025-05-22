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
     * CONSTRUCTOR
     * 
     * @param bool $ok
     * @param int $status_code
     * @param string $message
     * @param array|null $data
     * @param WP_REST_Response $error_response
     * @param WP_REST_Response $success_response
     */

    private function __construct(
        public readonly bool $ok,
        public readonly int $status_code,
        public readonly string $message,
        public readonly array|null|string|bool $data,
        public readonly WP_REST_Response|null $error_response,
        public readonly WP_REST_Response|null $success_response
    )  {}

    /**
     * METHOD - response
     * 
     * Response method to standardize the response data
     * 
     * @param bool $ok
     * @param int $status_code
     * @param string $message
     * @param array|null $data
     * @return static
     */

    public static function response(bool $ok, int $status_code, string $message = '', array|null|string|bool $data = null, bool $parse_responses = true): static
    {
        $error_response = null;
        $success_response = null;
        if (!$ok && $parse_responses) {
            $error_response_data = [
                'message' => $message
            ];
            $error_response = new WP_REST_Response($error_response_data, $status_code);
        } else if ($parse_responses){
            $success_response_data = [
                'message' => $message,
            ];
            if (!isset($data['hash'])) {
                $success_response_data['data'] = $data;
            }
            $success_response = new WP_REST_Response($success_response_data, $status_code);
        }

        return new static(
            $ok, 
            $status_code, 
            $message, $data, 
            $error_response, 
            $success_response
        );
    }
}
