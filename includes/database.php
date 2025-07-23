<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

use WP_Custom_API\Config;
use WP_Custom_API\Includes\Response_Handler;
use WP_Custom_API\Includes\Error_Generator;

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

final class Database
{

    /**
     * METHOD - response
     * 
     * Generates a standardized response array for database operations.
     * Triggers a custom action with the response data and handles error responses.
     * 
     * @param bool $ok Indicates if the operation was successful.
     * @param string|null $error_code Optional error code if the operation failed.
     * @param string $message Message detailing the response.
     * @param array|null $data Optional data to include in the response.
     * 
     * @return Response_Handler The standardized response object.
     */

    private static function response(bool $ok, int $status_code, string $message = '', ?array $data = null): Response_Handler
    {
        $return_data = Response_Handler::response($ok, $status_code, $message, $data);

        do_action('wp_custom_api_database_response', $return_data);

        return $return_data;
    }

    /**
     * METHOD - escaped_chars
     * 
     * Checks string for any special characters
     * 
     * @param string $string The string to escape.
     * 
     * @return bool True if the string contains only alphanumeric characters and underscores, false otherwise.
     */

    private static function escaped_chars(string $string): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_]+$/', $string);
    }

    /**
     * METHOD - table_name_err_msg
     * 
     * Provides table name error message response.
     * 
     * @return object - Returns an object from response method with a key name of "ok" with a value of false, and a "message" key with a message indicating invalid table name, and a "data" key with value of null
     */

    private static function table_name_err_msg(): object
    {
        return self::response(false, 500, 'Invalid table name. Only alphanumeric characters and underscores are allowed.');
    }

    /**
     * METHOD - pagination_params
     * 
     * Handles pagination parameters for database queries.
     * Retrieves 'per_page' and 'page' values from GET request, with defaults and validation.
     * 
     * @return array - Returns an array with pagination details: 'per_page', 'page', and 'offset'.
     */

    public static function pagination_params(): array
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
     * @return void
     */

    public static function pagination_headers(string|int $total_rows, string|int $total_pages, string|int $limit, string|int $page): void
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
     * @param bool $return_prefix = Used to determine if table name prefix only should be returned
     * 
     * @return string|null - Returns null if regex fails.  Returns string for table name otherwise.
     */

    public static function get_table_full_name(string $table_name, bool $return_prefix = false): ?string
    {
        if (!self::escaped_chars($table_name) && !$return_prefix) return null;

        global $wpdb;

        return $wpdb->prefix . Config::PREFIX . $table_name;
    }

    /**
     * METHOD - table_exists
     * 
     * Checks Wordpress database if a specific table by name exists
     * @param string $table_name - The name of the table to search for
     * 
     * @return bool - Returns true if table name exists, false if it doesn't
     */

    public static function table_exists(string $table_name): bool
    {
        global $wpdb;

        $table_search_name = self::get_table_full_name($table_name);

        ob_start();

        $query = $wpdb->prepare("SHOW TABLES LIKE %s", $table_search_name);

        ob_end_clean();

        return $wpdb->get_var($query) ? true : false;
    }

    /**
     * METHOD - create_table
     * 
     * Creates a Wordpress database table.
     * Checks that a table of the same name does not exist.
     * @param string $table_name - The name of the table to check if exists and if not, create it
     * @param array $table_schema - An array consisting of keys for the column names, and their corresponding values indicating the data type 
     * 
     * @return Response_Handler The response of the create table operation from the self::response() method.
     */

    public static function create_table(string $table_name, array $table_schema): Response_Handler
    {
        if (self::table_exists($table_name)) return self::response(false, 500, 'Table `' . $table_name . '` already exists in database.');

        global $wpdb;

        $table_create_name = self::get_table_full_name($table_name);

        if (!$table_create_name) return self::table_name_err_msg();

        $charset_collate = $wpdb->get_charset_collate();
        $create_table_query = "CREATE TABLE $table_create_name ( id mediumint(11) NOT NULL AUTO_INCREMENT, ";

        foreach ($table_schema as $key => $value) {
            if (!self::escaped_chars($key)) {
                $err_msg = 'Column name of `' . $key . '` contained invalid characters.';
                Error_Generator::generate('Column name error in schema', $err_msg);
                return self::response(false, 500, $err_msg);
            }
            $column_query = $value['query'] ?? null;
            if (!$column_query) {
                $err_msg = 'Column query for column name of `' . $key . '` is not specified.';
                Error_Generator::generate('Column query type not specified', $err_msg);
                return self::response(false, 500, $err_msg);
            }
            $pattern = '/^
            (?:
                    (?:TINYINT|INT|MEDIUMINT|BIGINT|VARBINARY|BINARY|VARCHAR|TEXT|LONGTEXT|JSON|BOOLEAN)
                    (?:\(\d+\))?
                )
                (?:\s+UNSIGNED)?
                (?:\s+NOT\s+NULL)?
            $/ix';
            if (!preg_match($pattern, $column_query)) {
                $err_msg = 'Column query for column name of `' . $key . '` contained invalid characters.';
                Error_Generator::generate('Column name error in schema', $err_msg);
                return self::response(false, 500, $err_msg);
            }
            $create_table_query .= $key . " " . $column_query . ", ";
        }

        $create_table_query .= "created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (id)) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        ob_start();

        dbDelta($create_table_query);

        ob_end_clean();

        if ($wpdb->last_error) return self::response(false, 500, 'An error occurred when attempting to create the table `' . $table_name . '`.');

        if (!self::table_exists($table_name)) {
            $err_msg = 'An error occurred when attempting to create the table `' . $table_create_name . '`.';
            Error_Generator::generate('Error creating SQL table', $err_msg . $wpdb->last_error);
            return self::response(false, 500, $err_msg);
        }

        return self::response(true, 201, 'Table `' . $table_create_name . '` successfully created.');
    }

    /**
     * METHOD - drop_table
     * 
     * Drops Wordpress table.
     * This method only checks for tables that were created utilizing this plugin.  It will not check other tables that were created from other plugins or from Wordpress directly.
     * @param string $table_name - The name of the table to drop.
     * 
     * @return Response_Handler The response of the drop table operation from the self::response() method.
     */

    public static function drop_table(string $table_name): Response_Handler
    {
        if (!self::table_exists($table_name)) return self::response(false, 500, 'Table `' . $table_name . '` does not exist and therefore cannot be dropped.');

        global $wpdb;

        $table_to_drop_name = self::get_table_full_name($table_name);

        if (!$table_to_drop_name) return self::table_name_err_msg();

        ob_start();

        $result = $wpdb->query("DROP TABLE IF EXISTS $table_to_drop_name");

        ob_end_clean();

        if (!$result) return self::response(false, 500, 'An error occured when attempting to drop the table `' . $table_name . '`.');

        return !self::table_exists($table_name)
            ? self::response(true, 200, 'Table `' . $table_name . '` was successfully dropped.')
            : self::response(false, 500, 'An error occured when attempting to drop the table `' . $table_name . '`.');
    }

    /**
     * METHOD - get_table_data
     * 
     * Retrieves all rows from a specified table created by this plugin.
     * Validates that the table exists and retrieves all data.
     * * Has pagination with a limit of 10 row items by default, and a user can set a per_page url and page parameters, with per_page having a limit of 100.
     * 
     * @param string $table_name - The name of the table to retrieve data from.
     * @param bool $get_all_rows - Determines if all rows should be returned or if pagination should be used.
     * 
     * @return Response_Handler The response of the get table data operation from the self::response() method.
     */

    public static function get_table_data(string $table_name, bool $get_all_rows = false): Response_Handler
    {
        if (!self::table_exists($table_name)) return self::response(false, 500, 'Table `' . $table_name . '` does not exist and therefore no table rows data can be retrieved.');

        global $wpdb;

        $table_name_to_query = self::get_table_full_name($table_name);

        if (!$table_name_to_query) return self::table_name_err_msg();

        ob_start();

        $rows_data = null;

        $pagination = self::pagination_params();

        if (!$get_all_rows) {
            $rows_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name_to_query LIMIT %d OFFSET %d", $pagination['per_page'], $pagination['offset']), ARRAY_A);

            $total_rows = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name_to_query");
            $total_pages = (int) ceil($total_rows / $pagination['per_page']);
        } else {
            $rows_data = $wpdb->get_results("SELECT * FROM $table_name_to_query", ARRAY_A);
            $total_rows = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name_to_query");
            $total_pages = 1;
        }

        if ($rows_data === false) return self::response(false, 500, 'An error occurred when attempting to retrieve data from the table `' . $table_name . '`.');

        ob_end_clean();

        if ($pagination['page'] > $total_pages && $total_rows > 0) return self::response(false, 400, 'Page url param number provided for `' . $table_name . '` is greater than the total number of pages.');

        if (empty($rows_data)) return self::response(true, 200, 'No table row data found. Table `' . $table_name . '` is empty.', []);

        self::pagination_headers($total_rows, $total_pages, $pagination['per_page'], $pagination['page']);

        return self::response(true, 200, count($rows_data) . ' table row(s) retrieved successfully from `' . $table_name . '`.', $rows_data);
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
     * 
     * @return Response_Handler The response of the get rows data operation from the self::response() method.
     */

    public static function get_rows_data(string $table_name, string $column, int|string $value, bool $multiple = true): Response_Handler
    {
        if (!self::table_exists($table_name)) return self::response(false, 500, 'Table `' . $table_name . '` does not exist and therefore no table rows data can be retrieved.');

        global $wpdb;

        $table_name_to_query = self::get_table_full_name($table_name);

        if (!$table_name_to_query) return self::table_name_err_msg();

        if (!self::escaped_chars($column)) return self::response(false, 400, 'Invalid column name provided for `' . $table_name . '`.');

        $pagination = self::pagination_params();

        $placeholder = is_numeric($value) ? "%d" : "%s";

        ob_start();

        $count_query = $wpdb->prepare("SELECT COUNT(*) FROM $table_name_to_query WHERE $column = $placeholder", $value);
        $total_rows = (int) $wpdb->get_var($count_query);
        $total_pages = (int) ceil($total_rows / $pagination['per_page']);

        $query = $wpdb->prepare("SELECT * FROM $table_name_to_query WHERE $column = $placeholder LIMIT %d OFFSET %d", $value, $pagination['per_page'], $pagination['offset']);

        $rows_data = $wpdb->get_results($query, ARRAY_A);

        if ($rows_data === false) return self::response(false, 500, 'An error occurred when attempting to retrieve data from the table `' . $table_name . '`.');

        ob_end_clean();

        self::pagination_headers($total_rows, $total_pages, $pagination['per_page'], $pagination['page']);

        if ($pagination['page'] > $total_pages && $total_rows > 0) return self::response(false, 400, 'Page url param number provided for `' . $table_name . '` is greater than the total number of pages.');

        if (empty($rows_data)) return self::response(true, 200, 'No table rows found corresponding to the specified column name and value for `' . $table_name . '`.');

        if (!$multiple) {
            return self::response(true, 200, 'Table row retrieved successfully for `' . $table_name . '` based upon search parameters.', $rows_data[0]);
        }

        return self::response(true, 200, count($rows_data) . ' table row(s) retrieved successfully for `' . $table_name . '` based upon search parameters.', $rows_data);
    }

    /**
     * METHOD - insert_row
     * 
     * Inserts a new row into a specified table that was created by this plugin.
     * Validates that the table exists and that the data is properly formatted.
     * 
     * @param string $table_name - The name of the table to insert the data into.
     * @param array $data - An associative array of column names and their corresponding values.
     * 
     * @return Response_Handler The response of the insert row operation from the self::response() method.
     */

    public static function insert_row(string $table_name, array $data): Response_Handler
    {
        if (!self::table_exists($table_name)) return self::response(false, 500, 'Table `' . $table_name . '` does not exist and therefore a row cannot be inserted.');

        global $wpdb;

        $table_name_to_insert = self::get_table_full_name($table_name);

        if (!$table_name_to_insert) return self::table_name_err_msg();

        if (is_object($data)) {
            $data = (array) $data;
        }

        ob_start();

        $result = $wpdb->insert($table_name_to_insert, $data);

        ob_end_clean();

        if (!$result || $wpdb->insert_id === 0) return self::response(false, 500, 'An error occurred while inserting data into row for table `' . $table_name . '`.');

        return self::response(true, 201, 'Table row for `' . $table_name . '` successfully inserted.', ['id' => $wpdb->insert_id]);
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
     * 
     * @return Response_Handler The response of the update row operation from the self::response() method.
     */

    public static function update_row(string $table_name, int $id, array $data): Response_Handler
    {
        if (!self::table_exists($table_name)) return self::response(false, 500, 'Table `' . $table_name . '` does not exist and therefore the table row cannot be updated.');

        global $wpdb;

        $table_name_to_update = self::get_table_full_name($table_name);

        if (!$table_name_to_update) return self::table_name_err_msg();

        $where = ['id' => intval($id)];

        if (is_object($data)) {
            $data = (array) $data;
        }

        ob_start();

        $result = $wpdb->update($table_name_to_update, $data, $where);

        ob_end_clean();

        if ($result === false) return self::response(false, 500, 'An error occurred while updating the table row for `' . $table_name . '`.');

        if ($result === 0) return self::response(false, 400, 'Table row for `' . $table_name . '` could not be updated.  Please check the ID and make sure it corresponds to an existing table row.');

        return self::response(true, 200, 'Table row for `' . $table_name . '` successfully updated.');
    }

    /**
     * METHOD - delete_row
     * 
     * Deletes a row from a specified table created by this plugin.
     * Validates that the table exists and deletes the row based on the provided ID.
     * 
     * @param string $table_name - The name of the table to delete the row from.
     * @param int $id - The ID of the row to delete.
     * 
     * @return Response_Handler The response of the delete row operation from the self::response() method.
     */

    public static function delete_row(string $table_name, int $id): Response_Handler
    {
        if (!self::table_exists($table_name)) return self::response(false, 500, 'Table `' . $table_name . '` does not exist and therefore the table row cannot be deleted.');

        global $wpdb;

        $table_name_to_delete_row = self::get_table_full_name($table_name);

        if (!$table_name_to_delete_row) return self::table_name_err_msg();

        $where = ['id' => intval($id)];

        ob_start();

        $result = $wpdb->delete($table_name_to_delete_row, $where);

        ob_end_clean();

        if ($result === false) return self::response(false, 500, 'An error occurred while attempting to delete the row for `' . $table_name . '`.');

        if ($result === 0) return self::response(false, 400, 'Table row for `' . $table_name . '` could not be deleted.  Please check the ID and make sure it corresponds to an existing table row.');

        return self::response(true, 200, 'Table row for `' . $table_name . '` successfully deleted.');
    }

    /**
     * METHOD - get_table_schema_from_db
     *
     * Pulls a table’s column definitions (minus the automatic id/created/updated)
     * and normalizes them into the format expected by create_table().
     *
     * @param string $table_name The table name without the prefix.
     * 
     * @return Response_Handler The response of the get table schema operation from the self::response() method.
     */

    public static function get_table_schema_from_db(string $table_name): Response_Handler
    {
        global $wpdb;

        // Get table data
        $table_full_name = self::get_table_full_name($table_name);

        // Return error if table does not exist
        if (!$table_full_name) return self::response(false, 500, 'Table `' . $table_name . '` does not exist.');

        // Get table columns schema
        $cols = $wpdb->get_results("DESCRIBE {$table_full_name}", ARRAY_A);
        $schema = [];

        foreach ($cols as $col) {
            $name = $col['Field'];
            // skip your own auto‐columns
            if (in_array($name, ['id', 'created', 'updated'], true)) {
                continue;
            }

            $type = strtoupper($col['Type']);
            if (preg_match('/^(?:TINY|SMALL|MEDIUM|BIG)?INT/i', $type)) {
                $query_type = 'INT';
            } elseif (preg_match('/^VARCHAR\((\d+)\)/i', $type, $m)) {
                $query_type = "VARCHAR({$m[1]})";
            } elseif (preg_match('/^(VAR)?BINARY\((\d+)\)/i', $type, $m)) {
                // m[1] is "VAR" or empty
                $query_type = ($m[1] ? 'VARBINARY' : 'BINARY') . "({$m[2]})";
            } elseif (strpos($type, 'TEXT') === 0) {
                $query_type = 'TEXT';
            } elseif (preg_match('/^BLOB/i', $type)) {
                $query_type = 'BLOB';
            } else {
                continue; // unsupported type
            }

            $schema[$name] = [
                'query' => $query_type,
            ];
        }

        return self::response(true, 200, 'Schema retrieved successfully for table `' . $table_name . '`', $schema);
    }

    /**
     * METHOD - get_all_tables_data
     * 
     * Retrieves all table data for the tables created by this plugin.
     * 
     * @return Response_Handler The response of the get all tables data operation from the self::response() method.
     */

    public static function get_all_tables_data(): Response_Handler
    {
        global $wpdb;

        $table_name_query = self::get_table_full_name('', true);

        if (!$table_name_query) return self::response(false, 500, 'An error occurred while attempting to retrieve table names or no tables exist.');

        $like_table_name_query = '%' . $table_name_query . '%';

        // Get all table names
        $table_names = $wpdb->get_col(
            $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $like_table_name_query
            )
        );

        if (!$table_names) return self::response(false, 500, 'An error occurred while attempting to retrieve table names.');

        if (empty($table_names)) return self::response(false, 500, 'No tables created by this plugin were found.');

        // Initialize an empty array to store the table data
        $tables_data = [];

        $error_getting_table_data = false;

        // Loop through each table name and collect its data
        foreach ($table_names as $table_name) {

            // Get table name without prefix
            $api_table_name = str_replace($table_name_query, '', $table_name);

            // Get table data and check if it returned data
            $response = self::get_table_data($api_table_name, true);
            if (!$response->ok) $error_getting_table_data = true;

            // Get table schema
            $schema = self::get_table_schema_from_db($api_table_name);
            if (!$schema->ok) $error_getting_table_data = true;

            // Add table data to the tables data array
            $tables_data[$api_table_name] = [
                'ok' => $schema->ok && $response->ok,
                'data' => $response->data,
                'schema' => $schema->data
            ];;
        }

        if ($error_getting_table_data) {
            return self::response(false, 500, 'An error occurred while attempting to retrieve table data.', $tables_data);
        }

        if (empty($tables_data)) {
            return self::response(false, 200, 'No tables data was found in the database.', $tables_data);
        }

        // Return the table data
        return self::response(true, 200, 'Tables data successfully retrieved.', $tables_data);
    }

    /**
     * METHOD - import_tables_data
     * 
     * Import tables data
     *
     * @param array $data - Associative array of table names as keys and another associative array of 'ok', 'schema' and 'data' as values.
     *                      'schema' is an associative array of column definitions.
     *                      'data' is an array of associative arrays of the row data to import.
     *
     * @return Response_Handler The response of the import tables data operation from the self::response() method.
     */

    public static function import_tables_data(array $data): Response_Handler
    {
        if (empty($data)) {
            return self::response(false, 400, 'No data was provided to import database tables.');
        }

        $results = [];

        $import_data_errors = [];

        if (is_object($data)) {
            $data = (array) $data;
        }

        // Create tables
        foreach ($data as $table_name => $table_data) {
            if (!$table_data['ok']) {
                $import_data_errors[] = $table_name;
                continue;
            }
            $create_table = self::create_table($table_name, $table_data['schema']);
            $results[$table_name] = ['table_created' => $create_table->ok];
        }

        // Check if any existing import data had errors where ok was false
        if (!empty($import_data_errors)) {
            return self::response(
                false,
                500,
                'The following tables from the import data indicated errors when it was exported: ' . implode(', ', $import_data_errors) . '.  Try exporting the data from the source database and importing again.',
                $results
            );
        }

        // Import data for each table created
        foreach ($results as $table_name => $result) {
            if ($result['table_created']) {
                $table_data = $data[$table_name]['data'];
                foreach ($table_data as $row) {
                    $insert_row_data = self::insert_row($table_name, $row);
                    $results[$table_name]['data_inserted'] = $insert_row_data->ok;
                }
            }
        }

        // Check that all tables and data were created successfully.  If not, return error
        foreach ($results as $table_name => $data) {
            if (!$data['table_created'] || !$data['data_inserted']) {
                return self::response(
                    false,
                    500,
                    'An error occurred while attempting to import one or more table(s) data.',
                    $results
                );
            }
        }

        // If all was successful, return success
        return self::response(true, 200, 'Tables data successfully imported.', $results);
    }

    /**
     * METHOD - generate_data_migration
     * 
     * Generate a data migration file that can be used to import data into another Wordpress
     * site.
     *
     * @param string $filename The name of the file to create without the .json extension.
     *                          Default is 'migration'
     * 
     * @return Response_Handler The response of the generate migration file operation from the self::response() method.
     */

    public static function generate_migration_file(string $filename = 'migration'): Response_Handler
    {
        // Check if filename already exists
        $file_path = WP_CUSTOM_API_FOLDER_PATH . strtolower($filename) . '.json';

        if (@file_get_contents($file_path) !== false) return self::response(false, 500, 'A file with the name ' . $filename . '.json already exists.');

        // Get data from database
        $get_table_data = self::get_all_tables_data();
        
        // If error occured, return error
        if (!$get_table_data->ok) return self::response(false, 500, 'An error occurred while attempting to retrieve table data.');

        // If empty, return error
        if (empty($get_table_data->data)) return self::response(false, 400, 'No tables data was found in the database.');

        // Create file
        $file_content = json_encode($get_table_data->data);
        $create_file = file_put_contents($file_path, $file_content);

        // If error creating file, return error
        if (!$create_file) return self::response(false, 500, 'An error occurred while attempting to create the data file.');

        // Return success if file successfully created
        return self::response(true, 200, 'Data migration successfully generated.', ['filename' => $filename]);
    }

    /**
     * Run a migration from a file.
     *
     * Runs a migration from a file with the specified name.  The file should be a JSON file
     * in the root folder of the plugin.
     *
     * @param string $filename The name of the file to import without the .json extension.  Default is 'migration'
     * 
     * @return Response_Handler The response of the run migration from file operation from the self::response() method.
     */

    public static function run_migration_from_file(string $filename = 'migration'): Response_Handler
    {
        // Get file data
        $get_file_data = @file_get_contents(WP_CUSTOM_API_FOLDER_PATH . strtolower($filename) . '.json');

        // If file doesn't exist, return error
        if (!$get_file_data) return self::response(false, 500, 'Error importing data from file.  Make sure that ' . WP_CUSTOM_API_FOLDER_PATH . $filename . '.json' . ' exists in the root folder of the plugin.');

        // Convert JSON into an associative array
        $assoc_array = json_decode($get_file_data, true);

        // If file is not a valid JSON array, return error
        if (!is_array($assoc_array)) return self::response(false, 500, 'Data from ' . WP_CUSTOM_API_FOLDER_PATH . $filename . '.json' . ' is not valid for migrating. Check that the file is a valid JSON file.');
        
        // Import data and return result
        $import_data = self::import_tables_data($assoc_array);
        return $import_data;
    }
}
