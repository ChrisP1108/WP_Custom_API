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

WP Custom API is a lightweight framework for building custom WordPress REST API endpoints with a predictable file structure. It gives each API resource its own `routes.php`, `controller.php`, `model.php`, `permission.php`, and `utils.php`, then loads only the resource that matches the current request.

Features include:

- Request-scoped route loading for improved organization and reduced overhead
- Nested API resources such as `api/blog/comments`
- Route placeholders like `/{id}` and `/{slug}`
- Controller helpers for validation, sanitization, pagination, and standardized responses
- Model helpers for table creation and CRUD operations
- Permission helpers for public routes, token authentication, password hashing, and session utilities
- Extension points through `hooks.php` and WordPress actions/filters
- CLI scaffolding for creating and deleting API resources

Base API namespace:

`/wp-json/custom-api/v1`

Included example route:

`GET /wp-json/custom-api/v1/sample`

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin in the WordPress admin.
3. Update the `SECRET_KEY` and `DB_SESSION_SECRET_KEY` values in `config.php` before using authentication. These placeholders should be replaced with strong random values, ideally loaded from environment variables for better security.
4. Review the sample resource in `api/sample/` or generate a new one with `terminal.php`.

== How It Works ==

= Resource Structure =

Each resource lives in its own folder inside `api/`.

Example:

- `api/sample/routes.php`
- `api/sample/controller.php`
- `api/sample/model.php`
- `api/sample/permission.php`
- `api/sample/utils.php`

Nested resources are supported:

- `api/blog/comments/routes.php`
- `api/blog/comments/controller.php`

The plugin inspects the current request path, finds the deepest matching folder inside `api/`, autoloads that resource's files, then registers only the routes that match the current HTTP method and URL pattern.

= Routing =

Routes are defined with the `Router` class.

```php
use WP_Custom_API\Includes\Router;
use WP_Custom_API\Api\Example\Controller;
use WP_Custom_API\Api\Example\Permission;

Router::get("/", [Permission::class, "public"], [Controller::class, "index"]);
Router::get("/{id}", [Permission::class, "public"], [Controller::class, "show"]);
Router::post("/", [Permission::class, "authorized"], [Controller::class, "store"]);
Router::match(["PUT", "PATCH"], "/{id}", [Permission::class, "authorized"], [Controller::class, "update"]);
Router::delete("/{id}", [Permission::class, "authorized"], [Controller::class, "destroy"]);
```

Route placeholders wrapped in braces are converted into WordPress REST API named parameters.

= Controllers =

Controllers extend `Controller_Interface` and receive:

- `WP_REST_Request $request`
- `mixed $permission_params`

Example:

```php
public static function index(Request $request, mixed $permission_params): Response
{
    return self::response(null, 200, 'example route works');
}
```

Useful controller helpers include:

- `request_handler($request, $schema)` for sanitization and validation
- `compile_request_data(...)` to extract clean values
- `response($data, $status_code, $message)` for standardized responses
- `pagination_params()` and `pagination_headers(...)` for paginated endpoints

= Models =

Models extend `Model_Interface` and define the database schema for the resource.

```php
public static function schema(): array
{
    return [
        'name' => [
            'query' => 'VARCHAR(50)',
            'type' => 'text',
            'required' => true,
            'minimum' => 2,
            'maximum' => 50,
        ],
    ];
}

public static function create_table(): bool
{
    return false;
}
```

Available helpers include:

- `table_exists()`
- `get_table_data()`
- `get_rows_data($column, $value, $multiple = true)`
- `insert_row($data)`
- `update_row($id, $data)`
- `delete_row($id)`
- `execute_query($query)`

Tables are created automatically on matching requests when `create_table()` returns `true`.

= Permissions And Authentication =

Permissions extend `Permission_Interface`.

Public route:

```php
public static function public_route(Request $request): bool
{
    return self::public();
}
```

Token-protected route:

```php
public static function authorized(Request $request): bool|array
{
    return self::token_authenticate();
}
```

Available helpers include:

