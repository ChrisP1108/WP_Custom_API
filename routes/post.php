<?php

declare(strict_types=1);

namespace WP_Custom_API\Routes;

use WP_Custom_API\Core\Router;
use WP_Custom_API\Controllers\Post as Controller;
use WP_Custom_API\Permissions\Post as Permissions;

/**
 * PUBLIC
 * Gets all posts
 */

Router::get("/post", [Controller::class, "get_all"]);

/**
 * PROTECTED
 * Add post
 */

Router::post("/post", [Controller::class, "add_post"], [Permissions::class, "is_authorized"]);