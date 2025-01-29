<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Includes\Database;
use WP_Custom_API\Includes\Error_Generator;
use ReflectionClass;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) {
    exit;
}

/** 
 * Used for creating and dropping database tables.
 * Utilizes the WP_Custom_API\Includes\Database class for creating and dropping tables.
 * 
 * @since 1.0.0
 */

class Migration
{
    /**
     * METHOD - init_all
     * 
     * Will iterate through all model classes in the model array from the Init::get_files_loaded() method and create tables 
     *      in the database for any in which the class constant RUN_MIGRATION is set to true if it hasn't been created yet.
     * 
     * @return void
     * 
     * @since 1.0.0
     */

    public static function init_all(): void
    {
        $models_classes_names = [];
        $class_name = 'Model';
        $all_declared_classes = get_declared_classes();

        foreach ($all_declared_classes as $class) {
            if (str_starts_with($class, "WP_Custom_API")) {
                $short_name = (new ReflectionClass($class))->getShortName();
                if ($short_name === $class_name) {
                    $models_classes_names[] = $class;
                }
            }
        }

        foreach ($models_classes_names as $model_class_name) {
            $model = new $model_class_name;
            $table_exists = Database::table_exists($model::table_name());

            if (!$table_exists && method_exists($model, 'run_migration') && $model::run_migration() && !empty($model::table_schema())) {
                $table_creation_result = Database::create_table(
                    $model::table_name(), 
                    $model::table_schema()
                );

                if (!$table_creation_result['ok']) {
                    Error_Generator::generate(
                        'Error creating table in database', 
                        'The table name "' . Database::get_table_full_name($model::table_name()) . '" had an error in being created in MySql through the WP_Custom_API plugin.');
                }
            }
        }
    }
}
