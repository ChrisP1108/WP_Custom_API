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
 * This is a list of folders within the WP_CUSTOM_API_ROOT_FOLDER_PATH that will be auto loaded when the plugin runs 
 */

define("WP_CUSTOM_API_FOLDER_AUTOLOAD_PATHS", ["core", "controllers", "permissions", "models", "routes"]);

/**
 * Secret key used for auth token generation for the plugin
 */

define("WP_CUSTOM_API_SECRET_KEY", "6835be5d3e17ff0352492525c4d9c9291e61e51e10d07067f39334be1893bf92");

/**
 * Establishes a default expiration time of one is not specified.  Defaults to 604800 seconds(7 days)
 */

define("WP_CUSTOM_API_TOKEN_EXPIRATION", 604800);

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
