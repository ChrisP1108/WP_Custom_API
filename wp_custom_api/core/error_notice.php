<?php

declare(strict_types=1);

namespace WP_Custom_API\Core;

use \WP_Error;

/** 
 * Used for generating error messages in the Wordpress Dashboard utilizing the WP_Error class and the 'admin_notices' Wordpress hook.
 * 
 * @since 1.0.0
 */

class Error_Notice {

    /**
     * METHOD - generate
     * 
     * Utilizes the WP_Error class along with the 'admin_notices' hook
     * 
     * @param string $code_msg - Code Message
     * @param string $description_msg - Description Message
     * @return void
     * 
     * @since 1.0.0
     */

    public static function generate($code_msg = null, $description_msg = null):void {
        if ($code_msg && $description_msg) {
            add_action('admin_notices', function() use ($code_msg, $description_msg) {
                return new WP_Error($code_msg, $description_msg);
            });
        }
    }
}
