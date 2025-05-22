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
 * Used to create standardized responses throughtout the plugin.
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
            $error_response = self::build_error_response($message, $status_code);
        } else if ($parse_responses){
            $success_response = self::build_success_response($message, $status_code, $data);
        }

        return new static(
            $ok, 
            $status_code, 
            $message, $data, 
            $error_response, 
            $success_response
        );
    }

    /**
     * METHOD - build_error_response
     * 
     * Builds and returns a WP_REST_Response with an error message and status code
     * 
     * @param string $message - The error message to return
     * @param int $status_code - The status code to return
     * @return WP_REST_Response - The WP_REST_Response object
     */

    private static function build_error_response(string $message, int $status_code): WP_REST_Response {
        return new WP_REST_Response(['message' => $message], $status_code);
    }

    /**
     * METHOD - build_success_response
     * 
     * Builds and returns a WP_REST_Response with a success message and status code
     * 
     * @param string $message - The success message to return
     * @param int $status_code - The status code to return
     * @param array|null|string|bool $data - The data to return
     * @return WP_REST_Response - The WP_REST_Response object
     */

    private static function build_success_response(string $message, int $status_code, array|null|string|bool $data): WP_REST_Response {
        $response = ['message' => $message];
        if (!is_array($data) || !isset($data['hash'])) {
            $response['data'] = $data;
        }
        return new WP_REST_Response($response, $status_code);
    }
}
