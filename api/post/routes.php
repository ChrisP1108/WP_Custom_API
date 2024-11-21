<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Post;

use WP_Custom_API\Includes\Router;
use WP_Custom_API\Api\Post\Controller;
use WP_Custom_API\Api\Post\Permissions;

/**
 * PUBLIC
 * Method: GET
 * Route: /post
 * Description: Gets all posts
 */

Router::get("/posts", [Controller::class, "get_all"]);

/**
 * PUBLIC
 * Method: GET
 * Route: /post/{id}/comment/{comment_id}
 * Description: Get post comment from post id and comment id
 */

Router::get("/posts/{id}/comment/{comment_id}", [Controller::class, "get_post_comment"]);

/**
 * PROTECTED
 * Method: POST
 * Route: /post
 * Description: Add Post
 */

Router::post("/posts", [Controller::class, "add_post"], [Permissions::class, "is_authorized"]);
