<?php

declare(strict_types=1);

namespace WP_Custom_API\Core;

/** 
 * Used for interating with Wordpress database. 
 * Allows API to create tables, drop tables, get table data, get table rows based upon corresponding columns and values, get a single table row based upon corresponding column and value, insert rows, update rows, and delete rows
 * 
 * NOTE - This works only for tables created through this plugin.  Other existing tables created elsewhere will not work with this Database class methods.
 * 
 * @since 1.0.0
 */

class Database
{

    /**
     * CONSTANT
     * 
     * @const string BASE_API_ROUTE
     * Establishes base path for API. Any route will have a url path of {origin}/wp-json/custom-api/v1/${$route}
     * 
     * @since 1.0.0
     */

    private const DB_CUSTOM_PREFIX = "custom_api_";

    /**
     * METHOD - table_name_err_msg
     * 
     * @param string $bool_key - The name of the key for the method returning an error
     * 
     * @return array - Returns an array with a key name based upon $bool_key value with a value of false, and a "message" key with a message indicating invalid table name
     * @since 1.0.0
     */

    public static function table_name_err_msg(string $bool_key): array
    {
        return [$bool_key => false, 'message' => 'Invalid table name. Only alphanumeric characters and underscores are allowed.'];
    }

    /**
     * METHOD - get_table_full_name
     * 
     * Get name of table utilizing Wordpress database prefix concatenated with the DB_CUSTOM_PREFIX
     * @param string $table_name - The name of the table to search for
     * 
     * @return string|null - Returns null if regex fails.  Returns string for table name otherwise.
     * @since 1.0.0
     */

