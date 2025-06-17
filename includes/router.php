<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Config;
use WP_Custom_API\Includes\Error_Generator;
use WP_Custom_API\Includes\Permission_Interface as Permission;
use WP_Custom_API\Includes\Init;
use WP_REST_Request;
use WP_REST_Response;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

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
     * Collects routes to register from request before the Init method is called.
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
     * Check if the current request matches a given route and method.
     *
     * @param string $method The HTTP method to check against (e.g., GET, POST).
     * @param string $route The route pattern to match, with optional placeholders.
     * @return bool True if the request matches the route and method, false otherwise.
     */
    private static function route_matches_request(string $method, string $route): bool
    {
        // Check if the HTTP method matches the requested method
        if (Init::$requested_route_data['method'] !== strtoupper($method)) {
            return false;
        }

        // Trim the requested route and base route for comparison
        $requested = trim(Init::$requested_route_data['route'], '/');
        $base = trim(Init::$requested_route_data['name'], '/');
        $pattern = trim($route, '/');
        $combined = $base . ($pattern === '' ? '' : "/{$pattern}");

        // Replace route placeholders with a unique wildcard identifier
        $placeholder = preg_replace('#\{(\w+)\}#', '__WILD__$1__', $combined);
        // Escape special regex characters in the pattern
        $escaped = preg_quote($placeholder, '#');

        // Convert wildcards to named regex groups for matching
        $regex = preg_replace_callback(
            '/__WILD__(\w+)__/',
            fn($m) => '(?P<' . $m[1] . '>[A-Za-z0-9_-]+)',
            $escaped
        );

        // Construct full regex pattern for matching the requested route
        $full_regex = "#^{$regex}$#";
        // Return whether the requested route matches the constructed regex
        return (bool) preg_match($full_regex, $requested);
    }

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
        // Check that route matches the request.  If not, the route will not be registered

        if (!self::route_matches_request($method, $route)) return;

        // Check that permission callback is callable.  If not, return no_permission_callback_response and set permission_callback to true to display error message

        if (!is_callable($permission_callback)) {
            $no_permission_err_msg = 'A permission callback must be registered for the ' . $method . ' route `' . Init::$requested_route_data['route'] . $route . '`.';
            Error_Generator::generate('No Permission Callback', $no_permission_err_msg);
            $callback = function() use ($no_permission_err_msg) { 
                return new WP_Rest_Response(['message' => $no_permission_err_msg], 500);
            };
            $permission_callback = function () { return Permission::public(); };
        }

        self::$routes[] = [
            'name' => Init::$requested_route_data['name'],
            'method' => strtoupper($method),
            'route' => self::parse_wildcards(Init::$requested_route_data['name'] . $route),
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
        if (self::$routes_registered || empty(self::$routes)) return;

        self::$routes_registered = true;

        self::$routes = apply_filters('wp_custom_api_route_filter', self::$routes);

        add_action('rest_api_init', function () {

            foreach (self::$routes as $route) {
            
                // Callback wrapper to allow custom Permission callback unauthorized response to be set by running the main callback and checking if it returned true or false

                $wrapped_callback = function (WP_REST_Request $request) use ($route) {
                    
                    // Run permission callback
                    $ok = call_user_func($route['permission_callback'], $request);

                    // Check if permission callback returned true or false.  If not, return error response message.
                    
                    // Check if non array of true or false value
                    $non_bool_value = false;
                    if (!is_array($ok) && !is_bool($ok)) {
                        $non_bool_value = true;
                    }

                    // Check if array returned with true or false value
                    if (is_array($ok) && !is_bool($ok[0])) {
                        $non_bool_value = true;
                    }

                    // Show error if no boolean value was returned from permission callback
                    if ($non_bool_value) {
                        $non_bool_message = 'The permission callback registered for the ' . $route['method'] . ' route `' . $route['route'] . '` returned a non-boolean value.  It must return true or false.';
                        Error_Generator::generate('Non-Bool Return Value For Permission Callback', $non_bool_message);
                        return new WP_Rest_Response(['message' => $non_bool_message], 500);
                    }

                    // Destructure if array was returned from permission callback
                    $permission_callback_data_params = null;
                    if (is_array($ok) && is_bool($ok[0])) {
                        $permission_callback_data_params = $ok[1] ?? null;
                        $ok = $ok[0];
                    }

                    // Return an unauthorized response if permission callback returned false
                    if (!$ok) return new WP_Rest_Response(['message' => 'Unauthorized'], 401);

                    // Run controller callback if permission callback returned true and pass in permission_data from permission callback
                    return call_user_func($route['callback'], $request, $permission_callback_data_params);
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