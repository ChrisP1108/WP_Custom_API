<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_REST_Request;
use WP_REST_Response;
use WP_Custom_API\Includes\Database;
use WP_Custom_API\Includes\Response_Handler;

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
     * METHOD - request_parser
     * 
     * A helper method to parse the request and extract any required keys.
     *
     * This method takes a WP_REST_Request and an array of required keys. It merges the request query parameters and body, and then
     * checks if all required keys are present. If any required keys are missing, the method returns an associative array with the
     * parsed data, an 'ok' flag set to false, an error message, and an error response object.  If successful, the method returns an
     * associative array with the parsed data, an 'ok' flag set to true, with a success response.
     *
     * @param WP_REST_Request $req The request object to parse.
     * @param array $required_keys The required keys to check for.
     * @return array An associative array containing the parsed data, an 'ok' flag, an error message, and an error response object.
     */

    final public static function request_parser(WP_REST_Request $req, array $required_keys = []): array
    {
        $params = $req->get_params() ?? [];
        $json = json_decode($req->get_body(), true) ?? [];
        $form = $req->get_body_params() ?? [];
        $files = $req->get_file_params() ?? [];

        $all_request_data = array_merge($params, $json, $files);

        $missing_keys = array_filter($required_keys, function ($key) use ($all_request_data) {
            return !array_key_exists($key, $all_request_data);
        });

        $response_data = [
            'data' => [
                'params' => !empty($params) ? $params : null,
                'json' => !empty($json) ? $json : null,
                'form' => !empty($form) ? $form : null,
                'files' => !empty($files) ? $files : null
            ],
            'ok' => empty($missing_keys)
        ];

        if (!empty($missing_keys)) {
            $response_data['missing_keys'] = $missing_keys;
            $err_msg = 'The following keys are required: `' . implode(', ', $missing_keys) . '`.';
            $response_data['message'] = $err_msg;
            $response_data['error_response'] = Response_Handler::response(false, 400, $err_msg)['error_response'];
        } else {
            $response_data['message'] = 'Success.';
        }

        return $response_data;
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

    final public static function response($response, int $status_code = 200, string|null $message = null): WP_REST_Response
    {
        $parsed_response = [
            'ok' => $status_code < 300 ? true : false,
            'message' => isset($response['message']) ? $response['message'] : $message,
            'data' => isset($response['data']) ? $response['data'] : null
        ];

        if (!isset($response['message']) && !isset($response['data'])) $parsed_response['data'] = $response;

        if (isset($response['error_response'])) return $response['error_response'];
        if (isset($response['success_response'])) return $response['success_response'];

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
