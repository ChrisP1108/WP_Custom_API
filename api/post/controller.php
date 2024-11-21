<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Post;

use WP_REST_Request as Request;
use WP_REST_Response as Response;
use WP_Custom_API\Includes\Database;
use WP_Custom_API\Includes\Auth_Token;
use WP_Custom_API\Api\Post\Model;

class Controller
{
    /**
     * Post Arguments for quering posts
     */

    const POST_ARGS = [
        'post_type' => 'post',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ];

    /**
     * PUBLIC
     * Get all posts
     */

    public static function get_all()
    {
        $posts = get_posts(self::POST_ARGS);
        return new Response($posts, 200);
    }

    /**
     * PUBLIC
     * Get post comment
     */

    public static function get_post_comment(Request $req)
    {
        return new Response($req->get_params(), 200);
    }

    /**
     * PROTECTED
     * Add Post
     */

    public static function add_post(Request $req)
    {
        $decode = json_decode($req->get_body());
        return new Response($decode, 201);
    }
}
