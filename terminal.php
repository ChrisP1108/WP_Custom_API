<?php

/**
 * Define base file path
 */

define("BASE_PATH", rtrim(__DIR__, '/') . '/');

/**
 * Define app name
 */

define("APP_NAME", "WP_Custom_API");

/**
 * Define folder names
 */

define("FOLDER_NAME", [
    'controller' => 'controllers',
    'model' => 'models',
    'permission' => 'permissions',
    'router' => 'routes',
]);

/**
 * Define folder paths
 */

define("FOLDER_PATH", [
    'controller' => BASE_PATH . '/' . FOLDER_NAME['controller'] . '/',
    'model' => BASE_PATH . '/' . FOLDER_NAME['model'] . '/',
    'permission' => BASE_PATH . '/' . FOLDER_NAME['permission'] . '/',
    'router' => BASE_PATH . '/' . FOLDER_NAME['router'] . '/',
]);

/** 
 * Establish valid Command names "create" and "delete"
 */

define('COMMAND_CREATE', 'create');
define('COMMAND_DELETE', 'delete');

/**
 * Collect command line props
 */

$argv = [];

if (is_array($_SERVER['argv'])) {
    foreach($_SERVER['argv'] as $arg) {
        $argv[] = filter_var($arg, FILTER_SANITIZE_URL);
    }
}

// Check that all arguments exist

if (!isset($argv[2], $argv[3])) {
    echo "Error: Missing required arguments.\n";
    exit;
}

$cr = explode(":", $argv[2]);

// Check that command:resource arguments exist

if (count($cr) < 2) {
    echo "Error: Invalid command format. Expected 'command:resource'.\n";
    exit;
}

define("COMMAND", strtolower($cr[0]));
define("RESOURCE", strtolower($cr[1]));
define("NAME", ucfirst(strtolower($argv[3])));

/**
 * Process create commands
 */

class Create
{

    /**
     * Generates file content based upon parameters and global variable values and then creates file.  Makes sure file of same name doesn't already exist and makes sure file was created successfully.
     */

    private static function create_file($type, $dependencies, $class_content = '')
    {

        // Check that file of the same name doesn't already exist

        if (file_exists(FOLDER_PATH[$type] . strtolower(NAME) . ".php")) {
            echo ucfirst($type) . " " . strtolower(NAME) . ".php file already exists.\n";
            return;
        }

        // Generate file content

        $file_content = "<?php\n\ndeclare(strict_types=1);\n\nnamespace " . APP_NAME . "\\" . ucfirst(FOLDER_NAME[$type]) . ";\n";

        if (!empty($dependencies)) $file_content .= "\n";

        foreach ($dependencies as $dependency) {
            $file_content .= "use " . $dependency . ";\n";
        }

        if ($type !== 'router') {

            $file_content .= "\nclass " . NAME;

            if ($type === 'model') $file_content .= " implements Model";

            $file_content .= "\n{\n";

            $file_content .= $class_content;

            $file_content .= "\n}";
        }

        // Check that folder exists for writing file

        if (!is_dir(FOLDER_PATH[$type])) {
            echo "Directory " . FOLDER_PATH[$type] . " does not exist.\n";
            exit;
        }

        // Create file and check that it was successfullyc created

        $create_file = file_put_contents(FOLDER_PATH[$type] . strtolower(NAME) . '.php', $file_content);

        if ($create_file === false) {
            echo "Error creating " . ucfirst($type) . " file " . strtolower(NAME) . ".php";
            exit;
        }
        echo ucfirst($type) . " " . strtolower(NAME) . ".php file successfully created.\n";
    }

    /**
     * Creates controller file.
     */

    public static function controller()
    {
        $dependencies = [
            "\WP_REST_Response as Response",
            "WP_Custom_API\Core\Database",
            "WP_Custom_API\Core\Auth_Token",
            "WP_Custom_API\Models\\" . NAME . " as Model"
        ];
        self::create_file("controller", $dependencies);
    }

    /**
     * Creates model file.
     */

    public static function model()
    {
        $dependencies = [
            "WP_Custom_API\Core\Model"
        ];
        $class_content = "    public static function table_name():string {\n        return '" . strtolower(NAME) . "';\n    }\n    public static function table_schema(): array {\n        return\n            [\n\n            ];\n    }\n    public static function run_migration(): bool {\n        return false;\n    }";
        self::create_file("model", $dependencies, $class_content);
    }

    /**
     * Creates permission file.
     */

    public static function permission()
    {
        $dependencies = [];
        self::create_file("permission", $dependencies);
    }

    /**
     * Creates router file.
     */

    public static function router()
    {
        $dependencies = [
            "WP_Custom_API\Core\Router",
            "WP_Custom_API\Controllers\\" . NAME . " as Controller",
            "WP_Custom_API\Permissions\\" . NAME . " as Permission"
        ];
        self::create_file("router", $dependencies);
    }

    /**
     * Creates interface.  Creates a controller, router, model, and permission file utilizing the other methods
     */

    public static function interface()
    {
        self::controller();
        self::model();
        self::permission();
        self::router();
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
        $file_path = FOLDER_PATH[$type] . strtolower(NAME) . '.php';
        if (file_exists($file_path)) {
            unlink($file_path);
            echo ucfirst($type) . " " . strtolower(NAME) . ".php file successfully deleted.\n";
        } else echo ucfirst($type) . " " . strtolower(NAME) . ".php file does not exist and could not be deleted.\n";
    }

    public static function interface()
    {
        self::delete_file("controller");
        self::delete_file("model");
        self::delete_file("permission");
        self::delete_file("router");
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

else if (COMMAND === COMMAND_DELETE) {
    if (RESOURCE === 'interface') {
        Delete::interface();
        exit;
    } else if (FOLDER_PATH[RESOURCE] ?? null) {
        Delete::delete_file(RESOURCE);
        exit;
    } else {
        echo "Delete resource(s) method of `" . RESOURCE . "` does not exist and could not be executed.";
        exit;
    }
}

// If command is not `create` or `delete` show error message

echo "`" . COMMAND . "` is not a valid command and could not be executed.\n";
exit;
