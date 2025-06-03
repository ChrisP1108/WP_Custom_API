<?php

/**
 * Define base file path
 */

define("BASE_PATH", rtrim(__DIR__, '/') . '/');

/** 
 * Declare ABSPATH for wordpress environment
 */

define('ABSPATH', dirname(__DIR__, 3) . '/');

/**
 * Import Config class Base API Route
 */

require __DIR__ . '/config.php';

define("BASE_API_ROUTE", WP_Custom_API\Config::BASE_API_ROUTE);

/**
 * Import Database class for exporting and importing data
 */

require_once __DIR__ . '/includes/database.php';

use WP_Custom_API\Includes\Database;

/**
 * Define app name
 */

define("APP_NAME", "WP_Custom_API");

/** 
 * Establish valid Command names "create", "delete", "export", "import"
 */

define('COMMAND_CREATE', 'create');
define('COMMAND_DELETE', 'delete');
define('COMMAND_EXPORT', 'export');
define('COMMAND_IMPORT', 'import');

/**
 * Collect command line props
 */

$argv = [];

if (is_array($_SERVER['argv'])) {
    foreach ($_SERVER['argv'] as $arg) {
        $argv[] = filter_var($arg, FILTER_SANITIZE_URL);
    }
}

/*
 * Check that all arguments exist\
 */

if (!isset($argv[1], $argv[2])) {
    echo "Error: Missing required arguments.\n";
    exit;
}

/*
 * Split command and resource
 */

$cr = explode(":", $argv[1]);

/*
 * Check that command:resource arguments exist
 */

if (count($cr) < 2) {
    echo "Error: Invalid command format. Expected 'command:resource'.\n";
    exit;
}

/* 
 *Split any slashes in resource name for folder nesting
 */

$namings = explode("/", $argv[2]);

/*
 * Loop through naming split at "/" and make string lowercase with first name uppercase
 */

$naming_formatted = array_map(function ($str) {
    return ucfirst(strtolower($str));
}, $namings);

/*
 * Combine naming split at "/"
 */

$naming_string = implode("/", $naming_formatted);

/*
 * Check that naming string only contains alphanumeric characters, underscores, and forward slashes
 */

if (!preg_match('/^[a-zA-Z_\/]+$/', $naming_string)) {
    echo "Error: Invalid resource name format.  The resource name can only contain alphanumeric characters, underscores, and forward slashes.\n";
    exit;
}

/*
 * Define global variables
 */

define("COMMAND", strtolower($cr[0]));
define("RESOURCE", strtolower($cr[1]));
define("PATH", implode("/", $naming_formatted));
define("NAMESPACE_PATH", implode("\\", $naming_formatted));

/**
 * Establish file path for export and import commands of json file
 */

define('DATA_FILE_PATH', strtolower(PATH) . ".json");


/**
 * Process create commands
 */

class Create
{

    /**
     * Generates file content based upon parameters and global variable values and then creates file.  Makes sure file of same name doesn't already exist and makes sure file was created successfully.
     */

    private static function create_file($type, $dependencies, $class_content = '', $additional_content = '')
    {

        // Check that file of the same name doesn't already exist

        if (file_exists("api/" . strtolower(PATH) . "/" . $type . ".php")) {
            echo ucfirst($type) . ".php file in folder " . strtolower(PATH) . " already exists.\n";
            return;
        }

        // Generate file content

        $file_content = "<?php\n\ndeclare(strict_types=1);\n\nnamespace " . APP_NAME . "\\Api\\" . ucfirst(NAMESPACE_PATH) . ";\n";

        if (!empty($dependencies)) $file_content .= "\n";

        foreach ($dependencies as $dependency) {
            $file_content .= "use " . $dependency . ";\n";
        }

        // Add code snippet to prevent direct access to files outside Wordpress environment

        $file_content .= "\n/**\n* Prevent direct access from sources other than the Wordpress environment\n*/\n\nif (!defined('ABSPATH')) exit;\n";

        if ($type !== 'routes') {

            $file_content .= "\nfinal class " . ucfirst($type);

            if ($type === 'model') $file_content .= " extends Model_Interface";

            if ($type === 'permission') $file_content .= " extends Permission_Interface";

            if ($type === 'controller') $file_content .= " extends Controller_Interface";

            $file_content .= "\n{\n";

            $file_content .= $class_content;

            $file_content .= "\n}";
        }

        // Add additional content if it exists

        if (!empty($additional_content)) $file_content .=  "\n" . $additional_content;

        // Check that folder exists for writing file.  Create folder if it does not

        if (!is_dir("api/" . strtolower(PATH))) {
            if (mkdir("api/" . strtolower(PATH), 0755, true)) {
                echo "Directory `" . strtolower(PATH) . "` created inside api folder. ";
            } else {
                echo "Error creating directory " . strtolower(PATH) . " inside api folder.";
            }
        }

        // Create file and check that it was successfully created

        $create_file = file_put_contents("api/" . strtolower(PATH) . "/" . $type . ".php", $file_content);

        if ($create_file === false) {
            echo "Error creating " . ucfirst($type) . " file " . strtolower(PATH) . ".php inside the " . strtolower(PATH) . " folder.\n";
            exit;
        }
        echo ucfirst($type) . " " . strtolower(PATH) . ".php file successfully created inside the " . strtolower(PATH) . " folder.\n";
    }

