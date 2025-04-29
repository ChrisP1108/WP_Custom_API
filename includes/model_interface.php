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
     * Get the name of the table.
     * 
     * @return string
     */

    abstract public static function table_name(): string;

    /**
     * Get the schema for the table.
     *
     * @return array
     */

    abstract public static function table_schema(): array;

    /**
     * Run database migrations.
     *
     * @return bool
     */

    abstract public static function run_migration(): bool;

    /**
     * Check if the table exists in the database.
     *
     * @return bool
     */

    public static function table_exists(): bool
    {
        return Database::table_exists(static::table_name());
    }

    /**
     * Create the table in the database.
     *
     * @return array|object
     */

    final public static function create_table(): array|object
    {
        return Database::create_table(static::table_name(), static::table_schema());
    }

    /**
     * Drop the table from the database.
     *
     * @return array|object
     */

    final public static function drop_table(): array|object
    {
        return Database::drop_table(static::table_name());
    }

    /**
     * Retrieve all data from the table.
     *
     * @return array|object
     */

    final public static function get_table_data(): array|object
    {
        return Database::get_table_data(static::table_name());
    }

    /**
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
