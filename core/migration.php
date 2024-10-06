<?php

declare(strict_types=1);

namespace WP_Custom_API\Core;

use WP_Custom_API\Core\Database;
use WP_Custom_API\Core\Error_Generator;

/** 
 * Used for creating and dropping database tables.
 * Utilizes the WP_Custom_API\Core\Database class for creating and dropping tables.
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
        foreach($all_declared_classes as $class) {
            if (strpos($class, 'WP_Custom_API\Models') !== false) {
                $models_classes_names[] = $class;
            }
        }
        foreach ($models_classes_names as $model_class_name) {
            $model = new $model_class_name;
            $table_exists = Database::table_exists($model::TABLE_NAME);
            if (!$table_exists && $model::RUN_MIGRATION ?? false) {
                $table_creation_result = Database::create_table($class::TABLE_NAME, $class::TABLE_SCHEMA);
                if (!$table_creation_result['created']) {
                    Error_Generator::generate('Error creating table in database', 'The table name "'
                        . Database::get_table_full_name($class::TABLE_NAME) . '" had an error in being created in MySql through the WP_Custom_API plugin.');
                }
            }
        }
    }
}