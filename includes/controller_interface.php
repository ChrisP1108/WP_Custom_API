<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_REST_Request;
use WP_REST_Response;
use WP_Custom_API\Includes\Database;
use WP_Custom_API\Includes\Param_Sanitizer;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

/** 
 * Interface for controller classes. 
 * This acts as a parent class for controller classes to provide request and response helper methods.
 */

class Controller_Interface
{

    /**
     * The constructor is declared private to prevent instantiation of this class.
     * 
     * @param array $request_data The sanitized request data.
     * @param array $request_files The request files.
     * @param array $request_headers The request headers.
     * @param int $status_code The HTTP status code to return.
     * @param bool $ok Indicates if the operation was successful.
     * @param string $message The message to return.
     * @param array $missing_keys The missing keys from the request data.
     * @param array $invalid_types The invalid types found in the request data.
     * @param array $keys_not_meetings_char_requirements Keys that didn't satisfy character requirements for maximum/minimum.
     */

    private function __construct(
        public readonly array $request_data,
        public readonly array $request_files,
        public readonly array $request_headers,
        public readonly int $status_code,
        public readonly bool $ok,
        public readonly string $message,
        public readonly array $missing_keys,
        public readonly array $invalid_types,
        public readonly array $keys_not_meeting_char_requirements
    ) {}

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
     * 
     * @return static An object containing the sanitized request data and a flag indicating if the operation was successful.
     */

    final public static function request_handler(WP_REST_Request $req, array $schema = []): static
    {
        $params = $req->get_params() ?? [];
        $json = json_decode($req->get_body(), true) ?? [];
        $form = $req->get_body_params() ?? [];
        $files = $req->get_file_params() ?? [];
        $headers = $req->get_headers() ?? [];

        // Map out schema for checking data types
        $schema_data_types = [];

        foreach($schema as $key => $s) {
            $schema_data_types[$key] =  [
                'type' => $s['type'],
                'maximum' => $s['maximum'],
                'minimum'  => $s['minimum'],
            ];
        }

        // Sanitize the request data according to the schema
        $sanitized_params = [
            'params' => Param_Sanitizer::sanitize($params, $schema_data_types),
            'json'   => Param_Sanitizer::sanitize($json, $schema_data_types),
            'form'   => Param_Sanitizer::sanitize($form, $schema_data_types)
        ];

        // Merge the sanitized data
        $merged_sanitized_params = array_merge(
            $sanitized_params['params'],
            $sanitized_params['json'],
            $sanitized_params['form']
        );

        // Check if the sanitized data contains any invalid types
        $invalid_types = [];

        foreach ($merged_sanitized_params as $key => $value) {
            if ($value->type_error) {
                $invalid_types[] = [
                    'key' => $key,
                    'type_message' => $value->type_message,
                    'type_found' => $value->type_found,
                    'expected_type' => $value->expected_type
                ];
            }
        }

        // Set to check if required keys are present in the sanitized data and check it against the schema to make sure no required keys are missing from the schema, along with character maximums and minimums..
        $missing_keys = [];
        $keys_not_meeting_char_requirements = [];

        // Loop through the schema to make sure all required keys from sanitized data are present, along with making sure all parameter values do not exceed character maximum.
        foreach ($schema as $key => $params) {
            $required_key = $params['required'] ?? true;
            if ($required_key && !array_key_exists($key, $merged_sanitized_params)) {
                $missing_keys[] = $key;
                continue;
            }
            $value = $merged_sanitized_params[$key];
            if (!is_array($value)) {
                if ($value->char_maximum_exceeded || !$value->char_minimum_met) {
                    $keys_not_meeting_char_requirements[] = [
                        'key' => $key,
                        'is_array' => false,
                        'message' => 'Key of `' . $key . '` did not satisfy the character maximum or minimum length requirements.',
                        'char_maximum' => $value->char_maximum,
                        'char_maximum_message' => $value->char_maximum_message,
                        'char_length_exceeded' => $value->char_maximum_exceeded,
                        'char_length' => $value->char_length,
                        'char_minimum' => $value->char_minimum,
                        'char_minimum_met' => $value->char_minimum_met,
                        'char_minimum_message' => $value->char_minimum_message
                    ];
                }
            } else if (is_array($value)) {
                foreach ($value as $nested_key => $nested_value) {
                    if ($nested_value->char_maximum_exceeded || !$nested_value->char_minimum_met) {
                        $keys_not_meeting_char_requirements[] = [
                            'key' => $nested_key,
                            'array_key' => $key,
                            'is_array' => true,
                            'message' => 'Key of `' . $key . '` has a nested array key of `' . $nested_key . '` that does not satisfy the character maximum or minimum length requirements.',
                            'char_maximum' => $nested_value->char_maximum,
                            'char_maximum_message' => $nested_value->char_maximum_message,
                            'char_length_exceeded' => $nested_value->char_maximum_exceeded,
                            'char_length' => $nested_value->char_length,
                            'char_minimum' => $nested_value->char_minimum,
                            'char_minimum_met' => $nested_value->char_minimum_met,
                            'char_minimum_message' => $nested_value->char_minimum_message
                        ];
                    }
                }
            }
        }

        // Set if ok
        $ok = empty($missing_keys) && empty($invalid_types) && empty($keys_not_meeting_char_requirements);

        // Set for message 
        $message = null;

        // Set for status code
        $status_code = null;

        if (!empty($missing_keys)) {

            // Handle the case where missing keys or invalid data types are present
            $message = 'The following keys are required: `' . implode(', ', $missing_keys) . '`.';
            $status_code = 400;
        } else if (!empty($invalid_types)) {

            // Handle the case where there are invalid data types
            $invalid_keys = array_map(function ($item) {
                return $item['key'];
            }, $invalid_types);
            $message = 'Invalid data types found for `' . implode(', ', $invalid_keys) . '`.';
            $status_code = 422;
        } else if (!empty($keys_not_meeting_char_requirements)) {
            $keys_not_meeting_char_requirements_array = array_map(function ($item) {
                return $item['key'];
            }, $keys_not_meeting_char_requirements);
            $message = 'The following keys did not satisfy the character maximum or minimum length requirements: `' . implode(', ', $keys_not_meeting_char_requirements_array) . '`';
            $status_code = 400;
        } else {

            // Handle the case where everything is ok
            $status_code = 200;
            $message = 'Success';
        }

        return new static(
            $merged_sanitized_params,
            $files,
            $headers,
            $status_code,
            $ok,
            $message,
            $missing_keys,
            $invalid_types,
            $keys_not_meeting_char_requirements
        );
    }

