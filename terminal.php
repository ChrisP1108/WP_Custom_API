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
define("NAME", strtolower($argv[3]));

/**
 * Process create commands from switch below Create class
 */

class Create
{

    /**
     * Creates controller.
     */

    public static function controller()
    {
        if (file_exists(FOLDER_PATH['controller'] . NAME . ".php")) {
            echo "Controller of the same name already exists.";
            exit;
        }
        $controller_file_content ="<?php \n\ndeclare(strict_types=1);\n\nnamespace ".APP_NAME."\\".ucfirst(FOLDER_NAME['controller']) .";\n\nuse \WP_REST_Response as Response;\nuse WP_Custom_API\Core\Database;\nuse WP_Custom_API\Models\Sample_Model;\nuse WP_Custom_API\Core\Auth_Token;\n\nclass " . ucfirst(NAME) . "\n{\n\n}";
        $controller_created = file_put_contents(FOLDER_PATH['controller'] . NAME . '.php', $controller_file_content);
        if ($controller_created) {
        } else {
            echo "Error creating controller file " . NAME . ".php";
            exit;
        }
    }

    /**
     * Creates interface.  Creates a controller, router, model, and permission file utilizing the other methods
     */

    public static function interface()
    {
        self::controller();

        // Create router file
        if (file_exists(FOLDER_PATH['route'] . NAME . ".php")) {
            return "Route of the same name already exists.";
        }
        $route_file_contents =
            "<?php \n
            use WP_Custom_API\Core\Router;\n
            use WP_Custom_API\Controllers\Sample_Controller;\n\n";
        $route_handler = fopen(FOLDER_PATH['route'], 'w');
        if ($route_handler) {
        }
    }
}

/**
 * Commands to execute.  Create commands utilize the Create class above
 */

switch (COMMAND) {

        // Create methods

    case 'create':
        switch (RESOURCE) {
            case 'interface':
                echo 'create controller with the name of ' . NAME . '.';
                break;
            case 'controller':
                if (Create::controller()) echo NAME . " controller created successfully.";
            case 'route':
                echo 'create controller with the name of ' . NAME . '.';
                break;
            case 'model':
                echo 'create model with the name of ' . NAME . '.';
                break;
            case 'permission':
                echo 'create permission with the name of ' . NAME . '.';
                break;
            default:
                break;
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
