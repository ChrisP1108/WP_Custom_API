<?php

declare(strict_types=1);

namespace WP_Custom_API\App\Permissions;

class Post
{
    public static function is_authorized()
    {
        return false;
    }
}
