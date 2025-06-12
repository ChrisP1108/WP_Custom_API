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

    public static function generate(string $token_name, int $id, string $nonce, int $expiration_time): Response_Handler
    {
        // Current time
        $current_time = time();

        // Set data for transient
        $data = [
            'id' => $id,
            'token_name' => $token_name,
            'nonce' => $nonce,
            'issued_at' => $current_time,
            'expiration' => $expiration_time,
            'additionals' => []
        ];

        // Set transient for session storage
        $transient = set_transient(
            Config::AUTH_TOKEN_PREFIX . $token_name . '_' . $id,
            $data,
            $expiration_time - $current_time
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
     * METHOD - update_additionals
     * 
     * Retrieves the session, updates the additional data, and then saves the session.
     * 
     * @param string $token_name Token name
     * @param int $id User ID
     * @param string $key The key to add to the additional session data
     * @param string|bool|int|null $value The value to store for the key
     * @return Response_Handler Response object containing session data or error details
     */

    public static function update_additionals(string $token_name, int $id, array $updated_data): Response_Handler
    {
        // Retrieve the session
        $update_transient = self::get($token_name, $id);

        // If retrieval failed, return the error response
        if (!$update_transient->ok) {
            return Response_Handler::response(
                false,
                500,
                "Unable to retrieve session data corresponding to token name of `" . $token_name . "`.",
                null
            );
        }

        // Existing session data
        $existing_data = $update_transient->data;

        // Update additionals data
        $existing_data['additionals'] = $updated_data;

        // Update expiration time
        $updated_expiration = max(1, $existing_data['expiration'] - time());

        // Save the session by setting Wordpress transient
        $transient = set_transient(
            Config::AUTH_TOKEN_PREFIX . $token_name . '_' . $id,
            $existing_data,
            $updated_expiration
        );

        // Determine if the update was successful
        $ok = $transient !== false;

        // Return a standardized response
        $return_data = Response_Handler::response(
            $ok,
            $ok ? 200 : 500,
            $ok ? "Session data updated successfully corresponding to token name of `" . $token_name ."`." : "Session update failed corresponding to token name of `" . $token_name . "`.",
            $existing_data
        );

        do_action('wp_custom_api_session_updated_response', $return_data);
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

        // Determine if the deletion was successful
        $ok = $delete_transient !== false;
        return Response_Handler::response(
            $ok,
            $ok ? 200 : 500,
            $ok ? "Session deleted successfully for token name of `" . $token_name . "`." : "Session deletion failed for token name of `" . $token_name . "`."
        );
    }
}
