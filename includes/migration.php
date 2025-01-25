<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Includes\Database;
use WP_Custom_API\Includes\Error_Generator;

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
     * Will iterate through all model classes in the models folder and create tables 
     *      in the database for any in which the class constant RUN_MIGRATION is set to true if it hasn't been created yet.
     * 
     * @return void
     * 
     * @since 1.0.0
     */

    public static function init_all(): void
    {
        $models_classes_names = [];
        $all_declared_classes = get_declared_classes();
        foreach ($all_declared_classes as $class) {
            if (strpos($class, 'WP_Custom_API\App\Models') !== false) {
                $models_classes_names[] = $class;
            }
        }
        foreach ($models_classes_names as $model_class_name) {
            $model = new $model_class_name;
            $table_exists = Database::table_exists($model::table_name());
            if (!$table_exists && $model::run_migration() ?? false) {
                $table_creation_result = Database::create_table($class::table_name(), $class::table_schema());
                if (!$table_creation_result['ok']) {
                    Error_Generator::generate('Error creating table in database', 'The table name "'
                        . Database::get_table_full_name($class::table_name()) . '" had an error in being created in MySql through the WP_Custom_API plugin.');
                }
            }
        }
    }
}
