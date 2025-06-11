<?php

namespace WP_Custom_API;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

/** 
 * Used for plugin config.  
 * The values of the constants can be adjusted as needed.
 * 
 */

final class Config
{

    /**
     * CONSTANT
     * 
     * @const string BASE_API_ROUTE
     * Establishes base path for API. Any route will have a url path of {origin}/wp-json/custom-api/v1/${$route}
     */

    public const BASE_API_ROUTE = "custom-api/v1";

    /**
     * CONSTANT
     * 
     * @const string PREFIX
     * Establishes prefix name to establish unique naming for plugin
     */

    public const PREFIX = "custom_api_";

    /**
     * CONSTANT
     * 
     * @const array file_autoload
     * Files to autoload when plugin is initialized.
     * These are files that will automatically run that aren't directly called upon from other classes through the namespace autoloader.
     */

    public const FILES_TO_AUTOLOAD = ['model', 'routes'];

    /**
     * CONSTANT
     * 
     * @const string SECRET_KEY
     * Secret key used for auth token generation
     */

    public const SECRET_KEY = 'acc0b1fe4f9bc2bff8c978cf215e42176895228abfae40df16db6e6d82bcb420';

    /**
     * CONSTANT
     * 
     * @const int TOKEN_EXPIRATION
     * Establishes a default expiration time for auth token if method is called without an expiration parameter passed in.  Time is in seconds.
     */

    public const TOKEN_EXPIRATION = 604800; // 7 days

    /**
     * CONSTANT
     * 
     * @const string AUTH_TOKEN_PREFIX
     * Sets a prefix name for authentication tokens.  Used primarily in the Auth_Token class.
     */

    public const AUTH_TOKEN_PREFIX = self::PREFIX . 'auth_token_';

    /**
     * CONSTANT
     * 
     * @const int HASH_ROUNDS
     * Determines number of cost rounds for password hashing.  Used primarily in the Password class.
     */

    public const PASSWORD_HASH_ROUNDS = 12;

    /**
     * CONSTANT
     * 
     * @const string TOKEN_HTTPS_ONLY
     * If this constant is set to true, then auth token cookies will only be stored on the client if over HTTPS.
     */

    public const TOKEN_OVER_HTTPS_ONLY = true;

    /**
     * CONSTANT
     * 
     * @const bool TOKEN_COOKIE_HTTP_ONLY
     * If set to true, the cookie will be set for HTTP only access, preventing javascript access on the client side.
     */

    public const TOKEN_COOKIE_HTTP_ONLY = true;

    /**
     * CONSTANT
     * 
     * @const string TOKEN_COOKIE_SAME_SITE
     * Used for same site cookie configuration. Value can be either 'Stict', 'Lax' or 'None'.
     */

    public const TOKEN_COOKIE_SAME_SITE = 'Strict';

    /**
     * CONSTANT
     * 
     * @const bool DEBUG_MESSAGE_MODE
     * Sets whether detailed messages about errors are returned when hitting API routes for debugging.
     */

    public const DEBUG_MESSAGE_MODE = false;
}
