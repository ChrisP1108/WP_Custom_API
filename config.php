<?php

namespace WP_Custom_API;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) {
    exit;
}

/** 
 * Used for plugin config.  
 * The values of the constants can be adjusted as needed.
 * 
 */

class Config {

    /**
     * CONSTANT
     * 
     * @const string FOLDER_AUTOLOAD_PATHS
     * This is a list of folders within the WP_CUSTOM_API_ROOT_FOLDER_PATH that will be auto loaded when the plugin runs 
     * 
     * @since 1.0.0
     */

    public const FOLDER_AUTOLOAD_PATHS = ["core", "controllers", "permissions", "models", "routes"];

    /**
     * CONSTANT
     * 
     * @const string BASE_API_ROUTE
     * Establishes base path for API. Any route will have a url path of {origin}/wp-json/custom-api/v1/${$route}
     * 
     * @since 1.0.0
     */

    public const BASE_API_ROUTE = "custom-api/v1";

    /**
     * CONSTANT
     * 
     * @const string DB_CUSTOM_PREFIX
     * Establishes database table prefix name to establish unique table naming for plugin
     * 
     * @since 1.0.0
     */

    public const DB_PREFIX = "custom_api_";

    /**
     * CONSTANT
     * 
     * @const string SECRET_KEY
     * Secret key used for auth token generation
     * 
     * @since 1.0.0
     */

    public const SECRET_KEY = "6835be5d3e17ff0352492525c4d9c9291e61e51e10d07067f39334be1893bf92";

    /**
     * CONSTANT
     * 
     * @const int TOKEN_EXPIRATION
     * Establishes a default expiration time for auth token if method is called without an expiration parameter passed in.  Time is in seconds.
     * 
     * @since 1.0.0
     */

    public const TOKEN_EXPIRATION = 604800; // 7 days

    /**
     * CONSTANT
     * 
     * @const string TOKEN_HTTPS_ONLY
     * If this constant is set to true, then auth token cookies will only be stored on the client if over HTTPS.
     * 
     * @since 1.0.0
     */

    public const TOKEN_OVER_HTTPS_ONLY = true;

    /**
     * CONSTANT
     * 
     * @const string TOKEN_COOKIE_HTTP_ONLY
     * If set to true, the cookie will be set for HTTP only access, preventing javascript access on the client side.
     * 
     * @since 1.0.0
     */

    public const TOKEN_COOKIE_HTTP_ONLY = true;
}