    public static function get_table_full_name(string $table_name): ?string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) return null;
        global $wpdb;
        return $wpdb->prefix . self::DB_CUSTOM_PREFIX . $table_name;
    }

    /**
     * METHOD - table_exists
     * 
     * Checks Wordpress database if a specific table by name exists
     * @param string $table_name - The name of the table to search for
     * @return bool - Returns true if table name exists, false if it doesn't
     * 
     * @since 1.0.0
     */

    public static function table_exists(string $table_name): bool
    {
        global $wpdb;
        $table_search_name = self::get_table_full_name($table_name);
        return $wpdb->get_var("SHOW TABLES LIKE '$table_search_name'") ? true : false;
    }

    /**
     * METHOD - create_table
     * 
     * Creates a Wordpress database table.
     * Checks that a table of the same name does not exist, otherwise the method will return an array key "created" of a value of false, and a "message" key with a value "Table already exists in database".
     * @param string $table_name - The name of the table to check if exists and if not, create it
     * @param array $table_schema - An array consisting of keys for the column names, and their corresponding values indicating the data type 
     * @return array - Returns an array, with a "created" key, and a "message" key.  "created" key will have either a true or false value, and "message" key will have a message associated with it.
     * 
     * @since 1.0.0
     */

    public static function create_table(string $table_name, array $table_schema): array
    {
        if (self::table_exists($table_name)) return ['created' => false, 'message' => 'Table already exists in database.'];
        global $wpdb;
        $table_create_name = self::get_table_full_name($table_name);
        if (!$table_create_name) return self::table_name_err_msg('created');
        $charset_collate = $wpdb->get_charset_collate();
        $create_table_query = "CREATE TABLE $table_create_name ( id mediumint(11) NOT NULL AUTO_INCREMENT, ";
        foreach ($table_schema as $key => $value) {
            $create_table_query .= "$key $value, ";
        }
        $create_table_query .= "PRIMARY KEY (id)) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($create_table_query);
        if ($wpdb->last_error) return ['created' => false, 'message' => 'An error occurred when attempting to create the table: ' . $wpdb->last_error];
        return self::table_exists($table_name)
            ? ['created' => true, 'message' => 'Table successfully created.']
            : ['created' => false, 'message' => 'An error occurred when attempting to create the table.'];
    }

    /**
     * METHOD - drop_table
     * 
     * Drops Wordpress table.
     * This method only checks for tables that were created utilizing this plugin.  It will not check other tables that were created from other plugins or from Wordpress directly.
     * @param string $table_name - The name of the table to drop.
     * @return array - Returns an array, with a "dropped" key, and a "message" key.  "dropped" key will have either a true or false value, and "message" key will have a message associated with it.
     * 
     * @since 1.0.0
     */

    public static function drop_table(string $table_name): array
    {
        if (!self::table_exists($table_name)) return ['dropped' => false, 'message' => 'Table does not exist and therefore cannot be dropped.'];
        global $wpdb;
        $table_to_drop_name = self::get_table_full_name($table_name);
        if (!$table_to_drop_name) return self::table_name_err_msg('dropped');
        $result = $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS `$table_to_drop_name`"));
        if (!$result) return ['dropped' => false, 'message' => 'An error occured when attempting to drop the table:' . $wpdb->last_error];
        return !self::table_exists($table_name)
            ? ['dropped' => true, 'message' => 'Table was successfully dropped.']
            : ['dropped' => false, 'message' => 'An error occured when attempting to drop the table.'];
    }

    /**
     * METHOD - get_table_data
     * 
     * Retrieves all rows from a specified table created by this plugin.
     * Validates that the table exists and retrieves all data.
     * 
     * @param string $table_name - The name of the table to retrieve data from.
     * @return array - Returns an array with a "found" key, "data" key, and a "message" key.
     *                 "found" will have either a true or false value,
     *                 "message" will have an associated message,
     *                 and "data" will contain the retrieved rows data if found.
     * 
     * @since 1.0.0
     */

    public static function get_table_data(string $table_name): array
    {
        if (!self::table_exists($table_name)) return ['found' => false, 'message' => 'Table does not exist and therefore no table rows data can be retrieved.'];
        global $wpdb;
        $table_name_to_query = self::get_table_full_name($table_name);
        if (!$table_name_to_query) return self::table_name_err_msg('found');
        $rows_data = $wpdb->get_results("SELECT * FROM $table_name_to_query", ARRAY_A);
        if (empty($rows_data)) return ['found' => false, 'message' => 'No table row data found. Table is empty.'];
        return ['found' => true, 'message' => count($rows_data) . ' table row(s) data retrieved successfully.', 'data' => $rows_data];
    }

    /**
     * METHOD - get_rows_data
     * 
     * Retrieves data from rows in a table created by this plugin that correspond to the same column and value.
     * Validates that the table exists and retrieves data for the specified ID.
     * 
     * @param string $table_name - The name of the table to retrieve data from.
     * @param int $id - The ID of the row to retrieve.
     * @return array - Returns an array with a "found" key, "data" key, and a "message" key. 
     *                "found" will have either a true or false value, 
     *                "message" will have an associated message,
     *                and "data" will contain the retrieved data of rows corresponding to the same column and value 
     * 
     * @since 1.0.0
     */

    public static function get_rows_data(string $table_name, string $column, $value): array
    {
        if (!self::table_exists($table_name)) return ['found' => false, 'message' => 'Table does not exist and therefore no table rows data can be retrieved.'];
        global $wpdb;
        $table_name_to_query = self::get_table_full_name($table_name);
        if (!$table_name_to_query) return self::table_name_err_msg('found');
        if (is_numeric($value)) {
            $query = $wpdb->prepare("SELECT * FROM $table_name_to_query WHERE $column = %d", $value);
        } else {
            $query = $wpdb->prepare("SELECT * FROM $table_name_to_query WHERE $column = %s", $value);
        }
        $rows_data = $wpdb->get_results($query, ARRAY_A);
        if (empty($rows_data)) return ['found' => false, 'message' => 'No table rows found corresponding to the specified column name and value.'];
        return ['found' => true, 'message' => count($rows_data) . ' table row(s) data retrieved successfully.', 'data' => $rows_data];
    }

    /**
     * METHOD - get_row_data
     * 
     * Retrieves data from a single row in a table created by this plugin that correspond to the same column and value.
     * Validates that the table exists and retrieves data for the specified ID.
     * 
     * @param string $table_name - The name of the table to retrieve data from.
     * @param int $id - The ID of the row to retrieve.
     * @return array - Returns an array with a "found" key, "data" key, and a "message" key. 
     *                "found" will have either a true or false value, 
     *                "message" will have an associated message,
     *                and "data" will contain the data of the row corresponding to the same column and value 
     * 
     * @since 1.0.0
     */

    public static function get_row_data(string $table_name, string $column, $value): array
    {
        if (!self::table_exists($table_name)) return ['found' => false, 'message' => 'Table does not exist and therefore no table rows data can be retrieved.'];
        global $wpdb;
        $table_name_to_query = self::get_table_full_name($table_name);
        if (!$table_name_to_query) return self::table_name_err_msg('found');
        if (is_numeric($value)) {
            $query = $wpdb->prepare("SELECT * FROM $table_name_to_query WHERE $column = %d", $value);
        } else {
            $query = $wpdb->prepare("SELECT * FROM $table_name_to_query WHERE $column = %s", $value);
        }
        $row_data = $wpdb->get_row($query, ARRAY_A);
        if ($row_data === null) return ['found' => false, 'message' => 'No table row found corresponding to the specified column name and value.'];
        return ['found' => true, 'message' => 'Table row data retrieved successfully.', 'data' => $row_data];
    }

    /**
     * METHOD - insert_row
     * 
     * Inserts a new row into a specified table that was created by this plugin.
     * Validates that the table exists and that the data is properly formatted.
     * 
     * @param string $table_name - The name of the table to insert the data into.
     * @param array $data - An associative array of column names and their corresponding values.
     * @return array - Returns an array with a "inserted" key and a "message" key. "inserted" will have either a true of false value, and "message" key will have a message associated with it.  If insert was successful, a key of "id" with its value will be included.
     * 
     * @since 1.0.0
     */

    public static function insert_row(string $table_name, array $data): array
    {
        if (!self::table_exists($table_name)) return ['inserted' => false, 'message' => 'Table does not exist and therefore a row cannot be inserted.'];
        global $wpdb;
        $table_name_to_insert = self::get_table_full_name($table_name);
        if (!$table_name_to_insert) return self::table_name_err_msg('inserted');
        $result = $wpdb->insert($table_name_to_insert, $data);
        if (!$result || $wpdb->insert_id === 0) return ['inserted' => false, 'message' => 'An error occurred while inserting data into row: ' . $wpdb->last_error];
        return ['inserted' => true, 'message' => 'Table row successfully inserted.', 'id' => $wpdb->insert_id];
    }

    /**
     * METHOD - update_row
     * 
     * Updates an existing row in a specified table that was created by this plugin.
     * Validates that the table exists and that the data is properly formatted.
     * 
     * @param string $table_name - The name of the table to update the row in.
     * @param array $data - An associative array of column names and their corresponding values to update.
     * @param array $where - An associative array of column names and their corresponding values to identify the row(s) to update.
     * @return array - Returns an array with an "updated" key and a "message" key. "updated" will have either a true or false value, and "message" key will have a message associated with it.
     * 
     * @since 1.0.0
     */

    public static function update_row(string $table_name, int $id, array $data): array
    {
        if (!self::table_exists($table_name)) return ['updated' => false, 'message' => 'Table does not exist and therefore the table row cannot be updated.'];
        global $wpdb;
        $table_name_to_update = self::get_table_full_name($table_name);
        if (!$table_name_to_update) return self::table_name_err_msg('updated');
        $where = ['id' => intval($id)];
        $result = $wpdb->update($table_name_to_update, $data, $where);
        if ($result === false) return ['updated' => false, 'message' => 'An error occurred while updating the table row: ' . $wpdb->last_error];
        if ($result === 0) return ['updated' => false, 'message' => 'Table row could not be updated.  Please check the ID and make sure it corresponds to an existing table row.'];
        return ['updated' => true, 'message' => 'Table row successfully updated.'];
    }

    /**
     * METHOD - delete_row
     * 
     * Deletes a row from a specified table created by this plugin.
     * Validates that the table exists and deletes the row based on the provided ID.
     * 
     * @param string $table_name - The name of the table to delete the row from.
     * @param int $id - The ID of the row to delete.
     * @return array - Returns an array with a "deleted" key and a "message" key. 
     *                 "deleted" will have either a true or false value,
     *                 and "message" will provide an associated message.
     * 
     * @since 1.0.0
     */

    public static function delete_row(string $table_name, int $id): array
    {
        if (!self::table_exists($table_name)) return ['deleted' => false, 'message' => 'Table does not exist and therefore the table row cannot be deleted.'];
        global $wpdb;
        $table_name_to_delete_row = self::get_table_full_name($table_name);
        if (!$table_name_to_delete_row) return self::table_name_err_msg('deleted');
        $where = ['id' => intval($id)];
        $result = $wpdb->delete($table_name_to_delete_row, $where);
        if ($result === false) return ['deleted' => false, 'message' => 'An error occurred while attempting to delete the row: ' . $wpdb->last_error];
        return ['deleted' => true, 'message' => 'Table row successfully deleted.'];
    }
}