    /**
     * Creates controller file.
     */

    public static function controller()
    {
        $dependencies = [
            "WP_REST_Request as Request",
            "WP_REST_Response as Response",
            "WP_Custom_API\Includes\Controller_Interface",
            "WP_Custom_API\Api\\" . NAMESPACE_PATH . "\Model",
            "WP_Custom_API\Api\\" . NAMESPACE_PATH . "\Permission"
        ];
        $class_content = "    public static function index(Request \$request, \$permission_params): Response \n    {\n        return self::response(null, 200, '" . ucfirst(PATH) . " route works');\n    }";
        self::create_file("controller", $dependencies, $class_content);
    }

    /**
     * Creates model file.
     */

    public static function model()
    {
        $dependencies = [
            "WP_Custom_API\Includes\Model_Interface"
        ];
        $class_content = $class_content = <<<'PHP'
                public static function table_name(): string 
                {
                    return 'sample';
                }

                public static function schema(): array 
                {
                    // Below is a sample schema, feel free to update/delete as needed.

                    return
                        [
                            'name' => 
                                [
                                    'query'    => 'VARCHAR(50)',
                                    'type'     => 'text',
                                    'required' => true,
                                    'limit'    => 50
                                ],
                            'email' => 
                                [
                                    'query'    => 'VARCHAR(80)',
                                    'type'     => 'email',
                                    'required' => true,
                                    'limit'    => 80
                                ]
                        ]
                    ;
                }

                public static function create_table(): bool 
                {
                    return false;
                }
            PHP;
        self::create_file("model", $dependencies, $class_content);
    }

    /**
     * Creates permission file.
     */

    public static function permission()
    {
        $dependencies = [
            "WP_REST_Request as Request",
            "WP_Custom_API\Includes\Permission_Interface",
            "WP_Custom_API\Api\\" . NAMESPACE_PATH . "\Model"
        ];
        $class_content = "    public const TOKEN_NAME = '" . strtolower(str_replace('/', '_', PATH)) . "_token';\n\n    public static function authorized(Request \$request): bool|array\n    {\n        // Replace code in this method with logic for protecting a route from unauthorized access. \n\n        return self::token_validate(self::TOKEN_NAME)->ok;\n    }";
        self::create_file("permission", $dependencies, $class_content);
    }

    /**
     * Creates routes file.
     */

    public static function routes()
    {
        $dependencies = [
            "WP_Custom_API\Includes\Router",
            "WP_Custom_API\Api\\" . NAMESPACE_PATH . "\Controller",
            "WP_Custom_API\Api\\" . NAMESPACE_PATH . "\Permission"
        ];
        $additional_content = "/**\n* API Base Route - {url_origin}/wp-json/" . BASE_API_ROUTE . "/" . strtolower(PATH) . " \n*/\n\n/**\n* Sample GET route\n*/\n\nRouter::get(\"/\", [Controller::class, \"index\"], [Permission::class, \"public\"]);";
        self::create_file("routes", $dependencies, '', $additional_content);
    }

    /**
     * Creates interface.  Creates a controller, routes, model, and permission file utilizing the other methods
     */

    public static function interface()
    {
        self::controller();
        self::model();
        self::permission();
        self::routes();
    }
}

