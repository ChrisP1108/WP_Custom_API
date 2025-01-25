<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Includes\Migration;
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

class Init
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

    public static function get_files_loaded()
    {
        return self::$files_loaded;
    }

    /**
     * METHOD - Init
     * 
     * Initializes the plugin by running spl_auto_load_register for class namespacing 
     *      and for loading files within the application folder.  Migration init_all method is run
     *      to create tables in database for all models that have their RUN_MIGRATION property set to true.
     * @return void
     * 
     * @since 1.0.0
     */

    public static function run()
    {
        self::namespaces_autoloader();
        self::api_files_autoloader();
        Migration::init_all();
    }

    /**
     * METHOD - namespaces_autoloader
     * 
     * Runs spl_auto_load_register for class importing based upon namespace
     * @return void
     * 
     * @since 1.0.0
     */

    public static function namespaces_autoloader(): void
    {
        spl_autoload_register(function ($class) {
            $file = WP_CUSTOM_API_PLUGIN_PATH . '/' . str_replace('\\', '/', $class) . '.php';
            if (file_exists($file)) {
                require_once $file;
                self::$files_loaded[] = $file;
            }
        });
    }

    /**
     * METHOD - api_files_autoloader
     * 
     * Runs RecursiveDirectoryIterator and RecursiveIteratorIterator to load all .php files within the api folder
     * @return void
     * 
     * @since 1.0.0
     */

    public static function api_files_autoloader(): void
    {
        try {
            $directory = new RecursiveDirectoryIterator(WP_CUSTOM_API_FOLDER_PATH . '/' . 'api');
            $iterator = new RecursiveIteratorIterator($directory);
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    require_once $file->getPathname();
                    self::$files_loaded[] = $file;
                }
            }
        } catch (Exception $e) {
            Error_Generator::generate('Error loading application files: ' . $e->getMessage());
        }
    }
}
