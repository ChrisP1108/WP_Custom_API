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
     * PROPERTY - ok
     * 
     * @var bool Indicates if the response is successful
     */

    readonly public bool $ok;

    /**
     * PROPERTY - message
     * 
     * @var string The message describing the response
     */

    readonly public string $message;

    /**
     * PROPERTY - data
     * 
     * @var array|null The data associated with the response
     */

    readonly public array|null $data;

    /**
     * PROPERTY - error_response
     * 
     * @var WP_REST_Response The error response object
     */

    readonly public WP_REST_Response $error_response;

    /**
     * PROPERTY - success_response
     * 
     * @var WP_REST_Response The success response object
     */
    
    readonly public WP_REST_Response $success_response;

    /**
     * METHOD - response
     * 
     * Response method to standardize the response data
     * 
     * @param bool $ok
     * @param int $status_code
     * @param string $message
     * @param array|null $data
     * @return object
     */
    public static function response(bool $ok, int $status_code, string $message = '', array|null|string|bool $data = null, bool $parse_responses = true): object
    {
        $return_data = new self();
        $return_data->message = $message;;
        $return_data->data = $data;

        if (!$ok && $parse_responses) {
            $error_response_data = [
                'message' => $message
            ];
            $return_data->error_response = new WP_REST_Response($error_response_data, $status_code);
        } else if ($parse_responses){
            $success_response_data = [
                'message' => $message,
                'data' => $data
            ];
            $return_data->success_response = new WP_REST_Response($success_response_data, $status_code);
        }

        $return_data->ok = $ok;

        return $return_data;
    }
}
