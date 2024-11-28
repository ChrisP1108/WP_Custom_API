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

class Config
{

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

    public const SECRET_KEY = WP_CUSTOM_API_SECRET_KEY;

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

    public const TOKEN_OVER_HTTPS_ONLY = false;

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
