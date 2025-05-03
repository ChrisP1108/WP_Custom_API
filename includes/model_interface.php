<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Includes\Database;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) {
    exit;
}

/** 
 * Used for interating with Wordpress database. 
 * Allows API to create tables, drop tables, get table data, get table rows based upon corresponding columns and values, get a single table row based upon corresponding column and value, insert rows, update rows, and delete rows
 * 
 * NOTE - This works only for tables created through this plugin.  Other existing tables created elsewhere will not work with this Database class methods.
 * 
 * @since 1.0.0
 */

abstract class Model_Interface
{
    /**
     * METHOD - table_name
     * 
     * Get the name of the table.
     * 
     * @return string
     */

    abstract public static function table_name(): string;

    /**
     * METHOD - table_schema
     * 
     * Get the schema for the table.
     *
     * @return array
     */

    abstract public static function table_schema(): array;

    /**
     * METHOD - create_table
     * 
     * Create database table if table doesn't already exist.
     *
     * @return bool
     */

    abstract public static function create_table(): bool;

    /**
     * METHOD - table_columns
     * 
     * Get the table schema columns.
     *
     * @return array
     */
    
    final public static function table_columns(): array {
        return array_keys(static::table_schema());
    }

    /**
     * METHOD - table_exists
     * 
     * Check if the table exists in the database.
     *
     * @return bool
     */

    final public static function table_exists(): bool
    {
        return Database::table_exists(static::table_name());
    }

    /**
     * METHOD - create_new_table
     * 
     * Create the table in the database.
     *
     * @return array|object
     */

    final public static function create_new_table(): array|object
    {
        return Database::create_table(static::table_name(), static::table_schema());
    }

    /**
     * METHOD - drop_table
     * 
     * Drop the table from the database.
     *
     * @return array|object
     */

    final public static function drop_table(): array|object
    {
        return Database::drop_table(static::table_name());
    }

    /**
     * METHOD - get_table_data
     * 
     * Retrieve all data from the table.
     *
     * @return array|object
     */

    final public static function get_table_data(): array|object
    {
        return Database::get_table_data(static::table_name());
    }

    /**
     * METHOD - get_rows_data
     * 
     * Retrieve data from the table based on a specific column and value.
     *
     * @param string $column
     * @param string|null $value
     * @param bool $multiple
     * @return array|object
     */

    final public static function get_rows_data(string $column, ?string $value, bool $multiple = true): array|object
    {
        return Database::get_rows_data(static::table_name(), $column, $value, $multiple);
    }

    /**
     * METHOD - get_row_data
     * 
     * Insert a new row into the table.
     *
     * @param array $data
     * @return array|object
     */

    final public static function insert_row(array $data): array|object
    {
        return Database::insert_row(static::table_name(), $data);
    }

    /**
     * METHOD - update_row
     * 
     * Update a row in the table by ID.
     *
     * @param int $id
     * @param array $data
     * @return array|object
     */

    final public static function update_row(int $id, array $data): array|object
    {
        return Database::update_row(static::table_name(), $id, $data);
    }

    /**
     * METHOD - delete_row
     * 
     * Delete a row from the table by ID.
     *
     * @param int $id
     * @return array|object
     */

    final public static function delete_row(int $id): array|object
    {
        return Database::delete_row(static::table_name(), $id);
    }
}