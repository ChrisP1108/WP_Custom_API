<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Config;

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

final class Database
{

    /**
     * METHOD - response
     * 
     * Response handler for MySql database interaction.  Allows action hooks to be fired.
     * 
     * @param bool $ok - True or false boolean value if request was handled successfully or not.
     * @param string $message - Message to return describing if request was successful or not and what request was performed.
     * @param array|null $data - Data to return.  If there is a data, then an object will be returned.  Otherwise null is returned.
     * 
     * @return object - Returns an object with a key name of "ok" with a value of true or false, a "message" key with a message indicating invalid table name
     * @since 1.0.0
     */

    private static function response(bool $ok = false, string $message = '', ?array $data = null): array
    {
        $return_data = ['ok' => $ok, 'message' => $message, 'data' => $data];

        do_action('wp_custom_api_database_response', $return_data);

        return $return_data;
    }

    /**
     * METHOD - table_name_err_msg
     * 
     * Provides table name error message response.
     * @return array - Returns an object from response method with a key name of "ok" with a value of false, and a "message" key with a message indicating invalid table name, and a "data" key with value of null
     * @since 1.0.0
     */

    private static function table_name_err_msg(): array|object
    {
        return self::response(false, 'Invalid table name. Only alphanumeric characters and underscores are allowed.');
    }


    /**
     * METHOD - pagination
     * 
     * Handles pagination parameters for database queries.
     * Retrieves 'per_page' and 'page' values from GET request, with defaults and validation.
     * 
     * @return array - Returns an array with pagination details: 'per_page', 'page', and 'offset'.
     * @since 1.0.0
     */

    private static function pagination_data(): array
    {
        $pagination['per_page'] = isset($_GET['per_page'])
            ? min(100, max(1, intval($_GET['per_page'])))
            : 10;
        $pagination['page'] = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $pagination['offset'] = ($pagination['page'] - 1) * $pagination['per_page'];

        return $pagination;
    }

    /**
     * METHOD - pagination_headers
     * 
     * Set pagination headers
     * 
     * @param int $total_rows - The total number of rows in the table.
     * @param int $total_pages - The total number of pages.
     * @param int $limit - The number of records per page.
     * @param int $page - The current page number.
     * 
     * @since 1.0.0
     */

