<?php

/**
 * Plugin Name: WP Custom API
 * Description: This custom made plugin is meant for those seeking to utilize the Wordress REST API with their own custom PHP code.  This plugin provides a structure for routing, controllers, and models and a database helper for managing custom API routing.
 * Author: Chris Paschall
 * Version: 1.0.0
 * PHP Version Minimum: 8.0
 */

/**
 * NOTE - AVOID MODIFYING FILES INSIDE THE "INCLUDES" FOLDER. ALSO AVOID RENAMING FILE NAMES WITHIN API FOLDER.
 * 
 * You can create, update and delete files within the "controllers", "permissions", "models", and "routes" folders inside the app folder only.
 * Avoid changing the names of the files, especially the routes.php files, as those are loaded through the api_routes_files_autoloader method that loads filenames specifically to routes.php.
 * The config.php file can also be adjusted as needed.
 */

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) {
    exit;
}

/** 
 * Define Folder Paths.  Used for requiring init files and auto loader
 */

define("WP_CUSTOM_API_PLUGIN_PATH", strtolower(str_replace('/', '', dirname(__DIR__, 1))));
define("WP_CUSTOM_API_FOLDER_PATH", WP_CUSTOM_API_PLUGIN_PATH . "/wp_custom_api");

/** 
 * Load config settings for plugin
 */

require_once WP_CUSTOM_API_FOLDER_PATH . '/config.php';

/** 
 * Load Error Generator to output errors that occur from the plugin
 */

require_once WP_CUSTOM_API_FOLDER_PATH . '/includes/error_generator.php';

use WP_Custom_API\Includes\Error_Generator;

/** 
 * Load Init class to initialize plugin
 */

require_once WP_CUSTOM_API_FOLDER_PATH . '/includes/init.php';

use WP_Custom_API\Includes\Init;

/** 
 * Check that Wordpress is running PHP version 8.0 or higher.
 * If so, plugin is initialized.  Otherwise the plugin doesn't run and an error notice message is shown in the Wordpress dashboard.
 */

if (!version_compare(PHP_VERSION, '8.0.0', '>=')) {
    Error_Generator::generate('WP Custom API plugin is currently not running', 'This plugin requires that PHP version 8.0 or higher to be installed.');
} else {
    Init::run();
}

/** 
 * Output error messages that occurred when running the plugin
 */

add_action('admin_notices', [Error_Generator::class, 'display_errors']);
