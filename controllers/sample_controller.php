<?php

declare(strict_types=1);

namespace WP_Custom_API\Controllers;

use \WP_REST_Response;
use WP_Custom_API\Core\Database;
use WP_Custom_API\Models\Sample_Model;
use WP_Custom_API\Core\Auth_Token;

class Sample_Controller
{
    public static function get_all(): WP_REST_Response
    {
        $result = Database::get_table_data(Sample_Model::table_name());

        if (!$result['ok']) {
            return new WP_REST_Response($result['msg'], 500);
        }

        return new WP_REST_Response($result['data'], 200);
    }
}
