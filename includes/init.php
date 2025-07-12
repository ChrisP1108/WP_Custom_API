<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Config;
use WP_Custom_API\Includes\Database;
use WP_Custom_API\Includes\Router;
use WP_Custom_API\Includes\Error_Generator;
use WP_Custom_API\Includes\Session;
use WP_Custom_API\Hooks;
use Exception;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

/** 
 * Runs spl_autoload_register for all classes throughout the plugin based upon namespaces
 * 
 * @since 1.0.0
 */

final class Init
{

    /**
     * PROPERTY
     * 
     * @bool instantiated
     * Determines if Init class has been instantiated.
     */

    private static bool $instantiated = false;

    /**
     * PROPERTY
     * 
     * @array files_loaded
     * Stores a list of files that were autoloaded in the plugin
     */

    private static array $files_loaded = [];

    /**
     * PROPERTY
     * 
     * @array requested_route
     * Stores data about the requested route
     */

    public static array $requested_route_data;

    /**
     * METHOD - get_files_loaded
     * 
     * Returns list of files loaded as an array
     * 
     * @return array
     */

    public static function get_files_loaded(): array
    {
        return self::$files_loaded;
    }



    /**
     * CONSTRUCTOR
     * 
     * Creates an instance of the Init class, autoloads files, calls hooks, and sets up routes.
     * 
     * @return void
     */

    private function __construct()
    {
        // Call before_init from the hooks class before initializing plugin
        Hooks::before_init();

        // Autoload files based on Config::FILES_TO_AUTOLOAD constant
        self::files_autoloader();

        // Delete expired sessions from database
        Session::delete_expired_sessions();

        // Create tables
        self::create_tables();

        // Initialize routes
        Router::init();

        // Call after_init from the hooks class after initializing plugin
        Hooks::after_init();
    }

    /**
     * Runs the plugin by autoloading classes and files, creating tables in the database, and registering routes with the Wordpress REST API.
     * 
     * @return void
     */
    public static function run(): void
    {
        // Register namespaces_autoloader_callback for autoloading
        spl_autoload_register([self::class, 'namespaces_autoloader_callback']);

        // Check if the request is for the plugin and if the plugin hasn't been instantiated yet
        if (!self::$instantiated && self::request_to_plugin()) {
            new self();
            self::$instantiated = true;
            do_action('wp_custom_api_loaded', self::$files_loaded);
        }
    }

    /**
     * METHOD - request_to_plugin
     * 
     * Determines if the current request is directed towards the plugin's API routes.
     * Sets the requested route data if the request matches the expected route pattern.
     * 
     * @return bool True if the request is for the plugin, false otherwise.
     */

    private static function request_to_plugin(): bool
    {
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $prefix = '/wp-json/' . Config::BASE_API_ROUTE . '/';

        if (strpos($uri, $prefix) !== 0) {
            return false;
        }

        $after = trim(str_replace($prefix, '', $uri), '/');   // e.g. "youtube_blogs/categories/4"
        $api   = rtrim(WP_CUSTOM_API_FOLDER_PATH, '/') . '/api/';

        $segments = $after === '' ? [] : explode('/', $after);

        // find the deepest matching folder under api/
        $matched    = false;
        $matched_dir = '';
        $remainder  = '';
        for ($i = count($segments); $i > 0; $i--) {
            $cand = implode('/', array_slice($segments, 0, $i));
            if (is_dir($api . $cand)) {
                $matched    = true;
                $matched_dir = $cand;
                $remainder  = implode('/', array_slice($segments, $i));
                break;
            }
        }

        if (! $matched) {
            // fallback: first segment is folder
            $matched_dir = $segments[0] ?? '';
        }

        // Extract and sanitize the route path
        $route_path = str_replace($prefix, '', $uri);
        $route_path = str_replace('\\', '/', $route_path);
        $route_path = preg_replace('#/+#', '/', $route_path);

        $route_without_remainder = str_replace($remainder, '', $route_path);
        $route_without_remainder = str_replace('\\', '/', $route_without_remainder);
        $route_without_remainder = preg_replace('#/+#', '/', $route_without_remainder);

        self::$requested_route_data = [
            'folder' => $api . $matched_dir,
            'method' => $_SERVER['REQUEST_METHOD'],
            'route'  => $route_path,
            'route_without_remainder' => $route_without_remainder,
            'remainder' => $remainder
        ];

        return true;
    }

    /**
     * METHOD - load_file
     * 
     * Loads file and adds its path to $files_loaded property array
     * 
     * @return void
     */

    private static function load_file(string $file, string|null $class = null): void
    {
        $file = str_replace('\\', '/', $file);
        $file = preg_replace('#/+#', '/', $file);

        if (!file_exists($file)) {
            Error_Generator::generate('File load error', 'Error loading ' . $file . '.php file. The file does not exist');
            return;
        }

        require_once $file;

        $file_contents = file_get_contents($file);
        $namespace = null;

        if (preg_match('/namespace\s+([\w\\\\]+);/m', $file_contents, $matches)) {
            $namespace = trim($matches[1]);
        }

        $file_data = [
            'name' => strtolower(pathinfo($file, PATHINFO_FILENAME)),
            'path' => $file,
            'namespace' => $namespace
        ];

        if ($class) {
            $file_data['class'] = $class;
        }

        self::$files_loaded[] = $file_data;
    }

