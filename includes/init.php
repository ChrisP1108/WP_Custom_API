<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Config;
use WP_Custom_API\Includes\Database;
use WP_Custom_API\Includes\Router;
use WP_Custom_API\Includes\Error_Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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
     * Initializes the plugin by running spl_auto_load_register for class namespacing 
     *      and for loading files within the application folder from the FILES_TO_AUTOLOAD constant in the CONFIG class. 
     *      create_tables method is run to create tables in database for all models that have their RUN_MIGRATION property set to true.
     *      It will then run the init method of the Router class to register all routes to the Wordpress REST API.
     * 
     * @return void
     */

    private function __construct()
    {
        self::namespaces_autoloader();
        self::files_autoloader();
        do_action('wp_custom_api_file_autoloaded', self::$files_loaded);
        self::create_tables();
        Router::init();
    }

    /**
     * METHOD - run
     * 
     * Instantiates Init class constructor if $instantiated is set to false.
     * Once instantiated, $instantiated is set to true and 'wp_custom_api_loaded' action is run.
     * This helps prevent more than one instance of the Init class from being loaded.
     * 
     * @return void
     */

    public static function run(): void
    {
        if (!self::$instantiated) {
            new self();
            self::$instantiated = true;

            do_action('wp_custom_api_loaded', self::$files_loaded);
        }
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

        $file_name = strtolower(pathinfo($file, PATHINFO_FILENAME));

        $file_data = [
            'name' => $file_name,
            'path' => $file,
            'namespace' => $namespace
        ];

        if ($class) {
            $file_data['class'] = $class;
        }

        self::$files_loaded[] = $file_data;
    }

    /**
     * METHOD - namespaces_autoloader
     * 
     * Runs spl_auto_load_register for class importing based upon namespace
     * 
     * @return void
     */

    private static function namespaces_autoloader(): void
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'WP_Custom_API') !== 0) {
                return;
            }
            $relative_class = str_replace('WP_Custom_API\\', '', $class);
            $file = WP_CUSTOM_API_FOLDER_PATH . strtolower($relative_class) . '.php';
            self::load_file($file, $class);
        });
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
        // $route_requested_path = str_replace('/wp-json/' . Config::BASE_API_ROUTE, '', $_SERVER['REQUEST_URI']);

        // $route_requested_path = explode('?', $route_requested_path)[0];

        // $route_requested_path = explode('/',$route_requested_path)[1];

        $route_requested_path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

        $route_prefix = '/wp-json/'. Config::BASE_API_ROUTE . '/';

        if ( strpos( $route_requested_path, $route_prefix ) !== 0 ) {
            return;
        }

        $route_path = str_replace($route_prefix, '', $route_requested_path);
        $route_path = str_replace('\\', '/', $route_path);
        $route_path = preg_replace('#/+#', '/', $route_path);

        $base_route_folder = substr( $route_requested_path, strlen( $route_prefix ) );
        $base_route_folder = strtok( trim( $base_route_folder, '/' ), '/' );
        $base_route_folder = str_replace("\\", "/", $base_route_folder);
        $base_route_folder = preg_replace('#/+#', '/', $base_route_folder);

        self::$requested_route_data = [
            'folder' => WP_CUSTOM_API_FOLDER_PATH . 'api/' . $base_route_folder,
            'name' => $base_route_folder,
            'method' => $_SERVER['REQUEST_METHOD'],
            'route' => $route_path
        ];

        $all_files_to_load = apply_filters('wp_custom_api_files_to_autoload', Config::FILES_TO_AUTOLOAD);

        foreach ($all_files_to_load as $filename) {
            try {
                $path = self::$requested_route_data['folder'] . "/{$filename}.php";
                if ( file_exists( $path ) ) {
                    self::load_file( $path );
                }
            } catch (Exception $e) {
                Error_Generator::generate('File load error', 'Error loading ' . $filename . '.php file in "api" folder at ' . WP_CUSTOM_API_FOLDER_PATH . '/api: ' . $e->getMessage());
            }
        }
    }

    /**
     * METHOD - create_tables
     * 
     * Will iterate through all model classes in the model array from the Init::get_files_loaded() method and create tables 
     *      in the database for any model class fiels that have its create_table method return true if it hasn't been created yet.
     * Calls a Wordpress action hook after migrations are finished
     * 
     * @return void
     */

    private static function create_tables(): void
    {
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
            $table_exists = Database::table_exists($model::table_name());

            if (!$table_exists && $model::table_name() !== '' && method_exists($model, 'create_table') && $model::create_table() && !empty($model::schema())) {
                $table_creation_result = Database::create_table(
                    $model::table_name(),
                    $model::schema()
                );
                if (!$table_creation_result->ok) {
                    Error_Generator::generate(
                        'Error creating table in database',
                        'The table name "' . Database::get_table_full_name($model::table_name()) . '" had an error in being created in MySql through the WP_Custom_API plugin.'
                    );
                }
            }
        }

        do_action('wp_custom_api_migrations_run', $models_classes_names);
    }
}
