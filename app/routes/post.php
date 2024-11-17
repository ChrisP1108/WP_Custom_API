<?php

declare(strict_types=1);

namespace WP_Custom_API\App\Routes;

use WP_Custom_API\Plugin\Router;
use WP_Custom_API\App\Controllers\Post as Controller;
use WP_Custom_API\App\Permissions\Post as Permission;

/**
 * PUBLIC
 * Gets all posts
 */

Router::get("/post", [Controller::class, "get_all"]);

/**
 * PROTECTED
 * Add post
 */

Router::post("/post", [Controller::class, "add_post"], [Permission::class, "is_authorized"]);
