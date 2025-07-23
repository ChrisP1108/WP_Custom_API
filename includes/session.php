<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Config;
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
            'query' => 'VARCHAR(255)'
        ],
        'user' =>
        [
            'query' => 'BIGINT(12)'
        ],
        'nonce' =>
        [
            'query' => 'VARBINARY(100)'
        ],
        'refresh_nonce' =>
        [
            'query' => 'VARBINARY(100)'
        ],
        'header_nonce' =>
        [
            'query' => 'VARBINARY(100)'
        ],
        'expiration_at' =>
        [
            'query' => 'BIGINT(12)'
        ],
        'updated_tally' =>
        [
            'query' => 'INT(11)'
        ],
        'additionals' =>
        [
            'query' => 'JSON'
        ]
    ];

    /**
     * CONSTANT
     * 
     * Name of the sessions table.
     */

    const SESSIONS_TABLE_NAME = '_sessions_';

    /**
     * CONSTANT
     * 
     * Name of the sessions interval transient name.
     */

    const SESSIONS_INTERVAL_TRANSIENT_NAME = 'wp_custom_api_sessions_interval_check';

    /**
     * CONSTRUCTOR
     *
     * Initializes a session object with the given parameters.
     *
     * @param string $name Name of the session.
     * @param int $user ID of the user associated with the session.
     * @param string $nonce Nonce used for additional validation.
     * @param string $refresh_nonce Nonce used for refreshing the session.
     * @param string $header_nonce Nonce used for refreshing and validating the session in the request header.
     * @param int $created_at Timestamp when the session was first issued.
     * @param int $expiration_at Timestamp when the session will expire.
     * @param int $updated_tally Count of how many times the session has been updated.
     * @param int|null $updated_at Timestamp of the last update.
     * @param array $additionals Additional data related to the session.
     */

    private function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $user,
        public readonly string $nonce,
        public readonly string $refresh_nonce,
        public readonly string $header_nonce,
        public readonly int $created_at,
        public readonly int $expiration_at,
        public int $updated_tally,
        public int|null $updated_at,
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

    public static function delete_expired_sessions(): Response_Handler
    {
        // Check if the expiry interval check transient is set
        $check_interval = get_transient(self::SESSIONS_INTERVAL_TRANSIENT_NAME);

        // If the interval has passed, delete expired sessions
        if (!$check_interval) {

            // Get session table full name
            global $wpdb;
            $table_name = Database::get_table_full_name(self::SESSIONS_TABLE_NAME);
            $table_exists = Database::table_exists(self::SESSIONS_TABLE_NAME);

            // Make sure the sessions table exists
            if (!$table_exists) return Response_Handler::response(
                false,
                500,
                'An error occurred while attempting to delete expired sessions. The sessions table does not exist.'
            );

            // Set current time for expiration check
            $expiration = time();

            ob_start();

            // Delete sessions that have expired from sessions table
            $query = $wpdb->prepare(
                'DELETE FROM ' . $table_name . ' WHERE expiration_at < %d',
                $expiration
            );

            $result = $wpdb->query($query);

            ob_end_clean();

            // Check if the deletions were successful
            if ($result === false) {
                return Response_Handler::response(
                    false,
                    500,
                    'An error occurred while attempting to delete expired sessions.'
                );
            }

            // Set transient to prevent deletion from running again within database refresh interval.
            set_transient(self::SESSIONS_INTERVAL_TRANSIENT_NAME, true, Config::DATABASE_REFRESH_INTERVAL);

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
     * @param array $additionals The array to store in the additionals key
     * @param string $refresh_nonce Nonce used for refreshing the session
     * @param string $header_nonce Nonce used for refreshing the session in the request header
     * @return Response_Handler Response object
     */

    public static function generate(string $name, int $id, string $nonce, int $expiration_time, array $additionals = [], string $refresh_nonce = '', string $header_nonce = ''): Response_Handler
    {
        // Delete any previous sessions with the same name and user id
        
        global $wpdb;
        $table_name = Database::get_table_full_name(self::SESSIONS_TABLE_NAME);

        ob_start();

        $query = $wpdb->prepare(
            'DELETE FROM ' . $table_name . ' WHERE name = %s AND user = %d',
            $name,
            $id
        );

        $result = $wpdb->query($query);

        ob_end_clean();

        // Check if the deletions were successful
        if ($result === false) return Response_Handler::response(
            false, 
            500, 
            'An error occurred while attempting to delete previous sessions.'
        );

        // Check if additionals is an array
        if (!is_array($additionals)) return Response_Handler::response(
            false, 
            500, 
            'Additionals data must be an array when generating a session from the Session class.'
        );

        // Set data for table row insert
        $data = [
            'name' => $name,
            'user' => $id,
            'nonce' => $nonce,
            'refresh_nonce' => $refresh_nonce,
            'header_nonce' => $header_nonce,
            'expiration_at' => $expiration_time,
            'updated_tally' => 0,
            'additionals' => json_encode($additionals)
        ];

        // Create session table row in sessions table
        $insert_session_result = Database::insert_row(
            self::SESSIONS_TABLE_NAME,
            $data
        );

        // Check if insert was successful to session table
        $ok = $insert_session_result->ok;

        if (!$ok) return Response_Handler::response(
            $ok, $ok ? 200 : 500, 
            $ok ? "Session generated successfully." : "Session generation failed."
        );

        // Get session id
        $session_id = $insert_session_result->data['id'];

        // Object return data
        $object_data = new static(
            $session_id,
            $name,
            $id,
            $nonce,
            $refresh_nonce,
            $header_nonce,
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
        // Get session table full name
        global $wpdb;
        $table_name = Database::get_table_full_name(self::SESSIONS_TABLE_NAME);

        ob_start();

        // Retrieve session data row from sessions table that matches session name and user id
        $query = $wpdb->prepare(
            'SELECT * FROM ' . $table_name . ' WHERE name = %s AND user = %d LIMIT 1',
            $name,
            $id
        );

        $user_session_data = $wpdb->get_row($query, ARRAY_A);

        ob_end_clean();

        // Determine if retrieval was successful
        $ok = $user_session_data !== null;

        // Check if session has expired
        if ($ok && time() > intval($user_session_data['expiration_at'])) {

            // Delete expired session
            $delete_session_result = Database::delete_row(
                self::SESSIONS_TABLE_NAME,
                $user_session_data['id']
            );

            // Check if deletion was successful
            if (!$delete_session_result->ok) return Response_Handler::response(
                false,
                500,
                "An error occurred while attempting to delete expired session."
            );

            // Session has expired return response
            return Response_Handler::response(
                false,
                401,
                "Session has expired."
            );
        }

        // Object data
        $object_data = null;

        // If retrieval was successful, set object data
        if ($ok) {
            $object_data = new static(
                intval($user_session_data['id']),
                $user_session_data['name'],
                intval($user_session_data['user']),
                $user_session_data['nonce'],
                $user_session_data['refresh_nonce'],
                $user_session_data['header_nonce'],
                strtotime($user_session_data['created_at']),
                intval($user_session_data['expiration_at']),
                intval($user_session_data['updated_tally']),
                strtotime($user_session_data['updated_at']) ?? null,
                json_decode($user_session_data['additionals'], true) ?? []
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
     * METHOD - update
     * 
     * Retrieves the session, updates the additional data, and then saves the session.
     * 
     * @param string $name Name of the session
     * @param int $id User ID
     * @param array $updated_data The array to store in the additionals key
     * @param string $refresh_nonce Nonce used for refreshing the session
     * @param string $header_nonce Nonce used for refreshing the session in the request header
     * @return Response_Handler Response object containing session data or error details
     */

    public static function update(string $name, int $id, array $updated_data, string $refresh_nonce = '', string $header_nonce = ''): Response_Handler
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
            $existing_data['updated_tally'] = intval($existing_data['updated_tally']);
            $existing_data['updated_tally'] += 1;
        } else {
            $existing_data['updated_tally'] = 1;
        }

        // Update additionals data
        $existing_data['additionals'] = json_encode($updated_data);

        $sql_update_data = $existing_data;

        // Remove unnecessary data
        unset($sql_update_data['updated_at']);
        unset($sql_update_data['created_at']);
        unset($sql_update_data['id']);

        // Add refresh and header nonces
        $sql_update_data['refresh_nonce'] = $refresh_nonce;
        $sql_update_data['header_nonce'] = $header_nonce;

        // Update session table row in sessions table
        $insert_session_result = Database::update_row(
            self::SESSIONS_TABLE_NAME,
            intval($existing_data['id']),
            $sql_update_data,
        );

        // Determine if the update was successful
        $ok = $insert_session_result->ok;

        // Object data
        $object_data = null;

        // If retrieval was successful, set object data
        if ($ok) {
            $object_data = new static(
                intval($existing_data['id']),
                $existing_data['name'],
                intval($existing_data['user']),
                $existing_data['nonce'],
                $refresh_nonce,
                $header_nonce,
                intval($existing_data['created_at']),
                intval($existing_data['expiration_at']),
                intval($existing_data['updated_tally']),
                intval($existing_data['updated_at']),
                json_decode($existing_data['additionals'], true) ?? []
            );
        }

        // Return a standardized response
        $return_data = Response_Handler::response(
            $ok,
            $ok ? 200 : 500,
            $ok ? "Session data updated successfully corresponding to session name of `" . $name . "`." : "Session update failed corresponding to session name of `" . $name . "`.",
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

        // Get session ID
        $data_array = (array) $get_session_data->data;
        $id = $data_array['id'];

        // Delete session row
        $delete_row_result = Database::delete_row(
            self::SESSIONS_TABLE_NAME,
            $id
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
