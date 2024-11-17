<?php

declare(strict_types=1);

namespace WP_Custom_API\App\Controllers;

use \WP_REST_Response as Response;
use WP_Custom_API\Plugin\Database;
use WP_Custom_API\Plugin\Auth_Token;
use WP_Custom_API\App\Models\Post as Model;

class Post
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
     * PROTECTED
     * Add Post
     */

    public static function add_post($req)
    {
        $decode = json_decode($req->get_body());
        return new Response($decode, 201);
    }
}
