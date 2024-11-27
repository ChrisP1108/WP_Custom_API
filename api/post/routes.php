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

Router::get("/posts", [Controller::class, "index"]);

/**
 * PUBLIC
 * Method: GET
 * Route: /post/{id}
 * Description: Get single post by id
 */

Router::get("/posts/{id}", [Controller::class, "show"]);

/**
 * PUBLIC
 * Method: GET
 * Route: /post/{id}/comment/{comment_id}
 * Description: Get post comment from post id and comment id
 */

Router::get("/posts/{id}/comments/{comment_id}", [Controller::class, "show_comment"]);

/**
 * PROTECTED
 * Method: POST
 * Route: /post
 * Description: Add Post
 */

Router::post("/posts", [Controller::class, "store"], [Permissions::class, "is_authorized"]);
