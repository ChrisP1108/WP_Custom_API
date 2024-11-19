<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use \WP_Error;

/** 
 * Used for generating error messages in the Wordpress Dashboard utilizing the WP_Error class and the 'admin_notices' Wordpress hook.
 * 
 * @since 1.0.0
 */

class Error_Generator
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

    public static function generate($code_msg = null, $description_msg = null): void
    {
        if ($code_msg && $description_msg) {
            error_log($description_msg);
            self::$errors_list[] = new WP_Error($code_msg, $description_msg);
        }
    }

    /**
     * METHOD - display_errors
     * 
     * Outputs error messages to the WordPress admin dashboard.
     * 
     * @return void
     * 
     * @since 1.0.0
     */
    public static function display_errors(): void
    {
        foreach (self::$errors_list as $error) {
            echo '<div class="notice notice-error"><strong>' . esc_html($error->get_error_code()) . ':</strong> ' . esc_html($error->get_error_message()) . '</div>';
        }
    }
}
