<?php

declare(strict_types=1);

namespace WP_Custom_API\Traits;

use \WP_Session_Tokens;

/**
 * Wordpress User Login / Logout helpers
 */
trait WP_User {

    /**
     * METHOD - wp_user_data
     * 
     * Retrieve the current user data
     * 
     * @return Object WP_User object with a logged_in property to check if the user is logged in
     */

    public static function wp_user_data(): Object {
        $user_data = wp_get_current_user();
        $user_data->logged_in = $user_data->ID !== 0;
        return $user_data;
    }

    /**
     * METHOD - wp_user_data
     * 
     * Log in a user given their username and password
     * 
     * @param string $username The username of the user to log in
     * @param string $password The password of the user to log in
     * @param bool $remember Whether to remember the user or not
     * @return bool Whether the login was successful or not
     */

    public static function wp_user_login(string $username, string $password, bool $remember = false): bool {       
        // Compile credentials
        $credentials = [
            "user_login" => sanitize_user($username),
            "user_password" => $password,
            "remember" => $remember
        ];

        // Login user
        $login = wp_signon($credentials, is_ssl());

        // Check if login was successful
        if (is_wp_error($login)) {
            return false;
        }

        return true;
    }
    
    /**
     * METHOD - wp_user_logout
     * 
     * Log out the current user by destroying all their sessions and logging out from this browser
     * 
     * @return void
     */
    
    public static function wp_user_logout(): void {

        // Get the current user's ID
        $user_id = get_current_user_id();

        // Destroy every session this user has
        WP_Session_Tokens::get_instance($user_id)->destroy_all();

        // Log out this browser
        wp_logout();
    }
}