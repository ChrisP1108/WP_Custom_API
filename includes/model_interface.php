<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Includes\Database;
use WP_Custom_API\Includes\Response_Handler;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

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
     * ABSTRACT METHOD - table_name
     * 
     * Get the name of the table.
     * 
     * @return string
     */

    abstract public static function table_name(): string;

    /**
     * ABSTRACT METHOD - schema
     * 
     * Set the schema for table creation, as well as type declaration, requirements, and character limits.
     *
     * @return array
     */

    abstract public static function schema(): array;

    /**
     * ABSTRACT METHOD - create_table
     * 
     * Create database table if table doesn't already exist.
     *
     * @return bool
     */

    abstract public static function create_table(): bool;

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
     * METHOD - get_table_data
     * 
     * Retrieve all data from the table.
     *
     * @return Response_Handler The response of the get table data operation from the self::response() method.
     */

    final public static function get_table_data(): Response_Handler
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
     * 
     * @return Response_Handler The response of the get rows data operation from the self::response() method.
     */

    final public static function get_rows_data(string $column, mixed $value, bool $multiple = true): Response_Handler
    {
        return Database::get_rows_data(static::table_name(), $column, $value, $multiple);
    }

    /**
     * METHOD - get_row_data
     * 
     * Insert a new row into the table.
     *
     * @param array $data
     * @return Response_Handler The response of the insert row operation from the self::response() method.
     */

    final public static function insert_row(array $data): Response_Handler
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
     * 
     * @return Response_Handler The response of the update row operation from the self::response() method.
     */

    final public static function update_row(int $id, array $data): Response_Handler
    {
        return Database::update_row(static::table_name(), $id, $data);
    }

    /**
     * METHOD - delete_row
     * 
     * Delete a row from the table by ID.
     *
     * @param int $id
     * 
     * @return Response_Handler The response of the delete row operation from the self::response() method.
     */

    final public static function delete_row(int $id): Response_Handler
    {
        return Database::delete_row(static::table_name(), $id);
    }

    /**
     * METHOD - execute_query
     * 
     * Execute a raw SQL query.
     * 
     * @param string $query The raw SQL query to execute.
     * 
     * @return Response_Handler The response of the query operation from the self::response() method.
     */

    final public static function execute_query(string $query): Response_Handler
    {
        return Database::execute_query($query);
    }
}