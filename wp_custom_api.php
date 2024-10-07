<?php

/**
 * Plugin Name: WP Custom API
 * Description: This custom made plugin is meant for those seeking to utilize the Wordress REST API with their own custom PHP code.  This plugin provides a structure for routing, controllers, and models and a database helper for managing custom API routing.
 * Author: Chris Paschall
 * Version: 1.0.0
 */

/**
 * NOTE - DO NOT MODIFY OR CREATE ADDITIONAL FILES INSIDE THE "CORE" FOLDER OF THIS PLUGIN.
 * 
 * You can create, update and delete files within the "controllers", "permisssions", "models", and "routes" folders only.
 * The config.php file can also be adjusted
 */

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) {
    exit;
}

/** 
 * Define Folder Paths.  Used for auto loader
 */

define("WP_CUSTOM_API_BASE_PATH", strtolower(str_replace('/', '', __DIR__)));
define("WP_CUSTOM_API_ROOT_FOLDER_PATH", WP_CUSTOM_API_BASE_PATH . '/');

/** 
 * Load config settings for plugin
 */

require_once WP_CUSTOM_API_ROOT_FOLDER_PATH . '/config.php';

/** 
 * Load Init class to initialize plugin
 */

require_once WP_CUSTOM_API_ROOT_FOLDER_PATH . '/core/init.php';

use WP_Custom_API\Core\Init;

/** 
 * Load Error Generator to output errors that occur from the plugin
 */

require_once WP_CUSTOM_API_ROOT_FOLDER_PATH . '/core/error_generator.php';

use WP_Custom_API\Core\Error_Generator;

/** 
 * Initialize plugin
 */

Init::run();

/** 
 * Output error messages that occurred when running the plugin
 */

add_action('admin_notices', [Error_Generator::class, 'display_errors']);
