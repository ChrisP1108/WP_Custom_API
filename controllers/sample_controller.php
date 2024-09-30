<?php

declare(strict_types=1);

namespace WP_Custom_API\Controllers;

use \WP_REST_Response;
use WP_Custom_API\Core\Database;
use WP_Custom_API\Models\Sample_Model;

class Sample_Controller
{
    public static function get_all(): WP_REST_Response
    {
        $result = Database::get_table_data(Sample_Model::TABLE_NAME);

        if (!$result['found']) {
            return new WP_REST_Response([], 200);
        }

        return new WP_REST_Response($result['data'], 200);
    }
}
