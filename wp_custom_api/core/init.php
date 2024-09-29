<?php

declare(strict_types=1);

namespace WP_Custom_API\Core;

use WP_Custom_API\Core\Migration;

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
     * @array FILES_LOADED
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
     *      and for loading classes from files from specific folder.  Migration init_all method is run
     *      to create tables in database for all models that have their RUN_MIGRATION property set to true.
     * @return void
     * 
     * @since 1.0.0
     */

    public static function run()
    {
        self::namespaces_autoloader();
        self::folders_autoloader();
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
            $file_path = WP_CUSTOM_API_BASE_PATH . '/' . str_replace('\\', '/', $class) . '.php';
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log("Autoloader error: File '{$file_path}' does not exist.");
            }
        });
    }

    /**
     * METHOD - folder_autoloader
     * 
     * Runs glob() to load all classes from files from specific folders
     * @return void
     * 
     * @since 1.0.0
     */

    public static function folders_autoloader(): void
    {
        foreach (WP_CUSTOM_API_FOLDER_AUTOLOAD_PATHS as $folder_path) {
            foreach (glob(WP_CUSTOM_API_ROOT_FOLDER_PATH . '/' . $folder_path . '/*.php') as $file) {
                require_once $file;
                self::$files_loaded[] = $file;
            }
        }
    }
}
