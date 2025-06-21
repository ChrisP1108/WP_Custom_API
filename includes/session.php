<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Includes\Response_Handler;

final class Session
{

    /**
     * CONSTRUCTOR
     *
     * Initializes a session object with the given parameters.
     *
     * @param int $id Unique identifier for the session.
     * @param string $name Name of the session.
     * @param string $nonce Nonce used for additional validation.
     * @param int $first_issued_at Timestamp when the session was first issued.
     * @param int $expiration_at Timestamp when the session will expire.
     * @param int $updated_tally Count of how many times the session has been updated.
     * @param int|null $last_updated_at Timestamp of the last update.
     * @param array $additionals Additional data related to the session.
     */

    private function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $nonce,
        public readonly int $first_issued_at,
        public readonly int $expiration_at,
        public int $updated_tally,
        public int|null $last_updated_at,
        public array $additionals
    ) {}

    /**
     * METHOD - generate
     * 
     * Generate a session with the given parameters
     * 
     * @param string $name Session name
     * @param int $id ser id
     * @param string $nonce Nonce used for validation
     * @param int $expiration Timestamp when session will expire
     * @return Response_Handler Response object
     */

    public static function generate(string $name, int $id, string $nonce, int $expiration_time): Response_Handler
    {
        // Current time
        $current_time = time();

        // Set data for transient
        $data = [
            'id' => $id,
            'name' => $name,
            'nonce' => $nonce,
            'first_issued_at' => $current_time,
            'expiration_at' => $expiration_time,
            'updated_tally' => 0,
            'last_updated_at' => null,
            'additionals' => []
        ];

        // Set transient for session storage
        $transient = set_transient(
            $name . '_' . $id,
            $data,
            $expiration_time
        );

        // Checks that transient data generation was successful
        $ok = $transient !== false;
        
        // Object data
        $object_data = new static(
            $data['id'], 
            $data['name'], 
            $data['nonce'], 
            $data['first_issued_at'], 
            $data['expiration_at'],
            0,
            null,
            []
        );

        // Generate response
        $return_data = Response_Handler::response($ok, $ok ? 200 : 500, $ok ? "Session generated successfully." : "Session generation failed.", $object_data);

        do_action('wp_custom_api_session_generated_response', $return_data);
        
        return $return_data;
    }

    /**
     * METHOD - get
     * 
     * Retrieve a session based on user ID and session name.
     *
     * @param int $id User ID
     * @param string $name Name of session
     * @return Response_Handler Response object containing session data or error details
     */

    public static function get(string $name, int $id): Response_Handler
    {
        // Retrieve session data from transient storage
        $transient = get_transient($name . '_' . $id);

        // Determine if retrieval was successful
        $ok = $transient !== false;

        // Object data
        $object_data = null;

        // If retrieval was successful, set object data
        if ($ok) {
            $object_data = new static(
                $transient['id'], 
                $transient['name'], 
                $transient['nonce'], 
                $transient['first_issued_at'], 
                $transient['expiration_at'], 
                $transient['updated_tally'], 
                $transient['last_updated_at'] ?? null,
                $transient['additionals'] ?? []
            );
        }

        // Return a standardized response
        return Response_Handler::response(
            $ok,
            $ok ? 200 : 500,
            $ok ? "Session retrieved successfully." : "Session retrieval failed.",
            $object_data
        );
    }

    /**
     * METHOD - update_additionals
     * 
     * Retrieves the session, updates the additional data, and then saves the session.
     * 
     * @param string $name Name of the session
     * @param int $id User ID
     * @param array $key The array to store in the additionals key
     * @return Response_Handler Response object containing session data or error details
     */

    public static function update_additionals(string $name, int $id, array $updated_data): Response_Handler
    {
        // Retrieve the session
        $update_transient = self::get($name, $id);

        // If retrieval failed, return the error response
        if (!$update_transient->ok) {
            return Response_Handler::response(
                false,
                500,
                "Unable to retrieve session data corresponding to the name of `" . $name . "`."
            );
        }

        // Existing session data
        $existing_data = $update_transient->data;

        // Add tally to number of times updated
        if (isset($existing_data['updated_tally'])) {
            $existing_data['updated_tally'] += 1;
        } else {
            $existing_data['updated_tally'] = 1;
        }

        // Set last time updated
        $current_time = time();
        $existing_data['last_updated_at'] = $current_time;

        // Update additionals data
        $existing_data['additionals'] = $updated_data;

        // Update expiration time
        $updated_expiration = max(1, $existing_data['expiration_at'] - $current_time);

        // Save the session by setting Wordpress transient
        $transient_update = set_transient(
            $name . '_' . $id,
            $existing_data,
            $updated_expiration
        );

        // Determine if the update was successful
        $ok = $transient_update !== false;

        // Object data
        $object_data = null;

        // If retrieval was successful, set object data
        if ($ok) {
            $object_data = new static(
                $existing_data['id'], 
                $existing_data['name'], 
                $existing_data['nonce'], 
                $existing_data['first_issued_at'], 
                $existing_data['expiration_at'], 
                $existing_data['updated_tally'] ?? 0, 
                $existing_data['last_updated_at'],
                $existing_data['additionals'] ?? []
            );
        }

        // Return a standardized response
        $return_data = Response_Handler::response(
            $ok,
            $ok ? 200 : 500,
            $ok ? "Session data updated successfully corresponding to session name of `" . $name ."`." : "Session update failed corresponding to session name of `" . $name . "`.",
            $object_data
        );

        do_action('wp_custom_api_session_updated_response', $return_data);

        return $return_data;
    }

    /**
     * METHOD - delete
     * 
     * Delete a session based on user ID and session name.
     * 
     * @param string $name Session name
     * @param int $id User ID
     * @return Response_Handler Response object containing information about the deletion
     */

    public static function delete(string $name, int $id): Response_Handler
    {
        $delete_transient = delete_transient($name . '_' . $id);

        // Determine if the deletion was successful
        $ok = $delete_transient !== false;
        return Response_Handler::response(
            $ok,
            $ok ? 200 : 500,
            $ok ? "Session deleted successfully for session name of `" . $name . "`." : "Session deletion failed for session name of `" . $name . "`."
        );
    }
}
