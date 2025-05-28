=== WP Custom API ===
Contributors: Chris Paschall
Tags: api, custom api, REST API, WordPress, plugin, authentication, database, security
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.1
License: GPLv2 or later
Stable tag: 1.0.0
GitHub Plugin URI: https://github.com/ChrisP1108/WP_Custom_API.git

== Description ==

WP Custom API is a powerful WordPress plugin designed to simplify custom REST API development with a structured, secure, and extensible framework. It provides a complete MVC-like architecture for building custom API endpoints with proper authentication, parameter validation, and database operations.

### Key Features:
- **MVC-like Architecture**: Clean separation of concerns with Routes, Controllers, Models, and Permissions
- **Robust Authentication**: Secure token-based authentication with encryption and protection against replay attacks
- **Parameter Validation**: Type checking, sanitization, and validation for all API inputs
- **Database Abstraction**: Simple yet powerful database operations with built-in pagination
- **Standardized Responses**: Consistent API responses with proper HTTP status codes
- **Error Handling**: Centralized error logging and display
- **CLI Commands**: Generate API resources quickly with terminal commands

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. **IMPORTANT**: Update the SECRET_KEY constant in the Config.php file for security.
4. Use the CLI commands to create and manage your API resources.

== Framework Architecture ==

WP Custom API follows a clean MVC-like architecture:

### Routes
Routes define the API endpoints and connect them to controllers and permissions. They determine what URLs are available in your API and what HTTP methods they support.

### Controllers
Controllers handle the business logic of your API. They process requests, interact with models, and format responses.

### Models
Models manage database operations and define table schemas. They provide methods for CRUD operations on your data.

### Permissions
Permissions handle authentication and authorization. They determine who can access your API endpoints.

== Getting Started ==

### 1. Creating Your First API Endpoint

To create a complete API resource (controller, model, permission, and routes):

php terminal.php create:interface example

This will create:
- `api/example/controller.php`
- `api/example/model.php`
- `api/example/permission.php`
- `api/example/routes.php`

### 2. Understanding the Generated Files

#### Routes File
```php
// api/example/routes.php
Router::get("/", [Controller::class, "ind`ex"], [Permission::class, "public"]);
````

This creates a GET endpoint at `/wp-json/custom-api/v1/example` that uses the Controller's "index" method and is publicly accessible.

#### Controller File

```php
// api/example/controller.php
public static function index(Request $request, $permission_params): Response 
{
    return self::response(null, 200, 'Example route works');
}
```

The controller handles the request and returns a response.

#### Model File

```php
// api/example/model.php
public static function table_name(): string 
{
    return 'example';
}

public static function schema(): array 
{
    return [
        'name' => [
            'query'    => 'VARCHAR(50)',
            'type'     => 'text',
            'required' => true,
            'limit'    => 50
        ],
        // other fields...
    ];
}

public static function create_table(): bool 
{
    return false; // Set to true to create the table on plugin initialization
}
```

The model defines the database table structure.

#### Permission File

```php
// api/example/permission.php
public static function authorized(Request $request): bool|array
{
    return self::token_validate(self::TOKEN_NAME)->ok;
}
```

The permission class determines who can access the endpoint.

### 3. Customizing Your API

#### Adding More Endpoints

Edit your routes file to add more endpoints:

```php
// api/example/routes.php
Router::get("/", [Controller::class, "index"], [Permission::class, "public"]);
Router::get("/{id}", [Controller::class, "get_by_id"], [Permission::class, "authorized"]);
Router::post("/", [Controller::class, "create"], [Permission::class, "authorized"]);
Router::put("/{id}", [Controller::class, "update"], [Permission::class, "authorized"]);
Router::delete("/{id}", [Controller::class, "delete"], [Permission::class, "authorized"]);
```

#### Implementing Controller Methods

Add corresponding methods to your controller:

```php
// api/example/controller.php
public static function get_by_id(Request $request, $permission_params): Response 
{
    $id = $request->get_param('id');
    $data = Model::get_rows_data('id', $id, false);
    return self::response($data, 200);
}

public static function create(Request $request, $permission_params): Response 
{
    // Validate and sanitize input
    $handler = self::request_handler($request, Model::schema());
    if (!$handler->ok) return self::response($handler, $handler->status_code);
    
    // Insert data
    $result = Model::insert_row($handler->request_data);
    return self::response($result, $result->ok ? 201 : 500);
}

// Add update and delete methods similarly
```