    private static function pagination_headers(string|int $total_rows, string|int $total_pages, string|int $limit, string|int $page): void
    {
        header('X-Total-Count: ' . intval($total_rows));
        header('X-Total-Pages: ' . intval($total_pages));
        header('X-Per-Page: ' . intval($limit));
        header('X-Current-Page: ' . intval($page));
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

        return $wpdb->prefix . Config::PREFIX . $table_name;
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

        $query = $wpdb->prepare("SHOW TABLES LIKE %s", $table_search_name);

        return $wpdb->get_var($query) ? true : false;
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

    public static function create_table(string $table_name, array $table_schema): array|object
    {
        if (self::table_exists($table_name)) return self::response(false, 'Table `' . $table_name . '` already exists in database.');

        global $wpdb;

        $table_create_name = self::get_table_full_name($table_name);

        if (!$table_create_name) return self::table_name_err_msg();

        $charset_collate = $wpdb->get_charset_collate();
        $create_table_query = "CREATE TABLE $table_create_name ( id mediumint(11) NOT NULL AUTO_INCREMENT, ";

        foreach ($table_schema as $key => $value) {
            $create_table_query .= esc_sql($key) . " " . esc_sql($value) . ", ";
        }

        $create_table_query .= "created DATETIME DEFAULT CURRENT_TIMESTAMP, updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id)) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($create_table_query);

        if ($wpdb->last_error) return self::response(false, 'An error occurred when attempting to create the table `' . $table_name . '`: ' . $wpdb->last_error);
        return self::table_exists($table_name)
            ? self::response(true, 'Table `' . $table_name . '` successfully created.')
            : self::response(false, 'An error occurred when attempting to create the table `' . $table_name . '`.');
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

    public static function drop_table(string $table_name): array|object
    {
        if (!self::table_exists($table_name)) return self::response(false, 'Table `' . $table_name . '` does not exist and therefore cannot be dropped.');

        global $wpdb;

        $table_to_drop_name = self::get_table_full_name($table_name);

        if (!$table_to_drop_name) return self::table_name_err_msg();

        $result = $wpdb->query("DROP TABLE IF EXISTS $table_to_drop_name");

        if (!$result) return self::response(false, 'An error occured when attempting to drop the table `' . $table_name . '`: ' . $wpdb->last_error);

        return !self::table_exists($table_name)
            ? self::response(true, 'Table `' . $table_name . '` was successfully dropped.')
            : self::response(false, 'An error occured when attempting to drop the table `' . $table_name . '`.');
    }

    /**
     * METHOD - get_table_data
     * 
     * Retrieves all rows from a specified table created by this plugin.
     * Validates that the table exists and retrieves all data.
     * * Has pagination with a limit of 10 row items by default, and a user can set a per_page url and page parameters, with per_page having a limit of 100.
     * 
     * @param string $table_name - The name of the table to retrieve data from.
     * @return array - Returns an array with a "found" key, "data" key, and a "message" key.
     *                 "found" will have either a true or false value,
     *                 "message" will have an associated message,
     *                 and "data" will contain the retrieved rows data if found.
     * 
     * @since 1.0.0
     */

    public static function get_table_data(string $table_name): array|object
    {
        if (!self::table_exists($table_name)) return self::response(false, 'Table `' . $table_name . '` does not exist and therefore no table rows data can be retrieved.');

        global $wpdb;

        $table_name_to_query = self::get_table_full_name($table_name);

        if (!$table_name_to_query) return self::table_name_err_msg();

        $pagination = self::pagination_data();

        $rows_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name_to_query LIMIT %d OFFSET %d", $pagination['per_page'], $pagination['offset']), ARRAY_A);

        $total_rows = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name_to_query");
        $total_pages = (int) ceil($total_rows / $pagination['per_page']);

        if ($pagination['page'] > $total_pages) return self::response(false, 'Page url param number provided for `' . $table_name . '` is greater than the total number of pages.');

        if (empty($rows_data)) return self::response(true, 'No table row data found. Table `' . $table_name . '` is empty.', []);

        self::pagination_headers($total_rows, $total_pages, $pagination['per_page'], $pagination['page']);

        return self::response(true, count($rows_data) . ' table row(s) retrieved successfully from `' . $table_name . '`.', $rows_data);
    }

    /**
     * METHOD - get_rows_data
     * 
     * Retrieves data from rows in a table created by this plugin that correspond to the same column and value.
     * Validates that the table exists and retrieves data for the specified ID.
     * Has pagination with a limit of 10 row items by default, and a user can set a per_page url and page parameters, with per_page having a limit of 100.
     * 
     * @param string $table_name - The name of the table to retrieve data from.
     * @param string $column - Column name to search for based upon value
     * @param string|int $value - The value of the column to search for
     * @param bool $multiple - Boolean to determine if more than one table row should be returned.
     * @return array - Returns an array with a "found" key, "data" key, and a "message" key. 
     *                "found" will have either a true or false value, 
     *                "message" will have an associated message,
     *                and "data" will contain the retrieved data of rows corresponding to the same column and value 
     * 
     * @since 1.0.0
     */

    public static function get_rows_data(string $table_name, string $column, ?string $value, bool $multiple = true): array|object
    {
        if (!self::table_exists($table_name)) return self::response(false, 'Table `' . $table_name . '` does not exist and therefore no table rows data can be retrieved.');

        global $wpdb;

        $table_name_to_query = self::get_table_full_name($table_name);

        if (!$table_name_to_query) return self::table_name_err_msg();

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) return self::response(false, 'Invalid column name provided for `' . $table_name . '`.');

        $pagination = self::pagination_data();

        $placeholder = is_numeric($value) ? "%d" : "%s";

        $count_query = $wpdb->prepare("SELECT COUNT(*) FROM $table_name_to_query WHERE $column = $placeholder", $value);
        $total_rows = (int) $wpdb->get_var($count_query);
        $total_pages = (int) ceil($total_rows / $pagination['$per_page']);

        $query = $wpdb->prepare("SELECT * FROM $table_name_to_query WHERE $column = $placeholder LIMIT %d OFFSET %d", $value, $pagination['per_page'], $pagination['offset']);

        $rows_data = $wpdb->get_results($query, ARRAY_A);

        self::pagination_headers($total_rows, $total_pages, $pagination['per_page'], $pagination['page']);

        if ($pagination['page'] > $total_pages) return self::response(false, 'Page url param number provided for `' . $table_name . '` is greater than the total number of pages.');

        if (empty($rows_data)) return self::response(false, 'No table rows found corresponding to the specified column name and value for `' . $table_name . '`.');

        if (!$multiple) {
            return self::response(true, 'Table row retrieved successfully for `' . $table_name . '` based upon search parameters.', $rows_data[0]);
        }

        return self::response(true, count($rows_data) . ' table row(s) retrieved successfully for `' . $table_name . '` based upon search parameters.', $rows_data);
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

    public static function insert_row(string $table_name, array $data): array|object
    {
        if (!self::table_exists($table_name)) return self::response(false, 'Table `' . $table_name . '` does not exist and therefore a row cannot be inserted.');

        global $wpdb;

        $table_name_to_insert = self::get_table_full_name($table_name);

        if (!$table_name_to_insert) return self::table_name_err_msg();

        $result = $wpdb->insert($table_name_to_insert, $data);

        if (!$result || $wpdb->insert_id === 0) return self::response(false, 'An error occurred while inserting data into row for `' . $table_name . '`: ' . $wpdb->last_error);

        return self::response(true, 'Table row for `' . $table_name . '` successfully inserted.', ['id' => $wpdb->insert_id]);
    }

    /**
     * METHOD - update_row
     * 
     * Updates an existing row in a specified table that was created by this plugin.
     * Validates that the table exists and that the data is properly formatted.
     * 
     * @param string $table_name - The name of the table to update the row in.
     * @param int $id - Id of table row to update.
     * @param array $data - An associative array of column names and their corresponding values to update.
     * @return array - Returns an array with an "updated" key and a "message" key. "updated" will have either a true or false value, and "message" key will have a message associated with it.
     * 
     * @since 1.0.0
     */

    public static function update_row(string $table_name, int $id, array $data): array|object
    {
        if (!self::table_exists($table_name)) return self::response(false, 'Table `' . $table_name . '` does not exist and therefore the table row cannot be updated.');

        global $wpdb;

        $table_name_to_update = self::get_table_full_name($table_name);

        if (!$table_name_to_update) return self::table_name_err_msg();

        $where = ['id' => intval($id)];

        $result = $wpdb->update($table_name_to_update, $data, $where);

        if ($result === false) return self::response(false, 'An error occurred while updating the table row for `' . $table_name . '`: ' . $wpdb->last_error);

        if ($result === 0) return self::response(false, 'Table row for `' . $table_name . '` could not be updated.  Please check the ID and make sure it corresponds to an existing table row.');

        return self::response(true, 'Table row for `' . $table_name . '` successfully updated.');
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

    public static function delete_row(string $table_name, int $id): array|object
    {
        if (!self::table_exists($table_name)) return self::response(false, 'Table `' . $table_name . '` does not exist and therefore the table row cannot be deleted.');

        global $wpdb;

        $table_name_to_delete_row = self::get_table_full_name($table_name);

        if (!$table_name_to_delete_row) return self::table_name_err_msg();

        $where = ['id' => intval($id)];

        $result = $wpdb->delete($table_name_to_delete_row, $where);

        if ($result === false) return self::response(false, 'An error occurred while attempting to delete the row for `' . $table_name . '`: ' . $wpdb->last_error);

        return self::response(true, 'Table row for `' . $table_name . '` successfully deleted.');
    }
}
