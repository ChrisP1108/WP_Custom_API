<?php

declare(strict_types=1);

namespace WP_Custom_API\Controllers;

use \WP_REST_Response as Response;
use WP_Custom_API\Core\Database;
use WP_Custom_API\Core\Auth_Token;
use WP_Custom_API\Models\Sample as Model;

class Sample
{
    public static function get_all(): Response
    {
        $result = Database::get_table_data(Model::table_name());

        if (!$result['ok']) {
            return new Response($result['msg'], 500);
        }

        return new Response($result['data'], 200);
    }
}
