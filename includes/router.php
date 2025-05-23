<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP;
use WP_Custom_API\Config;
use WP_Custom_API\Includes\Error_Generator;
use WP_Custom_API\Includes\Permission_Interface as Permission;
use WP_REST_Request;
use WP_REST_Response;

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
     * Used to determine if routes have already been registered.
     */

    private static $routes_registered = false;


    /**
     * METHOD - register_rest_api_route
     * 
     * Register a REST API route.  Finds a route folder that the method was called from and registers the route relative to that folder name.
     * 
     * @param string $method The HTTP method to register the route for.  Accepted values are GET, POST, PUT, DELETE, OPTIONS, HEAD, and PATCH.
     * @param string $route The route to register.  This is the path of the route relative to the wp-json endpoint.  Wildcards are supported.
     * @param callable|null $callback The callback to run when the route is accessed.  If null, the method will throw an error.
     * @param callable|null $permission_callback The permission callback to run before the route is accessed.  If null, the method will throw an error.
     * 
     * @return void
     */
    
    private static function register_rest_api_route(string $method, string $route, ?callable $callback, ?callable $permission_callback): void
    {
        // Gets folder name that Router class was called from to create the base API route name

        $router_trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $router_folder_path = str_replace("/", "\\", dirname($router_trace[1]['file']));

        $base_folder_path = str_replace("/", "\\",WP_CUSTOM_API_FOLDER_PATH) . "api\\";

        $router_base_route = "/" . str_replace($base_folder_path, '', $router_folder_path);

        $router_base_route = str_replace("\\", "/", $router_base_route);

        // Check that permission callback is callable.  If not, return no_permission_callback_response and set permission_callback to true to display error message

        if (!is_callable($permission_callback)) {
            $no_permission_err_msg = 'A permission callback must be registered for the ' . $method . ' route `' . $router_base_route . $route . '`.';
            Error_Generator::generate('No Permission Callback', $no_permission_err_msg);
            $callback = function() use ($no_permission_err_msg) { 
                return new WP_Rest_Response(['message' => $no_permission_err_msg], 500);
            };
            $permission_callback = function () { return Permission::public(); };
        }

        // Register routes to $routes property

        self::$routes[] = [
            'method' => strtoupper($method),
            'route' => self::parse_wildcards($router_base_route. $route),
            'callback' => $callback,
            'permission_callback' => $permission_callback
        ];
    }

    /**
     * METHOD - init
     * 
     * Loops through routes that were registered, runs theirs permission callbacks and registers them to Wordpress REST API through the rest_api_init action.
     * If permission callback returned false, an unauthrorized response is set for the callback.
     * Developers can utilize the Wordpress action and filter hooks to customize routes from outside of thie plugin.
     * After routes are registered, the routes_registered property is set to true to prevent duplicate registrations.
     * 
     * @return void
     */

    public static function init(): void
    {
        if (self::$routes_registered) return;

        self::$routes = apply_filters('wp_custom_api_routes_filter', self::$routes);

        self::$routes_registered = true;

        add_action('rest_api_init', function () {
            foreach (self::$routes as $route) {

                // Callback wrapper to allow custom Permission callback unauthorized response to be set by running the main callback and checking if it returned true or false
                $wrapped_callback = function (WP_REST_Request $request) use ($route) {
                    $ok = call_user_func( $route['permission_callback'], $request );

                    if (!is_bool($ok)) {
                        $non_bool_message = 'The permission callback registered for the ' . $route['method'] . ' route `' . $route['route'] . '` returned a non-boolean value.  It must return true or false.';
                        return new WP_Rest_Response(['message' => $non_bool_message], 500);
                    }
                        
                    if (!$ok) return new WP_Rest_Response(['message' => 'Unauthorized'], 401);

                    return call_user_func($route['callback'], $request);
                };

                register_rest_route(Config::BASE_API_ROUTE, $route['route'], [
                    'methods' => $route['method'],
                    'callback' => $wrapped_callback,
                    'permission_callback' => '__return_true'
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
     * 
     * @return string
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
     * 
     * @return void
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
     * 
     * @return void
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
     * 
     * @return void
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
     * 
     * @return void
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
     * 
     * @return void
     */

    public static function match(array $methods, string $route, ?callable $callback, ?callable $permission_callback = null): void
    {
        foreach ($methods as $method) {
            self::register_rest_api_route($method, $route, $callback, $permission_callback);
        }
    }
}