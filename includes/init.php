<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Includes\Database;
use WP_Custom_API\Includes\Error_Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Exception;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) {
    exit;
}

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
     * @array files_loaded
     * Stores a list of files that were autoloaded in the plugin
     * 
     * @since 1.0.0
     */

    private static $files_loaded = [];

    /**
     * METHOD - get_files_loaded
     * 
     * Returns list of files loaded as an array
     * @return array
     * 
     * @since 1.0.0
     */

    public static function get_files_loaded(): array
    {
        return self::$files_loaded;
    }

    /**
     * METHOD - Init
     * 
     * Initializes the plugin by running spl_auto_load_register for class namespacing 
     *      and for loading files within the application folder with the name 'routes.php' and 'model.php', as those files do not contain classes but rather utilize the Router class.  
     *      run_migrations method is run to create tables in database for all models that have their RUN_MIGRATION property set to true.
     * @return void
     * 
     * @since 1.0.0
     */

    public static function run(): void
    {
        self::namespaces_autoloader();
        self::files_autoloader('model');
        self::run_migrations();
        self::files_autoloader('routes');
    }

    /**
     * METHOD - load_file
     * 
     * Loads file and adds its path to $files_loaded property array
     * 
     * @return void
     * 
     * @since 1.0.0
     */

    private static function load_file($file) {

        require_once $file;
        
        $file_contents = file_get_contents($file);
        $namespace = null;

        if (preg_match('/^namespace\s+([^;]+);/m', $file_contents, $matches)) {
            $namespace = trim($matches[1]);
        }

        $file_name = strtolower(pathinfo($file, PATHINFO_FILENAME));

        self::$files_loaded[] = [
            'name' => $file_name,
            'path' => $file,
            'namespace' => $namespace
        ];
    }

    /**
     * METHOD - namespaces_autoloader
     * 
     * Runs spl_auto_load_register for class importing based upon namespace
     * @return void
     * 
     * @since 1.0.0
     */

    private static function namespaces_autoloader(): void
    {
        spl_autoload_register(function ($class) {
            $file = WP_PLUGIN_DIR . '/' . str_replace('\\', '/', $class) . '.php';
            if (file_exists($file)) {
                self::load_file($file);
            }
        });
    }

    /**
     * METHOD - api_routes_files_autoloader
     * 
     * Runs RecursiveDirectoryIterator and RecursiveIteratorIterator to load all php files from folders within the api folder
     * 
     * @param $filename - Name of PHP files to load from within api folder.
     * @return void
     * 
     * @since 1.0.0
     */

    private static function files_autoloader(string $filename): void
    {
        try {
            $directory = new RecursiveDirectoryIterator(WP_CUSTOM_API_FOLDER_PATH . '/' . 'api');
            $iterator = new RecursiveIteratorIterator($directory);
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php' && $file->getFilename() === $filename . '.php') {
                    self::load_file($file->getPathname());
                }
            }
        } catch (Exception $e) {
            Error_Generator::generate('Error loading ' . $filename . '.php file in "api" folder at ' . WP_CUSTOM_API_FOLDER_PATH . '/api: ' . $e->getMessage());
        }
    }

    /**
     * METHOD - run_migrations
     * 
     * Will iterate through all model classes in the model array from the Init::get_files_loaded() method and create tables 
     *      in the database for any in which the class constant RUN_MIGRATION is set to true if it hasn't been created yet.
     * 
     * @return void
     * 
     * @since 1.0.0
     */

    private static function run_migrations(): void
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

            if (!$table_exists && $model::table_name() !== '' && method_exists($model, 'run_migration') && $model::run_migration() && !empty($model::table_schema())) {
                $table_creation_result = Database::create_table(
                    $model::table_name(),
                    $model::table_schema()
                );

                if (!$table_creation_result['ok']) {
                    Error_Generator::generate(
                        'Error creating table in database',
                        'The table name "' . Database::get_table_full_name($model::table_name()) . '" had an error in being created in MySql through the WP_Custom_API plugin.'
                    );
                }
            }
        }
    }
}