#### Working with Database

Use the model to interact with the database:

```php
// Get all records
$all_data = Model::get_table_data();

// Get specific records
$filtered_data = Model::get_rows_data('status', 'active', true);

// Insert a record
$insert_result = Model::insert_row([
    'name' => 'Example Name',
    'email' => 'example@example.com'
]);

// Update a record
$update_result = Model::update_row(1, [
    'name' => 'Updated Name'
]);

// Delete a record
$delete_result = Model::delete_row(1);
```

### 4. Authentication

#### Setting Up Authentication

1. Update your permission class:

```php
// api/example/permission.php
public const TOKEN_NAME = 'example_token';

public static function authorized(Request $request): bool|array
{
    return self::token_validate(self::TOKEN_NAME)->ok;
}
```

2. Create a login endpoint:

```php
// api/example/routes.php
Router::post("/login", [Controller::class, "login"], [Permission::class, "public"]);
```

3. Implement the login method:

```php
// api/example/controller.php
public static function login(Request $request, $permission_params): Response 
{
    // Validate credentials (example)
    $username = $request->get_param('username');
    $password = $request->get_param('password');
    
    // Check credentials against your user storage
    // ...
    
    // If valid, generate token
    $user_id = 123; // The authenticated user's ID
    $token_result = Permission::token_generate($user_id, Permission::TOKEN_NAME);
    
    return self::response($token_result, $token_result->ok ? 200 : 401);
}
```

#### Using Authentication

The token is automatically stored as a cookie and validated on subsequent requests.

### 5. Parameter Validation

Use the request_handler method to validate and sanitize input:

```php
// api/example/controller.php
public static function create(Request $request, $permission_params): Response 
{
    $schema = [
        'name' => [
            'type' => 'text',
            'required' => true,
            'limit' => 50
        ],
        'email' => [
            'type' => 'email',
            'required' => true,
            'limit' => 80
        ],
        'active' => [
            'type' => 'bool',
            'required' => false
        ]
    ];
    
    $handler = self::request_handler($request, $schema);
    
    if (!$handler->ok) {
        // Handle validation errors
        return self::response($handler, $handler->status_code);
    }
    
    // Process validated data
    $data = $handler->request_data;
    // ...
}
```

\== Advanced Usage ==

### Custom Database Queries

While the Model provides standard CRUD operations, you can use the WordPress `$wpdb` object for more complex queries:

```php
global $wpdb;
$table_name = Database::get_table_full_name('example');
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_name WHERE created > %s",
    $date
), ARRAY_A);
```

### Pagination

The framework includes built-in pagination:

```php
// In your controller
public static function list(Request $request, $permission_params): Response 
{
    // Get pagination parameters
    $pagination = self::pagination_params();
    
    // Get data from model (pagination is handled internally)
    $result = Model::get_table_data();
    
    // Set pagination headers
    if ($result->ok) {
        $data = $result->data;
        $total_rows = count($data);
        $total_pages = ceil($total_rows / $pagination['per_page']);
        
        self::pagination_headers(
            $total_rows,
            $total_pages,
            $pagination['per_page'],
            $pagination['page']
        );
    }
    
    return self::response($result, $result->ok ? 200 : 500);
}
```

### Error Handling

Use the Error_Generator to log errors:

```php
use WP_Custom_API\Includes\Error_Generator;

// Log an error
Error_Generator::generate('Error Code', 'Detailed error message');
```

### Custom Response Formatting

Customize your API responses:

```php
// Standard response
return self::response($data, 200, 'Success message');

// Custom response with specific data structure
return self::response([
    'items' => $data,
    'meta' => [
        'total' => $count,
        'page' => $page
    ]
], 200, 'Data retrieved successfully');
```

\== CLI Commands ==

### Creating Resources

- __Create a controller__:

  ```javascript
  php terminal.php create:controller example
  ```

- __Create a model__:

  ```javascript
  php terminal.php create:model example
  ```

