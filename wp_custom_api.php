<?php

/**
 * Plugin Name: WP Custom API
 * Description: This custom made plugin is meant for those seeking to utilize the Wordress REST API with their own custom PHP code.  This plugin provides a structure for routing, controllers, and models and a database helper for managing custom API routing.
 * Author: Chris Paschall
 * Version: 1.0.0
 */

/**
 * NOTE - DO NOT MODIFY OR CREATE ADDITIONAL FILES INSIDE THE "PLUGIN" FOLDER OF THIS PLUGIN.
 * 
 * You can create, update and delete files within the "controllers", "permissions", "models", and "routes" folders inside the app folder only.
 * The config.php file can also be adjusted
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

require_once WP_CUSTOM_API_FOLDER_PATH . '/plugin/error_generator.php';

use WP_Custom_API\Plugin\Error_Generator;

/** 
 * Load Init class to initialize plugin
 */

require_once WP_CUSTOM_API_FOLDER_PATH . '/plugin/init.php';

use WP_Custom_API\Plugin\Init;

/** 
 * Initialize plugin
 */

Init::run();

/** 
 * Output error messages that occurred when running the plugin
 */

add_action('admin_notices', [Error_Generator::class, 'display_errors']);
