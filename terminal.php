<?php

/**
 * Define base file path
 */

define("BASE_PATH", strtolower(str_replace('/', '', __DIR__)));

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
    'route' => 'routes',
]);

/**
 * Define folder paths
 */

define("FOLDER_PATH", [
    'controller' => BASE_PATH . '/' . FOLDER_NAME['controller'] . '/',
    'model' => BASE_PATH . '/' . FOLDER_NAME['model'] . '/',
    'permission' => BASE_PATH . '/' . FOLDER_NAME['permission'] . '/',
    'route' => BASE_PATH . '/' . FOLDER_NAME['route'] . '/',
]);

/**
 * Collect command line props
 */

$argv = $_SERVER['argv'];

$cr = explode(":", $argv[2]);

define("COMMAND", strtolower($cr[0]));
define("RESOURCE", strtolower($cr[1]));
define("NAME", ucfirst(strtolower($argv[3])));

/**
 * Process create commands from switch below Create class
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
            echo ucfirst($type) . " of the same name already exists.";
            exit;
        }

        // Generate file content

        $file_content = "<?php\n\ndeclare(strict_types=1);\n\nnamespace " . APP_NAME . "\\" . ucfirst(FOLDER_NAME[$type]) . ";\n";

        if (!empty($dependencies)) $file_content .= "\n";

        foreach ($dependencies as $dependency) {
            $file_content .= "use " . $dependency . ";\n";
        }

        if ($type !== 'route') {

            $file_content .= "\nclass " . NAME;

            if ($type === 'model') $file_content .= " implements Model";

            $file_content .= "\n{\n";

            $file_content .= $class_content;

            $file_content .= "\n}";
        }

        // Create file and check that it was successfullyc created

        $create_file = file_put_contents(FOLDER_PATH[$type] . strtolower(NAME) . '.php', $file_content);
        if (!$create_file) {
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
            "WP_Custom_API\Core\Model",
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
        ];
        self::create_file("route", $dependencies);
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
 * Commands to execute.  Create commands utilize the Create class above
 */

switch (COMMAND) {

        // Create methods

    case 'create':
        switch (RESOURCE) {
            case 'controller':
                Create::controller();
                exit;
            case 'model':
                Create::model();
                exit;
            case 'permission':
                Create::permission();
                exit;
            case 'route':
                Create::router();
                exit;
            case 'interface':
                Create::interface();
                echo "Interface files for " . NAME . " created successfully. \n";
                exit;
            default:
                echo "Invalid create request.  Create methods are `controller`, `model`, `permission`, `route`, and `interface`.";
                exit;
        }
        break;

        // Delete methods

    case 'delete':
        switch (RESOURCE) {
            case 'controller':
                echo 'delete controller with the name of ' . NAME . '.';
                break;
            case 'route':
                echo 'delete route with the name of ' . NAME . '.';
                break;
            case 'model':
                echo 'delete model with the name of ' . NAME . '.';
                break;
            case 'permission':
                echo 'delete permission with the name of ' . NAME . '.';
                break;
            default:
                break;
        }

    default:
        break;
}
