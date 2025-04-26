=== WP Custom API ===
Contributors: Chris Paschall
Tags: api, custom api, REST API, WordPress, plugin, authentication, database, security
Requires at least: PHP version 8.0
Tested up to: PHP 8.4
License: None
Github Repository: https://github.com/ChrisP1108/WP_Custom_API.git

== Description ==

WP Custom API is a WordPress plugin designed to make custom API development easier and more streamlined. It allows you to manage your API resources, including controllers, models, permissions, and routes, through simple CLI commands. In addition to the basic API management functionality, the plugin includes classes to handle database interactions, user authentication, token management, and password security, ensuring a complete and secure solution for building custom APIs in WordPress.

### Key Features:
- **Create Custom API Resources**: Automatically generate API controllers, models, permissions, and routes.
- **Database Interaction**: Provides an easy-to-use database class to interact with your WordPress database.
- **Authentication**: Implements an authentication system with token-based access to secure API endpoints.
- **Password Security**: Securely handle password hashes and ensure that passwords are stored securely.
- **CLI Commands**: Full integration with the WordPress CLI to create, manage, and delete API resources.
- **Extensibility**: Easily extend and customize the generated files to meet your project needs.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Optionally, use the CLI commands to create and manage your API resources.

== Usage ==

### CLI Commands

Once the plugin is installed and activated, you can use the following commands to manage your API resources in the root plugin folder:

- **Create a resource**:
    - `php terminal.php create:controller sample` - Creates an API controller file with the name of sample.
    - `php terminal.php create:model sample` - Creates an API model file with the name of sample.
    - `php terminal.php create:permission sample` - Creates an API permission file with the name of sample.
    - `php terminal.php create:routes sample` - Creates an API routes file with the name of sample.
    - `php terminal.php create:interface sample` - Generates a full interface, including controller, model, permission, and routes files with the names of sample.

- **Delete a resource**:
    - `php terminal.php delete:controller sample` - Deletes an API controller file with the name of sample.
    - `php terminal.php delete:model sample` - Deletes an API model file with the name of sample.
    - `php terminal.php delete:permission sample` - Deletes an API permission file with the name of sample.
    - `php terminal.php delete:routes sample` - Deletes an API routes file with the name of sample.
    - `php terminal.php delete:interface sample` - Deletes all API interface files (controller, model, permission, and routes) with the names of sample.

A sample interface with the name of `sample` has already been created for better illustration.  Feel free to delete this interface with the `wp custom-api delete:interface sample` command

**Note**: All commands should be executed from the command line in the root directory of your WordPress installation.

== Classes Overview ==

This plugin includes several core classes, each designed to handle specific aspects of custom API development:

### 1. **CLI Class**
   - **Purpose**: Defines the command-line interface for interacting with the plugin. It parses the provided arguments and runs appropriate commands to create or delete API resources.
   - **Main Methods**:
     - `create_file()`: Creates a file (controller, model, permission, or routes).
     - `controller()`: Creates a controller file for a resource.
     - `model()`: Creates a model file for a resource.
     - `permission()`: Creates a permission file for a resource.
     - `routes()`: Creates a routes file for a resource.
     - `interface()`: Creates all necessary files (controller, model, permission, routes) for a resource.

### 2. **Create Class**
   - **Purpose**: Handles the creation of various API components (controller, model, permission, routes) for custom API resources.
   - **Main Methods**:
     - `create_file()`: Generates PHP file content and creates the specified file.
     - `controller()`: Creates a controller file for a resource.
     - `model()`: Creates a model file for a resource.
     - `permission()`: Creates a permission file for a resource.
     - `routes()`: Creates a routes file for a resource.
     - `interface()`: Generates a complete API interface, including the controller, model, permission, and routes files.

### 3. **Delete Class**
   - **Purpose**: Manages the deletion of API resource files.
   - **Main Methods**:
     - `delete_file()`: Deletes a specific file (controller, model, permission, routes).
     - `interface()`: Deletes the entire interface (controller, model, permission, routes) and the folder containing the files.

### 4. **Database Class**
   - **Purpose**: Provides an interface for interacting with the WordPress database. It allows you to create, read, update, and delete data in custom tables created by the plugin.
   - **Main Methods**:
     - `create_table()`: Creates a new table in the WordPress database.
     - `insert()`: Inserts a new record into the database.
     - `update()`: Updates an existing record.
     - `delete()`: Deletes a record from the database.
     - `get()`: Retrieves data from the database.
   - **Usage**: Use this class to interact with the database directly from your API model or controller classes.

### 5. **Model Interface Class**
   - **Purpose**: Defines the structure that all model classes must adhere to. It ensures consistency across all models, making it easier to manage database interactions and API responses.
   - **Main Methods**:
     - `get_data()`: Retrieves data from the model.
     - `save()`: Saves data to the model.
     - `delete()`: Deletes data from the model.
     - `update()`: Updates data in the model.
   - **Usage**: Any custom API model should implement this interface to ensure the necessary methods are available for data interaction.

### 6. **Password Class**
   - **Purpose**: Handles password hashing and validation. This class ensures that passwords are stored securely using modern hashing algorithms.
   - **Main Methods**:
     - `hash()`: Hashes a password.
     - `validate()`: Validates a password by comparing the provided input to the stored hash.
   - **Usage**: Use this class to handle user passwords securely when creating or managing user-related API endpoints.

### 7. **Auth Token Class**
   - **Purpose**: Manages token-based authentication for API endpoints. It helps generate, verify, and validate authentication tokens for secure API access.
   - **Main Methods**:
     - `generate()`: Generates a new authentication token.
     - `validate()`: Validates an existing token to ensure it is still valid.
     - `expire()`: Expires a token when a user logs out or the token is no longer valid.
   - **Usage**: Use this class to implement token-based authentication for your API endpoints, ensuring only authorized users can access sensitive data.

== Changelog ==

= 1.0.0 =
* Initial release with support for creating and managing custom API resources via the CLI.
* Includes classes for managing API controllers, models, permissions, and routes.
* Adds functionality for creating and deleting complete API interfaces.
* Added Database, Model Interface, Password, and Auth Token classes to enhance API security and management.

== Frequently Asked Questions ==

= What does this plugin do? =
WP Custom API simplifies the creation and management of custom API resources in WordPress. Using the provided CLI commands, you can generate API controllers, models, permissions, and routes for your custom resources, without manually creating each component. The plugin also includes security features like token authentication and password hashing.

= How do I use the CLI? =
After activating the plugin, you can access the CLI commands from the root of your WordPress installation. Example commands include `wp custom-api create:controller your-resource-name` to create a controller, and `wp custom-api delete:model your-resource-name` to delete a model.

= Can I use this plugin with other plugins? =
Yes, this plugin is compatible with other plugins as long as they don't conflict with the namespaces or file structure created by this plugin.

= How do I extend or modify the generated files? =
Once you generate a resource (like a controller or model), you can modify the files directly to customize them to your needs. The generated files are fully customizable.

= How does token authentication work? =
Token authentication is used to secure API endpoints. When a user logs in, an authentication token is generated. This token must be sent with API requests to access protected endpoints. The `Auth Token` class handles the generation and validation of tokens.

= How do I use the Database class? =
The `Database` class allows you to interact with your WordPress database directly. You can use it to create tables, insert, update, delete, and retrieve data from custom tables created by your plugin.

== License ==

This plugin is currently not licensed.