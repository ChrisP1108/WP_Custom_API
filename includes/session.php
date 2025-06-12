<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Config;
use WP_Custom_API\Includes\Response_Handler;

final class Session
{

    /**
     * METHOD - generate
     * 
     * Generate a session with the given parameters
     * 
     * @param string $token_name  Token name
     * @param int $id        User id
     * @param string $nonce     Nonce used for validation
     * @param int $expiration  Timestamp when session will expire
     * @return Response_Handler    Response object
     */

    public static function generate(string $token_name, int $id, string $nonce, int $expiration): Response_Handler
    {

        // Set data for transient
        $data = [
            'nonce' => $nonce,
            'issued_at' => time(),
            'expiration' => $expiration,
            'additionals' => []
        ];

        // Set transient for session storage
        $transient = set_transient(
            Config::AUTH_TOKEN_PREFIX . $token_name . '_' . $id,
            $data,
            $expiration
        );

        // Generate response
        $ok = $transient !== false;
        $return_data = Response_Handler::response($ok, $ok ? 200 : 500, $ok ? "Session generated successfully." : "Session generation failed.", $data);

        do_action('wp_custom_api_session_generated_response', $return_data);
        return $return_data;
    }

    /**
     * METHOD - get
     * 
     * Retrieve a session based on user ID and token name.
     *
     * @param int $id User ID
     * @param string $token_name Token name
     * @return Response_Handler Response object containing session data or error details
     */

    public static function get(string $token_name, int $id): Response_Handler
    {
        // Retrieve session data from transient storage
        $transient = get_transient(Config::AUTH_TOKEN_PREFIX . $token_name . '_' . $id);

        // Determine if retrieval was successful
        $ok = $transient !== false;

        // Return a standardized response
        return Response_Handler::response(
            $ok,
            $ok ? 200 : 500,
            $ok ? "Session retrieved successfully." : "Session retrieval failed.",
            $transient
        );
    }

    /**
     * METHOD - set_additional
     * 
     * Retrieves the session, updates the additional data, and then saves the session.
     * 
     * @param string $token_name Token name
     * @param int $id User ID
     * @param string $key The key to add to the additional session data
     * @param string|bool|int|null $value The value to store for the key
     * @return Response_Handler Response object containing session data or error details
     */

    public static function set_additional(string $token_name, int $id, string $key, string|bool|int|null $value): Response_Handler
    {
        // Retrieve the session
        $update_transient = self::get($token_name, $id);
        // If retrieval failed, return the error response
        if (!$update_transient->ok) return $update_transient;

        // Set the additional key value pair
        $update_transient['additionals'][$key] = $value;

        // Save the session
        $transient = set_transient(
            Config::AUTH_TOKEN_PREFIX . $token_name . '_' . $id,
            $update_transient,
            $update_transient['expiration']
        );

        // Determine if the update was successful
        $ok = $transient !== false;

        // Return a standardized response
        $return_data = Response_Handler::response(
            $ok,
            $ok ? 200 : 500,
            $ok ? "Session updated successfully." : "Session update failed.",
            $transient
        );

        do_action('wp_custom_api_session_generated_response', $return_data);
        return $return_data;
    }

    /**
     * METHOD - delete
     * 
     * Delete a session based on user ID and token name.
     * 
     * @param string $token_name Token name
     * @param int $id User ID
     * @return Response_Handler Response object containing information about the deletion
     */

    public static function delete(string $token_name, int $id): Response_Handler
    {
        $delete_transient = delete_transient(Config::AUTH_TOKEN_PREFIX . $token_name . '_' . $id);

        // Determine if the update was successful
        $ok = $delete_transient !== false;
        return Response_Handler::response(
            $ok,
            $ok ? 200 : 500,
            $ok ? "Session deleted successfully." : "Session deletion failed."
        );
    }
}
