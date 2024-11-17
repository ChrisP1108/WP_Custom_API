<?php

declare(strict_types=1);

namespace WP_Custom_API\Plugin;

use WP_Custom_API\Config;
use WP_Custom_API\Plugin\Migration;

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
            $file_path = WP_CUSTOM_API_PLUGIN_PATH . '/' . str_replace('\\', '/', $class) . '.php';
            if (file_exists($file_path)) {
                require_once $file_path;
                self::$files_loaded[] = $file_path;
            }
        });
    }

    /**
     * METHOD - folders_autoloader
     * 
     * Runs glob() to load all classes from files from folder within the root app folder
     * @return void
     * 
     * @since 1.0.0
     */

    public static function folders_autoloader(): void
    {
        foreach (Config::FOLDER_AUTOLOAD_PATHS as $folder_path) {
            foreach (glob(WP_CUSTOM_API_FOLDER_PATH . '/app/' . $folder_path . '/*.php') as $file) {
                if (file_exists($file)) {
                    require_once $file;
                    self::$files_loaded[] = $file;
                }
            }
        }
    }
}
