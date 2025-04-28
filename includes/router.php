<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Config;
use WP_Custom_API\Includes\Permission_Interface;
use WP_Custom_API\Includes\Error_Generator;
use WP_Error as Error;
use WP_REST_Response as Response;

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
     * PROPERTY
     * 
     * @array routes
     * Collects routes to register before the Init method is called.
     * 
     * @since 1.0.0
     */

    private static $routes = [];

    /**
     * PROPERTY
     * 
     * @bool routes_registered
     * USed to determine if routes have already been registered.
     * 
     * @since 1.0.0
     */

    private static $routes_registered = false;

    /**
     * METHOD - register_rest_api_route
     * 
     * Registers an API Route to the routes property to get registered through the Wordpress rest_api_init action.
     * @param string $method - HTTP method ('GET', 'POST', etc.).
     * @param string $route - The specific route to register.  Handles parameters if passed in
     * @param callable $callback - The function that runs when the route is accessed.
     * @param callable|null $permission_callback - Callback for checking permissions.
     * @return void
     * 
     * @since 1.0.0
     */

    private static function register_rest_api_route(string $method, string $route, ?callable $callback, ?callable $permission_callback): void
    {

        // Check that permission callback is callable.  If not, return no_permission_callback_response and set permission_callback to true to display error message
        if (!is_callable($permission_callback)) {
            $callback = function() use($method, $route) { return Permission_Interface::no_permission_callback_response($method, $route);};
            $permission_callback = function() { return true; };
            Error_Generator::generate('No Permission Callback', 'A permission callback must be registered for the ' . $method . ' route ' . $route . '.');
        } 

        self::$routes[] = [
            'method' => strtoupper($method),
            'route' => self::parse_wildcards($route),
            'callback' => $callback,
            'permission_callback' => $permission_callback
        ];
    }

    /**
     * METHOD - init
     * 
     * Loops through routes that were registered and registers them to Wordpress REST API through the rest_api_init action.
     * Developers can utilize the Wordpress action and filter hooks to customize routes from outside of thie plugin.
     * After routes are registered, the routes_registered property is set to true to prevent duplicate registrations.
     * @return void
     * 
     * @since 1.0.0
     */

    public static function init(): void
    {
        if (self::$routes_registered) return;

        self::$routes = apply_filters('wp_custom_api_routes_filter', self::$routes);

        self::$routes_registered = true;

        add_action('rest_api_init', function () {
            foreach (self::$routes as $route) {
                register_rest_route(Config::BASE_API_ROUTE, $route['route'], [
                    'methods' => $route['method'],
                    'callback' => $route['callback'],
                    'permission_callback' => $route['permission_callback']
                ]);
            }
        });

        do_action('wp_custom_api_routes_registered', self::$routes);
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
            return '(?P<' . $matches[1] . '>[a-zA-Z0-9_-]+)';
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

    public static function get(string $route = '', ?callable $callback = null, ?callable $permission_callback = null): void
    {
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
        self::register_rest_api_route("PUT", $route, $callback, $permission_callback);
    }

    /**
     * METHOD - delete
     * 
     * Registers a DELETE request route.
     * @param string $route The route URL.
     * @param callable|null $callback - The function that runs when the route is accessed.
     * @param callable|null $permission_callback - Callback for checking permissions.
     * @return void
     * 
     * @since 1.0.0
     */

    public static function delete(string $route = '', ?callable $callback = null, ?callable $permission_callback = null): void
    {
        self::register_rest_api_route("DELETE", $route, $callback, $permission_callback);
    }

    /**
     * METHOD - match
     * 
     * Registers multiple routes for different HTTP methods.
     * @param array $methods An array of HTTP methods.
     * @param string $route The route URL.
     * @param callable|null $callback - The function that runs when the route is accessed.
     * @param callable|null $permission_callback - Callback for checking permissions.
     * @return void
     * 
     * @since 1.0.0
     */

    public static function match(array $methods, string $route, ?callable $callback, ?callable $permission_callback = null): void
    {
        foreach ($methods as $method) {
            self::register_rest_api_route($method, $route, $callback, $permission_callback);
        }
    }
}