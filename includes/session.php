<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Includes\Database;
use WP_Custom_API\Includes\Response_Handler;

final class Session
{

    /**
     * CONSTANT
     * 
     * Schema for the sessions table.
     * Table is created in the Init class create_tables method.
     */

    const SESSIONS_TABLE_QUERY = [
        'name' => 
            [
                'query' => 'VARCHAR(255) NOT NULL'
            ],
        'user' => 
            [
                'query' => 'BIGINT(12) NOT NULL'
            ],
        'nonce' => 
            [
                'query' => 'VARCHAR(255) NOT NULL'
            ],
        'expiration_at' => 
            [
                'query' => 'BIGINT(12) NOT NULL'
            ],
        'updated_tally' => 
            [
                'query' => 'INT(11) NOT NULL'
            ],
        'additionals' => 
            [
                'query' => 'JSON NOT NULL'
            ]
    ];

    /**
     * CONSTANT
     * 
     * Name of the sessions table.
     */

    const SESSIONS_TABLE_NAME = '_sessions_';

    /**
     * CONSTRUCTOR
     *
     * Initializes a session object with the given parameters.
     *
     * @param string $name Name of the session.
     * @param int $user ID of the user associated with the session.
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
        public readonly int $user,
        public readonly string $nonce,
        public readonly int $first_issued_at,
        public readonly int $expiration_at,
        public int $updated_tally,
        public int|null $last_updated_at,
        public array $additionals
    ) {}

    /**
     * METHOD - delete_expired_sessions
     * 
     * Deletes expired sessions from the database if the check interval has passed.
     * A transient is used to ensure that the deletion runs only once every 24 hours.
     * 
     * @return Response_Handler Response object indicating the result of the operation.
     */

    public static function delete_expired_sessions(): Response_Handler {
        // Check if the expiry interval check transient is set
        $check_interval = get_transient('wp_custom_api_session_expiry_interval_check');

        // If the interval has passed, delete expired sessions
        if (!$check_interval) {
            global $wpdb;
            $table_name = Database::get_table_full_name(self::SESSIONS_TABLE_NAME);
            $expiration = time();
            
            // Delete sessions that have expired from sessions table
            $sql = "DELETE FROM $table_name WHERE expiration_at < $expiration";
            $result = $wpdb->query($sql);

            // Check if the deletion was successful
            if ($result === false) {
                return Response_Handler::response(
                    false,  
                    500,    
                    'An error occurred while attempting to delete expired sessions.'
                );
            }

            // Set transient to prevent deletion from running again within 24 hours
            set_transient('wp_custom_api_session_expiry_interval_check', true, 86400);

            // Return success response
            return Response_Handler::response(
                true,
                200,
                'Sessions expired successfully.'
            );
        }

        // Return response if interval has not passed
        return Response_Handler::response(
            true,
            200,
            'Sessions check not yet within refresh interval.',
        );
    }

    /**
     * METHOD - generate
     * 
     * Generate a session with the given parameters
     * 
     * @param string $name Session name
     * @param int $id user id
     * @param string $nonce Nonce used for validation
     * @param int $expiration Timestamp when session will expire
     * @return Response_Handler Response object
     */

    public static function generate(string $name, int $id, string $nonce, int $expiration_time): Response_Handler
    {
        // Set data for table row
        $data = [
            'name' => $name,
            'user' => $id,
            'nonce' => $nonce,
            'expiration_at' => $expiration_time,
            'updated_tally' => 0,
            'additionals' => []
        ];

        // Create session table row in sessions table
        $insert_session_result = Database::insert_row(
            self::SESSIONS_TABLE_NAME,
            $data
        );

        // Check if insert was successful to session table
        $ok = $insert_session_result->ok;
        
        // Object return data
        $object_data = new static(
            $insert_session_result->data['id'],
            $name, 
            $id, 
            $nonce, 
            time(), 
            $expiration_time,
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
        $get_session_rows_by_name = Database::get_rows_data(
            SESSION::SESSIONS_TABLE_NAME,
            'name',
            $name,
            true
        );

        // Check if retrieval was successful
        if (!$get_session_rows_by_name->ok) {
            return Response_Handler::response(
                false,
                500,
                "Unable to retrieve session data corresponding to the name of `" . $name . "`."
            );
        }

        $get_user_sessions_row = array_filter(
            $get_session_rows_by_name->data,
            function ($row) use ($id) {
                return intval($row['user']) === $id;
            }
        );

        // Determine if retrieval was successful
        $ok = !empty($get_user_sessions_row);

        $user_session_data = (array) $get_user_sessions_row[0];

        // Object data
        $object_data = null;

        // If retrieval was successful, set object data
        if ($ok) {
            $object_data = new static(
                $user_session_data['id'],
                $user_session_data['name'], 
                $user_session_data['user'],
                $user_session_data['nonce'], 
                strtotime($user_session_data['created_at']), 
                $user_session_data['expiration_at'], 
                $user_session_data['updated_tally'], 
                strtotime($user_session_data['updated_at']) ?? null,
                $user_session_data['additionals'] ?? []
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
        $update_session_data = self::get($name, $id);

        // If retrieval failed, return the error response
        if (!$update_session_data->ok) {
            return Response_Handler::response(
                false,
                500,
                "Unable to retrieve session data corresponding to the name of `" . $name . "`."
            );
        }

        // Existing session data
        $existing_data = (array) $update_session_data->data;

        // Add tally to number of times updated
        if (isset($existing_data['updated_tally'])) {
            $existing_data['updated_tally'] += 1;
        } else {
            $existing_data['updated_tally'] = 1;
        }

        // Update additionals data
        $existing_data['additionals'] = $updated_data;

        // Update expiration time
        $updated_expiration = max(1, $existing_data['expiration_at'] - time());

        // Update session table row in sessions table
        $insert_session_result = Database::insert_row(
            self::SESSIONS_TABLE_NAME,
            $update_session_data->data['id'],
            $existing_data,
        );

        // Determine if the update was successful
        $ok = $insert_session_result->ok;

        // Object data
        $object_data = null;

        // If retrieval was successful, set object data
        if ($ok) {
            $object_data = new static(
                $existing_data['id'],
                $existing_data['name'],
                $existing_data['user'], 
                $existing_data['nonce'], 
                strtotime($existing_data['created_at']), 
                $existing_data['expiration_at'], 
                $existing_data['updated_tally'],
                $updated_expiration, 
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
        // Retrieve session data
        $get_session_data = self::get($name, $id);

        // Check if retrieval was successful
        if (!$get_session_data->ok) {
            return Response_Handler::response(
                false,
                500,
                "Unable to retrieve session data corresponding to the name of `" . $name . "` for deletion."
            );
        }

        // Delete session row
        $delete_row_result = Database::delete_row(
            SESSION::SESSIONS_TABLE_NAME,
            $get_session_data->data['id']
        );

        // Check if deletion was successful
        $ok = $delete_row_result->ok;
        
        // Return response
        return Response_Handler::response(
            $ok,
            $ok ? 200 : 500,
            $ok ? "Session deleted successfully for session name of `" . $name . "`." : "Session deletion failed for session name of `" . $name . "`."
        );
    }
}
