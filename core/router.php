<?php

declare(strict_types=1);

namespace WP_Custom_API\Core;

/** 
 * Used for setting up API Routes.  
 * Files in the routing folder utilize this class for establishing API Routes and passing in controller and permission callbacks.
 * 
 * @since 1.0.0
 */

class Router
{

    /**
     * CONSTANT
     * 
     * @const string BASE_API_ROUTE
     * Establishes base path for API. Any route will have a url path of {origin}/wp-json/custom-api/v1/${$route}
     * 
     * @since 1.0.0
     */

    private const BASE_API_ROUTE = "custom-api/v1";

    /**
     * METHOD - register_rest_api_route
     * 
     * Registers an API Route through Wordpress from either the Get, Post, Put, or Delete methods
     * @param string $method - HTTP method ('GET', 'POST', etc.).
     * @param string $route - The specific route to register.
     * @param callable $callback - The function that runs when the route is accessed.
     * @param callable|null $permission_callback - Callback for checking permissions.
     * @return void
     * 
     * @since 1.0.0
     */

    public static function register_rest_api_route(string $method, string $route, ?callable $callback, ?callable $permission_callback)
    {
        add_action("rest_api_init", function () use ($method, $route, $callback, $permission_callback) {
            register_rest_route(self::BASE_API_ROUTE, $route, [
                "methods" => $method,
                "permission_callback" => $permission_callback ? $permission_callback : '__return_true',
                "callback" => $callback
            ]);
        });
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
        $route = '/' . ltrim($route, '/');
        self::register_rest_api_route("GET", $route, $callback, $permission_callback);
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
        $route = '/' . ltrim($route, '/');
        self::register_rest_api_route("POST", $route, $callback, $permission_callback);
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
        $route = '/' . ltrim($route, '/');
        self::register_rest_api_route("PUT", $route, $callback, $permission_callback);
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
        $route = '/' . ltrim($route, '/');
        self::register_rest_api_route("DELETE", $route, $callback, $permission_callback);
    }
}