    /**
     * METHOD - compile_request_data
     * 
     * Compiles the sanitized parameters into a single array.
     * 
     * Loops through the given array of sanitized parameters and builds a new array containing only the keys and values of the sanitized parameters where the `ok` property is true.
     * 
     * @param array|object $data An array of sanitized parameters.
     * 
     * @return array|bool A compiled array containing only the keys and values of the sanitized parameters where the `ok` property is true.  Returns false if one or more data object had an ok key with a value of false.
     */

    final public static function compile_request_data(array|object $data): array|bool
    {
        $compiled_data = [];

        if(isset($data->request_data)) {
            foreach ((array) $data->request_data ?? [] as $key => $object) {
                if ($object->ok) {
                    $compiled_data[$key] = $object->value;
                } else return false;
            }
        } else {
            foreach ((array) $data as $key => $object) {
                if ($object->ok) {
                    $compiled_data[$key] = $object->value;
                } else return false;
            }
        }
        return $compiled_data;
    }

    /**
     * METHOD - set_headers
     * 
     * Sets HTTP headers.
     * 
     * @param array $headers A key-value array of headers to be set.
     * 
     * @return void
     */

    final public static function set_headers(array $headers): void
    {
        // Check if headers are set
        if (!headers_sent()) {

            // Loop through the headers and set them using the header() function
            foreach ($headers as $key => $value) {
                header($key . ': ' . $value);
            }
        }
    }

    /**
     * METHOD - response
     * 
     * Handles the construction of a WP_REST_Response object.
     *
     * @param object|null|array|bool $response An object (or null) containing response data including 'ok', 'message', and 'data'.
     * @param bool $ok A default flag indicating if the operation was successful.
     * @param string $message A default message to return in the response.
     * 
     * @return WP_REST_Response A response object with the appropriate status code and data.
     */

    final public static function response(object|null|array|bool $response, int $status_code = 200, string|null $message = null): WP_REST_Response
    {
        $parsed_response = [];

        // Parse response message
        if ($message !== null || isset($response->message)) {
            $parsed_response['message'] =  isset($response->message) ? $response->message : $message;
        }

        // Parse response status code
        $response_status_code = isset($response->status_code) && $response->status_code !== null ? $response->status_code : $status_code;

        // Check if the response contains a validation error and return appropriate response.
        $validation_error = false;

        if (isset($response->ok) && !$response->ok) {

            if (isset($response->missing_keys) && !empty($response->missing_keys) && !$validation_error) {
                $parsed_response['missing_keys'] = (array) $response->missing_keys;
                $validation_error = true;
            }
            if (isset($response->invalid_types) && !empty($response->invalid_types) && !$validation_error) {
                $parsed_response['invalid_types'] = (array) $response->invalid_types;
                $validation_error = true;
            }
            if (isset($response->keys_not_meeting_char_requirements) && !empty($response->keys_not_meeting_char_requirements) && !$validation_error) {
                $parsed_response['keys_not_meeting_char_requirements'] = (array) $response->keys_not_meeting_char_requirements;
                $validation_error = true;
            }

            return new WP_REST_Response($parsed_response, $response_status_code);
        }

        // Check if the response contains an error or success response and return it
        if (isset($response->error_response) && $response->error_response) return $response->error_response;
        if (isset($response->success_response) && $response->success_response) return $response->success_response;

        // Parse response data
        if ($response !== null) {

            $data_array = null;
            
            // Check that response wasn't an associative array, if so, add it to data.  Otherwise use the data key from the object
            if (isset($response->data) && is_object($response->data)) {

                // Set data if response is an associaitve array
                $data_array = (array) $response->data;
            } else {
                $data_array = (array) $response;
            }

            $keys_not_to_display = ['hash', 'nonce', 'token_name', 'first_issued_at', 'expiration_at', 'updated_tally', 'last_updated_at', 'additionals'];

            foreach($keys_not_to_display as $key) {
                if (isset($data_array[$key])) {
                    unset($data_array[$key]);
                }
            }

            $parsed_response['data'] = $data_array;
        }

        return new WP_REST_Response($parsed_response, $response_status_code);
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
     * @param string|int $maximum The number of items per page.
     * @param string|int $page The current page number.
     * 
     * @return void
     */

    final public static function pagination_headers(string|int $total_rows, string|int $total_pages, string|int $maximum, string|int $page): void
    {
        Database::pagination_headers($total_rows, $total_pages, $maximum, $page);
    }
}
