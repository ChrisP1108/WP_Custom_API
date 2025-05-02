<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_REST_Request;
use WP_REST_Response;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) {
    exit;
}

/** 
 * Interface for controller classes. 
 * This acts as a parent class for controller classes to provide request and response helper methods.
 * 
 * @since 1.0.0
 */

class Controller_Interface
{

    /**
     * A helper method to parse the request and extract any required keys.
     *
     * This method takes a WP_REST_Request and an array of required keys. It merges the request query parameters and body, and then
     * checks if all required keys are present. If any required keys are missing, the method returns an associative array with the
     * parsed data, an 'ok' flag set to false, an error message, and an error response object.
     *
     * @param WP_REST_Request $req The request object to parse.
     * @param array $required_keys The required keys to check for.
     * @return array An associative array containing the parsed data, an 'ok' flag, an error message, and an error response object.
     */

    final public static function request_parser(WP_REST_Request $req, array $required_keys = []): array
    {
        $params = $req->get_params() ?? [];
        $json = json_decode($req->get_body(), true) ?? [];

        $all_request_data = array_merge($params, $json);

        $missing_keys = array_filter($required_keys, function ($key) use ($all_request_data) {
            return !array_key_exists($key, $all_request_data);
        });

        $response_data = [
            'data' => $all_request_data,
            'ok' => empty($missing_keys)
        ];

        if (!empty($missing_keys)) {
            $response_data['missing_keys'] = $missing_keys;
            $response_data['error_response'] = new WP_REST_Response(
                [
                    'ok' => false,
                    'message' => 'The following keys are required: `' . implode(', ', $missing_keys) .'`.',
                    'data' => null
                ],
                400
            );
        } else {
            $response_data['message'] = 'Success';
        }

        return $response_data;
    }


    /**
     * Handles the construction of a WP_REST_Response object.
     *
     * @param array $response An array containing response data including 'ok', 'message', and 'data'.
     * @param bool $ok A default flag indicating if the operation was successful.
     * @param string $message A default message to return in the response.
     * @return WP_REST_Response A response object with the appropriate status code and data.
     */

    final public static function response_handler(array $response, int $status_code = 200, string $message = ''): WP_REST_Response
    {

        $parsed_response = [
            'ok' => $status_code < 300 ? true : false,
            'message' => isset($response['message']) ? $response['message'] : $message,
            'data' => isset($response['data']) ? $response['data'] : null
        ];

        if (isset($response['error_response'])) {
            return $response['error_response'];
        }

        return new WP_REST_Response($parsed_response, $status_code);
    }
}
