<?php

namespace WP_Custom_API\Includes;

use \WP_Error;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) {
    exit;
}

/** 
 * Used for generating error messages in the Wordpress Dashboard utilizing the WP_Error class and the 'admin_notices' Wordpress hook.
 * 
 * @since 1.0.0
 */

final class Error_Generator
{

    /**
     * PROPERTY
     * 
     * @array errors_list
     * Stores a list of error message to log and output to the Wordpress dashboard
     * 
     * @since 1.0.0
     */

    private static $errors_list = [];

    /**
     * METHOD - generate
     * 
     * Utilizes the WP_Error class for logging errors
     * 
     * @param string $code_msg - Code Message
     * @param string $description_msg - Description Message
     * @return void
     * 
     * @since 1.0.0
     */

    public static function generate($code_msg = null, $description_msg = null)
    {
        if ($code_msg && $description_msg) {
            error_log($description_msg);
            self::$errors_list[] = new WP_Error($code_msg, $description_msg);
        }
    }

    /**
     * METHOD - display_errors
     * 
     * Outputs error messages to the WordPress admin dashboard.  Styling is applied for better readability.
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    public static function display_errors()
    {

        // Style error notices

            echo '
                <style>
                    .wp-custom-api-notice-error {
                        font-size: min(0.875rem, 4.25vw) !important;
                        padding: 1em !important;
                        text-wrap: balance;
                    }
                </style>
            ';

        // Output notice errors

        foreach (self::$errors_list as $error) {
            echo '<div class="notice notice-error wp-custom-api-notice-error"><strong>' . esc_html($error->get_error_code()) . ':</strong> ' . esc_html($error->get_error_message()) . '</div>';
        }

        do_action('wp_custom_api_error_displayed', self::$errors_list);
    }
}
