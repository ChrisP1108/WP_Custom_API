<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Config;
use WP_REST_Request as Request;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) {
    exit;
}


/** 
 * Used for setting up API Routes.  
 * Files in the routing folder utilize this class for establishing API Routes and passing in controller and permission callbacks.
 * 
 * @since 1.0.0
 */

final class Router
{

    /**
     * METHOD - register_rest_api_route
     * 
     * Registers an API Route through Wordpress from either the Get, Post, Put, or Delete methods
     * @param string $method - HTTP method ('GET', 'POST', etc.).
     * @param string $route - The specific route to register.  Handles parameters if passed in
     * @param callable $callback - The function that runs when the route is accessed.
     * @param callable|null $permission_callback - Callback for checking permissions.
     * @return void
     * 
     * @since 1.0.0
     */

    public static function register_rest_api_route(string $method, string $route, ?callable $callback, ?callable $permission_callback): void
    {
        add_action("rest_api_init", function () use ($method, $route, $callback, $permission_callback) {
            register_rest_route(Config::BASE_API_ROUTE, $route, [
                "methods" => $method,
                "permission_callback" => $permission_callback ? $permission_callback : '__return_true',
                "callback" => $callback
            ]);
        });
    }

    /**
     * METHOD - parse_wildcards
     * 
     * Extracts any wildcards in route wrapped in {} and formats the route as a url parameter for the Wordpress REST API.
     * This will match either numeric IDs or alphanumeric values (slugs, etc.)
     * @param string $route The route URL to be converted if wildcards exist.
     * @return string
     * 
     * @since 1.0.0
     */

    private static function parse_wildcards(string $route): string
    {
        return preg_replace_callback('/\{(\w+)\}/', function ($matches) {
            return '(?P<' . $matches[1] . '>(\d+|[\w\-]+))';
        }, $route);
    }

    /**
     * METHOD - get
     * 
     * Registers a GET request route.
     * @param string $route The route URL.
     * @param callable|null $callback - The function that runs when the route is accessed.
     * @param callable|null $permission_callback - Callback for checking permissions.
     * @return void
     * 
     * @since 1.0.0
     */

    public static function get(string $route = '', callable $callback = null, ?callable $permission_callback = null): void
    {
        self::register_rest_api_route("GET", self::parse_wildcards($route), $callback, $permission_callback);
    }

    /**
     * METHOD - post
     * 
     * Registers a POST request route.
     * @param string $route The route URL.
     * @param callable|null $callback - The function that runs when the route is accessed.
     * @param callable|null $permission_callback - Callback for checking permissions.
     * @return void
     * 
     * @since 1.0.0
     */

    public static function post(string $route = '', ?callable $callback = null, ?callable $permission_callback = null): void
    {
        self::register_rest_api_route("POST", self::parse_wildcards($route), $callback, $permission_callback);
    }

    /**
     * METHOD - put
     * 
     * Registers a PUT request route.
     * @param string $route The route URL.
     * @param callable|null $callback - The function that runs when the route is accessed.
     * @param callable|null $permission_callback - Callback for checking permissions.
     * @return void
     * 
     * @since 1.0.0
     */

    public static function put(string $route = '', ?callable $callback = null, ?callable $permission_callback = null): void
    {
        self::register_rest_api_route("PUT", self::parse_wildcards($route), $callback, $permission_callback);
    }

    /**
     * METHOD - delete
     * 
     * Registers a Delete request route.
     * @param string $route The route URL.
     * @param callable|null $callback - The function that runs when the route is accessed.
     * @param callable|null $permission_callback - Callback for checking permissions.
     * @return void
     * 
     * @since 1.0.0
     */

    public static function delete(string $route = '', ?callable $callback = null, ?callable $permission_callback = null): void
    {
        self::register_rest_api_route("DELETE", self::parse_wildcards($route), $callback, $permission_callback);
    }
}