    /**
     * CALLBACK - namespaces_autoloader_callback
     * 
     * Callback used with spl_autoload_register for autoloading classes.
     * Only loads classes that start with the namespace WP_Custom_API.
     * 
     * @param string $class  Fully qualified class name
     * @return void
     */

    private static function namespaces_autoloader_callback(string $class): void
    {
        // Check if the class starts with the WP_Custom_API namespace
        if (strpos($class, 'WP_Custom_API') !== 0) {
            return;
        }

        // Get the relative class name by removing the namespace
        $relative_class = str_replace('WP_Custom_API\\', '', $class);

        // Create the file path by converting the class name to lowercase and adding the .php extension
        $file = WP_CUSTOM_API_FOLDER_PATH . strtolower($relative_class) . '.php';

        // Load the file and add its path to the $files_loaded property array
        self::load_file($file, $class);
    }

    /**
     * METHOD - api_routes_files_autoloader
     * 
     * Runs RecursiveDirectoryIterator and RecursiveIteratorIterator to load files that are in the CONFIG class FILES_TO_AUTOLOAD constant.
     * Only folders within the "api" folder that pertain to the request URL route are loaded.
     * Additional files can be loaded/modified through the Wordpress filter hook. 
     * Wordpress action hook is called at the end for other custom code to run after files are loaded.
     * 
     * @return void
     */

    private static function files_autoloader(): void
    {
        $all_files_to_load = apply_filters('wp_custom_api_files_to_autoload', Config::FILES_TO_AUTOLOAD);

        foreach ($all_files_to_load as $filename) {
            try {
                $path = self::$requested_route_data['folder'] . '/' . $filename . '.php';
                if (file_exists($path)) {
                    self::load_file($path);
                } else {
                    Error_Generator::generate('File load error', 'Error loading ' . $filename . '.php file in "api" folder');
                }
            } catch (Exception $e) {
                Error_Generator::generate('File load error', 'Error loading ' . $filename . '.php file in "api" folder at ' . WP_CUSTOM_API_FOLDER_PATH . '/api: ' . $e->getMessage());
            }
        }
        do_action('wp_custom_api_files_autoloaded', self::$files_loaded);
    }

    /**
     * METHOD - create_tables
     * 
     * Sessions table class is created if it does not exists for storing session data.  It will also iterate through all model classes in the model array from the Init::get_files_loaded() method and create tables 
     *      in the database for any model class fiels that have its create_table method return true if it hasn't been created yet.
     * Calls a Wordpress action hook after migrations are finished and stores tables created in the $tables_created for Wordpress transient storage.
     * 
     * @return void
     */

    private static function create_tables(): void
    {
        // Get existing tables created to avoid iterating through tables that have already been created
        $existing_tables_created = get_transient('wp_custom_api_tables_created');

        // List for gathering table names created
        $tables_created = [];

        // Check if sessions table was created.  If not, create it.
        if (!is_array($existing_tables_created) || !in_array(Session::SESSIONS_TABLE_NAME, $existing_tables_created)) {
            $table_exists = Database::table_exists(Session::SESSIONS_TABLE_NAME);
            if (!$table_exists) {
                $sessions_table_created_result = Database::create_table(
                    Session::SESSIONS_TABLE_NAME,
                    Session::SESSIONS_TABLE_QUERY
                );
                if (!$sessions_table_created_result->ok) {
                    Error_Generator::generate(
                        'Error creating sessions table in database',
                        'The sessions table name had an error in being created in MySql through the WP_Custom_API plugin.'
                    );
                } else {
                    $tables_created[] = Session::SESSIONS_TABLE_NAME;
                }
            }
        }

        $models_classes_names = [];
        $class_name = 'Model';

        foreach (self::$files_loaded as $file_data) {
            if (isset($file_data['namespace']) && isset($file_data['name']) && $file_data['name'] === 'model') {
                $class_name = $file_data['namespace'] . '\\' . $file_data['name'];

                if (class_exists($class_name)) {
                    $models_classes_names[] = $class_name;
                }
            }
        }

        foreach ($models_classes_names as $model_class_name) {
            $model = new $model_class_name;

            // Skip if table has already been created based upon Wordpress transient data.
            if ($existing_tables_created && in_array($model::table_name(), $existing_tables_created)) {
                continue;
            }

            $table_exists = Database::table_exists($model::table_name());

            if (!$table_exists && $model::table_name() !== '' && method_exists($model, 'create_table') && $model::create_table() && !empty($model::schema())) {
                $table_creation_result = Database::create_table(
                    $model::table_name(),
                    $model::schema()
                );
                if (!$table_creation_result->ok) {
                    Error_Generator::generate(
                        'Error creating table in database',
                        'The table name `' . Database::get_table_full_name($model::table_name()) . '` had an error in being created in MySql through the WP_Custom_API plugin.'
                    );
                } else {
                    $tables_created[] = $model::table_name();
                }
            }
        }

        // Call Wordpress action hook
        do_action('wp_custom_api_tables_created', $tables_created);

        // Store existing tables that have been created through this plugin in a Wordpress transient for better load times.
        if (!empty($tables_created)) {
            set_transient('wp_custom_api_tables_created', $tables_created, Config::DATABASE_REFRESH_INTERVAL);
        }
    }
}
