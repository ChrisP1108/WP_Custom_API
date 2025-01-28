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
     * @const string PREFIX
     * Establishes prefix name to establish unique naming for plugin
     * 
     * @since 1.0.0
     */

    public const PREFIX = "custom_api_";

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
     * @const string AUTH_TOKEN_PREFIX
     * Sets a prefix name for authentication tokens.  Used primarily in the Auth_Token class.
     * 
     * @since 1.0.0
     */

    public const AUTH_TOKEN_PREFIX = self::PREFIX . 'auth_token_';

    /**
     * CONSTANT
     * 
     * @const string HASH_ROUNDS
     * Determines number of cost rounds for password hashing.  Used primarily in the Password class.
     * 
     * @since 1.0.0
     */

    public const PASSWORD_HASH_ROUNDS = 12;

    /**
     * CONSTANT
     * 
     * @const string TOKEN_HTTPS_ONLY
     * If this constant is set to true, then auth token cookies will only be stored on the client if over HTTPS.
     * 
     * @since 1.0.0
     */

    public const TOKEN_OVER_HTTPS_ONLY = IN_PRODUCTION_MODE;

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