- `public()`
- `token_authenticate()`
- `token_generate($user_id, $session_data_additionals = [], $expiration = Config::TOKEN_EXPIRATION)`
- `token_validate()`
- `token_remove()`
- `password_hash($string)`
- `password_verify($entered_password, $hashed_password)`
- custom session helpers through `session_custom_*`

Authentication tokens are namespaced to the current API resource and use cookies plus replay-protection data stored in the database session table.

= Hooks And Extension Points =

Use `hooks.php` for plugin-specific code that should run before or after initialization.

```php
final class Hooks
{
    public static function before_init(): void
    {
        // Custom logic before plugin initialization
    }

    public static function after_init(): void
    {
        // Custom logic after plugin initialization
    }
}
```

Notable WordPress integration points include:

- `wp_custom_api_files_to_autoload`
- `wp_custom_api_route_filter`
- `wp_custom_api_files_autoloaded`
- `wp_custom_api_routes_registered`
- `wp_custom_api_loaded`

== Quick Start ==

1. Create a resource:

`php terminal.php create:interface example`

2. Edit `api/example/routes.php`:

```php
Router::get("/", [Permission::class, "public"], [Controller::class, "index"]);
Router::get("/{id}", [Permission::class, "public"], [Controller::class, "show"]);
```

3. Update `api/example/controller.php`:

```php
public static function show(Request $request, mixed $permission_params): Response
{
    $id = $request->get_param('id');
    $result = Model::get_rows_data('id', $id, false);

    return self::response($result->data, $result->status_code, $result->message);
}
```

4. If you need a database table, update `api/example/model.php` and return `true` from `create_table()`.

== CLI Commands ==

= Create =

- `php terminal.php create:interface example`
- `php terminal.php create:controller example`
- `php terminal.php create:model example`
- `php terminal.php create:permission example`
- `php terminal.php create:routes example`
- `php terminal.php create:utils example`

= Delete =

- `php terminal.php delete:interface example`
- `php terminal.php delete:controller example`
- `php terminal.php delete:model example`
- `php terminal.php delete:permission example`
- `php terminal.php delete:routes example`
- `php terminal.php delete:utils example`

= Nested Resource Example =

`php terminal.php create:interface blog/comments`

This generates:

- `api/blog/comments/controller.php`
- `api/blog/comments/model.php`
- `api/blog/comments/permission.php`
- `api/blog/comments/routes.php`
- `api/blog/comments/utils.php`

== Configuration ==

Important settings in `config.php`:

- `BASE_API_ROUTE` sets the WordPress REST namespace
- `PREFIX` sets the plugin naming prefix
- `FILES_TO_AUTOLOAD` controls which API files are loaded automatically
- `SECRET_KEY` and `DB_SESSION_SECRET_KEY` secure auth and session data and should always be replaced with strong random values before use
- `TOKEN_EXPIRATION` sets default token lifetime
- `TOKEN_OVER_HTTPS_ONLY`, `TOKEN_COOKIE_HTTP_ONLY`, and `TOKEN_COOKIE_SAME_SITE` control cookie behavior
- `DATABASE_REFRESH_INTERVAL` controls how long table creation state is cached
- `DEBUG_MESSAGE_MODE` enables more detailed API error output

== Frequently Asked Questions ==

= How do I create a new endpoint? =

Run `php terminal.php create:interface your-resource`, then edit the generated files inside `api/your-resource/`.

= Can I use nested routes? =

Yes. Resource folders can be nested, such as `api/store/orders`, and route placeholders such as `/{id}` are supported inside `routes.php`.

= How do I validate request data? =

Use `Controller_Interface::request_handler()` with your model schema or a custom schema array.

= How do I protect an endpoint? =

Create a permission method in `permission.php` and use it in the router declaration before the controller callback.

= Where should custom shared logic go? =

Place reusable resource-specific helpers in `utils.php`.

== Changelog ==

= 1.0.0 =

- Initial public release
- MVC-like API resource structure with controllers, models, permissions, routes, and utilities
- Nested API resource support
- Request-scoped route matching and loading
- Database, session, password, and token helpers
- CLI scaffolding for resource creation and deletion

== License ==

GPLv2 or later
