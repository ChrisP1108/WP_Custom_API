<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Post;

class Permission
{
    public const TOKEN_NAME = "post_token";

    public static function is_authorized()
    {
        return false;
    }
}