- __Create a permission__:

  ```javascript
  php terminal.php create:permission example
  ```

- __Create routes__:

  ```javascript
  php terminal.php create:routes example
  ```

- __Create a complete interface__ (controller, model, permission, and routes):

  ```javascript
  php terminal.php create:interface example
  ```

### Deleting Resources

- __Delete a controller__:

  ```javascript
  php terminal.php delete:controller example
  ```

- __Delete a model__:

  ```javascript
  php terminal.php delete:model example
  ```

- __Delete a permission__:

  ```javascript
  php terminal.php delete:permission example
  ```

- __Delete routes__:

  ```javascript
  php terminal.php delete:routes example
  ```

- __Delete a complete interface__:

  ```javascript
  php terminal.php delete:interface example
  ```

### Nested Resources

You can create nested resources by using slashes in the resource name:

```javascript
php terminal.php create:interface user/profile
```

This will create resources in the `api/user/profile` directory.

\== Best Practices ==

1. __Security__:

   - Always update the SECRET_KEY in config.php
   - Use HTTPS for production environments
   - Validate and sanitize all user input

2. __Performance__:

   - Use pagination for large datasets
   - Consider caching for frequently accessed data
   - Optimize database queries

3. __Organization__:

   - Group related endpoints in the same resource
   - Use meaningful names for your resources
   - Document your API endpoints

4. __Error Handling__:

   - Provide meaningful error messages
   - Use appropriate HTTP status codes
   - Log errors for debugging

5. __Testing__:

   - Test your API endpoints with tools like Postman
   - Implement unit tests for your controllers and models
   - Test edge cases and error conditions

\== Configuration ==

The `config.php` file contains important configuration settings:

```php
// Base path for all API endpoints
public const BASE_API_ROUTE = "custom-api/v1";

// Prefix for database tables and other identifiers
public const PREFIX = "custom_api_";

// Files to autoload when the plugin initializes
public const FILES_TO_AUTOLOAD = ['model', 'routes'];

// Secret key for token encryption (CHANGE THIS!)
public const SECRET_KEY = 'your-secret-key';

// Token expiration time in seconds (default: 7 days)
public const TOKEN_EXPIRATION = 604800;

// Token prefix for cookies and transients
public const AUTH_TOKEN_PREFIX = self::PREFIX . 'auth_token_';

// Password hashing cost (higher is more secure but slower)
public const PASSWORD_HASH_ROUNDS = 12;

// Whether tokens should only be sent over HTTPS
public const TOKEN_OVER_HTTPS_ONLY = true;

// Whether token cookies should be HTTP-only (inaccessible to JavaScript)
public const TOKEN_COOKIE_HTTP_ONLY = true;

\== Frequently Asked Questions ==

\= How do I create a new API endpoint? = Use the CLI command `php terminal.php create:interface your-resource-name` to create all necessary files, then edit them to implement your specific functionality.

\= How do I secure my API endpoints? = Use the Permission class to implement authentication. The framework includes token-based authentication that you can use by implementing the `authorized` method in your Permission class.

\= How do I validate user input? = Use the `request_handler` method in your controller with a schema that defines the expected types and constraints for each parameter.

\= How do I work with the database? = Use the Model class methods like `get_table_data()`, `get_rows_data()`, `insert_row()`, `update_row()`, and `delete_row()` to interact with your database tables.

\= How do I handle pagination? = The framework includes built-in pagination. Use the `pagination_params()` method to get pagination parameters and `pagination_headers()` to set pagination headers in your response.

\= How do I customize error messages? = Use the `response()` method in your controller to return custom error messages with appropriate HTTP status codes.

\= Can I use this with existing WordPress plugins? = Yes, this plugin is designed to work alongside other WordPress plugins without conflicts.

\== Changelog ==

\= 1.0.0 =

- Initial release with support for creating and managing custom API resources via the CLI.
- Includes classes for managing API controllers, models, permissions, and routes.
- Adds functionality for creating and deleting complete API interfaces.
- Added Database, Model Interface, Password, and Auth Token classes to enhance API security and management.

\== License ==

This plugin is licensed under GPLv2 or later.