/**
 * Process Delete Commands
 */

class Delete
{
    /**
     * Deletes file by type
     */

    public static function delete_file($type)
    {
        $file_path = "api/" . strtolower(PATH) . "/" . $type . ".php";
        if (file_exists($file_path)) {
            unlink($file_path);
            echo ucfirst($type) . " " . strtolower(PATH) . ".php file successfully deleted inside the " . strtolower(PATH) . " folder.\n";
        } else echo ucfirst($type) . " " . strtolower(PATH) . ".php file does not exist inside the " . strtolower(PATH) . " folder and could not be deleted.\n";
    }

    public static function interface()
    {
        self::delete_file("controller");
        self::delete_file("model");
        self::delete_file("permission");
        self::delete_file("routes");
        rmdir("api/" . strtolower(PATH));
        echo "`". strtolower(PATH) . "` folder deleted inside api folder";
    }
}

/**
 * Commands to execute based upon COMMAND value.
 */

// Create commands

if (COMMAND === COMMAND_CREATE) {
    if (method_exists('Create', RESOURCE)) {
        call_user_func([Create::class, RESOURCE]);
        exit;
    } else {
        echo "Create resource(s) method of `" . RESOURCE . "` does not exist and could not be executed.";
        exit;
    }
}

// Delete commands

if (COMMAND === COMMAND_DELETE) {
    $resource_types = ['interface', 'controller', 'model', 'permission', 'routes'];
    if (RESOURCE === 'interface') {
        Delete::interface();
        exit;
    } else if (in_array(RESOURCE, $resource_types)) {
        Delete::delete_file(RESOURCE);
        exit;
    } else {
        echo "Delete resource(s) method of `" . RESOURCE . "` does not exist and could not be executed.";
        exit;
    }
}

// Export and Import Commmands

if (COMMAND === COMMAND_EXPORT || COMMAND === COMMAND_IMPORT && RESOURCE === 'data') {

    // Load wordpress environment
    $wp_load = dirname(__DIR__, 3) . '/wp-load.php';
    if (!file_Exists($wp_load)) {
        echo "Error loading Wordpress file wp-load.php from the Wordpress root directory";
        exit;
    }
    require_once $wp_load;

    global $wpdb;
    exit;

    // Perform export command
    if (COMMAND === COMMAND_EXPORT) {
        $get_table_data = Database::get_all_tables_data();
        if (!$get_table_data->ok) {
            echo "Error getting table data.  See list of errors below:\n";
            foreach ($get_table_data->data as $item) {
                if (!$item->ok) {
                    echo $item->message . "\n";
                }
            }
            exit;
        }
        if (empty($get_table_data->data)) {
            echo $get_table_data->message . "\n";
        }
        $file_content = json_encode($get_table_data->data);
        $create_file = file_put_contents(DATA_FILE_PATH, $file_content);
        if (!$create_file) {
            echo "Error creating export file " . DATA_FILE_PATH . "\n";
            exit;
        }
        echo "Export file " . DATA_FILE_PATH . " created successfully. \n";
        exit;
    }

    // Perform import command
    if (COMMAND === COMMAND_IMPORT) {
        $get_file_data = file_get_contents(DATA_FILE_PATH);
        if (!$get_file_data) {
            echo "Error importing data from file.  Make sure that " . DATA_FILE_PATH . " exists in the root folder of the plugin.";
            exit;
        }
        $assoc_array = json_decode($get_file_data, true);
        $import_data = Database::import_tables_data($assoc_array);
        $error_importing_data = false;
        if (!$import_data->ok) {
            echo $import_data->message . " See list below for error details.\n";
            foreach ($import_data->data as $table => $data) {
                if (!$table['table_created']) {
                    echo "An error occured when creating the table " . $table . ".";
                    $error_importing_data = true;
                }
                if (!$table['data_inserted']) {
                    echo "An error occured when inserting data into the table " . $table . ".";
                    $error_importing_data = true;
                }
            }
        }
        if (!$error_importing_data) {
            echo "One or more errors occured while importing data.\n";
            exit;
        }
        echo "Data import successful. \n";
        exit;
    }
}

// If command is not `create`, `delete`, `export`, or `import`, show error message

echo "`" . COMMAND . "` is not a valid command and could not be executed.\n";
exit;
