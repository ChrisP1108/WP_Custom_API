<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_REST_Request;
use WP_REST_Response;
use WP_Custom_API\Includes\Database;
use WP_Custom_API\Includes\Response_Handler;
use WP_Custom_API\Includes\Param_Sanitizer;

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
     * METHOD - request_handler
     * 
     * Parse the request data and sanitize it according to the provided schema.
     * 
     * This method will take the request data from the request object, and sanitize it according to the provided schema.
     * It will also check if the required keys are present in the sanitized data.
     * 
     * @param WP_REST_Request $req The request object to parse.
     * @param array $schema The schema to use for sanitizing the request data.
     * @param array $required_keys The required keys to check if they are present in the sanitized data.
     * 
     * @return array An array containing the sanitized request data and a flag indicating if the operation was successful.
     */

    final public static function request_handler(WP_REST_Request $req, array $schema = [], array $required_keys = []): array
    {
        $params = $req->get_params() ?? [];
        $json = json_decode($req->get_body(), true) ?? [];
        $form = $req->get_body_params() ?? [];
        $files = $req->get_file_params() ?? [];

        // Sanitize the request data according to the schema
        $sanitized_params = [
            'params' => Param_Sanitizer::sanitize($params, $schema),
            'json'   => Param_Sanitizer::sanitize($json, $schema),
            'form'   => Param_Sanitizer::sanitize($form, $schema)
        ];

        // Merge the sanitized data
        $merged_sanitized_params = array_merge(
            $sanitized_params['params'],
            $sanitized_params['json'],
            $sanitized_params['form']
        );

        // Check if the sanitized data contains any invalid types
        $invalid_types = [];

        foreach($merged_sanitized_params as $key => $value) {
            if (isset($value['error_response'])) {
                $invalid_types[] = [
                    'key' => $key,
                    'error_response' => $value['error_response']
                ];
            }
        }

        // Check if the required keys are present in the sanitized data and check it against the schema to make sure no required keys are missing from the schema.
        $missing_keys = [];
        $missing_schema_keys = [];

        foreach($required_keys as $key) {
            if (!isset($schema[$key])) {
                $missing_schema_keys[] = ['key' => $key, 'error_response' => 'The `' . $key . '` needs to be defined in the schema.'];
            } else if (!array_key_exists($key, $merged_sanitized_params)) {
                $missing_keys[] = $key;
            }
        }

        // Construct the response data
        $response_data = [
            'ok' => empty($missing_keys) && empty($invalid_types) && empty($missing_schema_keys)
        ];

        // Handle the case where required keys are missing from the schema
        if (!empty($missing_schema_keys)) {
            $response_data['missing_schema_keys'] = $missing_schema_keys;
            $missing_schema_key_names = array_map(function($item) { return $item['key']; }, $missing_schema_keys); 
            $err_msg = 'The following keys must be defined in the schema: `' . implode(', ', $missing_schema_key_names) . '`.';
            $response_data['message'] = $err_msg;
            $response_data['validation_error_status'] = 500;

        // Handle the case where missing keys or invalid data types are present
        } else if (!empty($missing_keys)) {
            $response_data['missing_keys'] = $missing_keys;
            $err_msg = 'The following keys are required: `' . implode(', ', $missing_keys) . '`.';
            $response_data['message'] = $err_msg;
            $response_data['validation_error_status'] = 400;

        // Handle the case where there are invalid data types
        } else if (!empty($invalid_types)) {
            $response_data['invalid_types'] = $invalid_types;
            $invalid_keys = array_map(function($item) {
                return $item['key'];
            }, $invalid_types);
            $err_msg = 'Invalid data types found for `' . implode(', ', $invalid_keys) . '`.';
            $response_data['message'] = $err_msg;
            $response_data['validation_error_status'] = 422;
        } else {
            $response_data['data'] = [
                'data_params' => $merged_sanitized_params,
                'file_params' => $files
            ];
            $response_data['message'] = 'Success.';
        }

        return $response_data;
    }

    /**
     * METHOD - get_param_data
     * 
     * Returns the sanitized and merged parameter data that was generated from a successful request handling from the request_handler method.
     * 
     * @param array $params A request object containing parameter data.
     * @return array|null The sanitized and merged parameter data.
     */

    final public static function get_param_data($params): array|null {
        return isset($params['data']['data_params']) ? $params['data']['data_params'] : null;
    }

    /**
     * METHOD - get_file_data
     * 
     * Returns the sanitized and merged file data that was generated from a successful request handling from the request_handler method.
     * 
     * @param array $params A request object containing file data.
     * @return array|null The sanitized and merged file data.
     */

    final public static function get_file_data($params): array|null {
        return isset($params['data']['file_params']) ? $params['data']['file_params'] : null;
    }

    /**
     * METHOD - response
     * 
     * Handles the construction of a WP_REST_Response object.
     *
     * @param array $response An array containing response data including 'ok', 'message', and 'data'.
     * @param bool $ok A default flag indicating if the operation was successful.
     * @param string $message A default message to return in the response.
     * @return WP_REST_Response A response object with the appropriate status code and data.
     */

    final public static function response(array|null|string|int|bool $response, int $status_code = 200, string|null $message = null): WP_REST_Response
    {
        $parsed_response = [];
        
        // Parse response message
        if ($message !== null || isset($response['message'])) {
            $parsed_response['message'] =  isset($response['message']) ? $response['message'] : $message;
        }

        // Check if the response contains a validation error and return appropriate response.
        if (isset($response['validation_error_status'])) {
            $status_code = $response['validation_error_status'];
            unset($response['validation_error_status']);
            unset($response['ok']);
            return new WP_REST_Response($response, $status_code);
        }

        // Check if the response contains an error or success response and return it
        if (isset($response['error_response'])) return $response['error_response'];
        if (isset($response['success_response'])) return $response['success_response'];

        // Parse response data
        if ($response !== null) {
            $parsed_response['data'] = isset($response['data']) ? $response['data'] : $response;
            if (isset($parsed_response['data']['data_params'])) {
                $parsed_response['data'] = $parsed_response['data']['data_params'];
            }
        }

        return new WP_REST_Response($parsed_response, $status_code);
    }

    /**
     * METHOD - pagination_params
     * 
     * A helper method to retrieve pagination parameters from the Database class.
     *
     * This method calls the static pagination_params method from the Database class
     * to obtain pagination details such as per_page, page, and offset.
     *
     * @return array An associative array containing pagination parameters.
     */

    final public static function pagination_params(): array 
    {
        return Database::pagination_params();
    }

    /**
     * METHOD - pagination_headers
     * 
     * A helper method to set pagination headers for the response.
     *
     * This method utilizes the pagination_headers method from the Database class
     * to set headers such as total rows, total pages, items per page, and the current page.
     *
     * @param string|int $total_rows The total number of rows.
     * @param string|int $total_pages The total number of pages.
     * @param string|int $limit The number of items per page.
     * @param string|int $page The current page number.
     * @return void
     */

    final public static function pagination_headers(string|int $total_rows, string|int $total_pages, string|int $limit, string|int $page): void 
    {
        Database::pagination_headers($total_rows, $total_pages, $limit, $page);
    }
}
