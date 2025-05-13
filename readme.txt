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

WP Custom API is a WordPress plugin designed to make custom API development easier and more streamlined. 
It allows you to manage your API resources, including controllers, models, permissions, and routes, through simple CLI commands. 
In addition to the basic API management functionality, the plugin includes classes to handle database interactions, user authentication, token management, and password security, ensuring a complete and secure solution for building custom APIs in WordPress.

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

== How It Works ==

The plugin starts with the root wp_custom_api.php file.  From there it makes sure that the PHP version requirement is met.
It will then import a class named `Init` from the /includes/init.php path.
From there, it will load all the files needed for the plugin to work. 
It includes an autoloader as well as a file loader for loading specific files specified in the config.php file.
The config.php file is where much of the plugins custom functionality can be configured, such as password hash rounds, secret keys, token expiration, files to autoload, etc.

== IMPORTANT ==

Avoid modifying any files in the `includes` folder, as these are core files for the plugin.
All files that you will be working with will be inside the `api` folder.

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